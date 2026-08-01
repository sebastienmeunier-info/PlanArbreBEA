<?php

declare(strict_types=1);

namespace Plantons\Services;

use Plantons\Repositories\UserRepository;
use RuntimeException;

/** Builds and imports self-contained Plantons transfer archives. */
final class PlantonsTransferService
{
    public function __construct(
        private readonly array $paths,
        private readonly array $auth,
        private readonly array $sources,
    ) {}

    public function export(): string
    {
        $archiveFile = tempnam(sys_get_temp_dir(), 'plantons-transfer-');
        if ($archiveFile === false) {
            throw new RuntimeException('Impossible de préparer l’archive Plantons.');
        }
        @unlink($archiveFile);
        $archiveFile .= '.tar';

        try {
            $archive = new \PharData($archiveFile);
            $store = new GeoJsonStore();
            $proposals = $store->read($this->sources['proposals']['file']);
            $donations = $store->read($this->sources['donations']['file']);
            $users = (new UserRepository($this->auth['users_file']))->all();
            $archive->addFromString('manifest.json', json_encode([
                'format' => 'plantons-transfer', 'version' => 1, 'exported_at' => date(DATE_ATOM),
                'contents' => ['users', 'proposals', 'donations', 'photos'],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $archive->addFromString('data/utilisateurs.json', json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $archive->addFromString('data/propositions.geojson', json_encode($proposals, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $archive->addFromString('data/dons.geojson', json_encode($donations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $this->addPhotos($archive, $proposals['features'] ?? []);
            $this->addPhotos($archive, $donations['features'] ?? []);
            unset($archive);
            $content = file_get_contents($archiveFile);
            if ($content === false) {
                throw new RuntimeException('Impossible de lire l’archive Plantons.');
            }
            return $content;
        } finally {
            @unlink($archiveFile);
        }
    }

    /** @return array{users:int,proposals:int,donations:int,photos:int} */
    public function import(string $archiveFile): array
    {
        try {
            $archive = new \PharData($archiveFile);
            $manifest = $this->jsonFile($archive, 'manifest.json');
            if (($manifest['format'] ?? '') !== 'plantons-transfer' || ($manifest['version'] ?? null) !== 1) {
                throw new RuntimeException('Ce fichier n’est pas une archive Plantons compatible.');
            }
            $users = $this->jsonFile($archive, 'data/utilisateurs.json');
            $proposals = $this->collection($this->jsonFile($archive, 'data/propositions.geojson'));
            $donations = $this->collection($this->jsonFile($archive, 'data/dons.geojson'));
            if (!is_array($users) || !array_is_list($users)) {
                throw new RuntimeException('La liste des utilisateurs est invalide.');
            }
            $this->validatePhotos($archive, $proposals['features']);
            $this->validatePhotos($archive, $donations['features']);

            $userCount = (new UserRepository($this->auth['users_file']))->merge($users);
            $store = new GeoJsonStore();
            $proposalCount = $store->mergeFeatures($this->sources['proposals']['file'], $proposals['features']);
            $donationCount = $store->mergeFeatures($this->sources['donations']['file'], $donations['features']);
            $photoCount = $this->copyPhotos($archive, $proposals['features']) + $this->copyPhotos($archive, $donations['features']);
            return ['users' => $userCount, 'proposals' => $proposalCount, 'donations' => $donationCount, 'photos' => $photoCount];
        } catch (\UnexpectedValueException $exception) {
            throw new RuntimeException('L’archive Plantons est illisible.', 0, $exception);
        }
    }

    private function addPhotos(\PharData $archive, array $features): void
    {
        foreach ($this->photoPaths($features) as $path) {
            $source = $this->paths['root'] . '/' . $path;
            if (is_file($source)) {
                $archive->addFile($source, $path);
            }
        }
    }

    private function validatePhotos(\PharData $archive, array $features): void
    {
        foreach ($this->photoPaths($features) as $path) {
            if (!$archive->offsetExists($path)) {
                throw new RuntimeException('Une photo référencée est absente de l’archive.');
            }
        }
    }

    private function copyPhotos(\PharData $archive, array $features): int
    {
        $count = 0;
        foreach ($this->photoPaths($features) as $path) {
            $destination = $this->paths['root'] . '/' . $path;
            if (is_file($destination)) {
                continue;
            }
            $directory = dirname($destination);
            if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new RuntimeException('Impossible de créer le dossier des photos importées.');
            }
            $content = $archive[$path]->getContent();
            if (file_put_contents($destination, $content, LOCK_EX) === false) {
                throw new RuntimeException('Impossible d’enregistrer une photo importée.');
            }
            $count++;
        }
        return $count;
    }

    /** @return array<int,string> */
    private function photoPaths(array $features): array
    {
        $paths = [];
        foreach ($features as $feature) {
            foreach ((array) ($feature['properties']['photos'] ?? []) as $path) {
                if (!is_string($path) || !preg_match('#^uploads/photos/[A-Za-z0-9._/-]+$#', $path) || str_contains($path, '..')) {
                    throw new RuntimeException('Le chemin d’une photo dans l’archive est invalide.');
                }
                $paths[$path] = $path;
            }
        }
        return array_values($paths);
    }

    private function jsonFile(\PharData $archive, string $path): array
    {
        if (!$archive->offsetExists($path)) {
            throw new RuntimeException('L’archive Plantons est incomplète.');
        }
        $data = json_decode($archive[$path]->getContent(), true);
        if (!is_array($data)) {
            throw new RuntimeException('Un fichier de données de l’archive est invalide.');
        }
        return $data;
    }

    private function collection(array $collection): array
    {
        if (($collection['type'] ?? null) !== 'FeatureCollection' || !is_array($collection['features'] ?? null)) {
            throw new RuntimeException('Un fichier GeoJSON de l’archive est invalide.');
        }
        return $collection;
    }
}
