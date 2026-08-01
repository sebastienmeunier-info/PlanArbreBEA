<?php

declare(strict_types=1);

namespace Plantons\Controllers;

use Plantons\Core\Application;
use Plantons\Core\Request;
use Plantons\Core\Response;
use Plantons\Repositories\UserRepository;
use Plantons\Services\AuthService;
use Plantons\Services\GeoJsonStore;
use Plantons\Services\PlantonsTransferService;
use RuntimeException;

final class ExportController
{
    public function __construct(private readonly Application $app) {}

    public function download(Request $request): never
    {
        $user = $this->auth()->currentUser();
        if (!in_array($user['role'] ?? null, ['administrateur', 'super_administrateur'], true)) {
            Response::html('Accès réservé aux administrateurs.', 403);
        }

        $source = (string) $request->query('source', 'all');
        $format = (string) $request->query('format', 'geojson');
        if (!in_array($source, ['proposals', 'donations', 'all'], true) || !in_array($format, ['geojson', 'csv', 'qgis', 'plantons'], true)) {
            Response::html('Paramètres d’export invalides.', 422);
        }

        if ($format === 'plantons') {
            Response::download($this->transfer()->export(), 'plantons-transfert-complet.plantons.tar', 'application/x-tar');
        }

        $collection = ['type' => 'FeatureCollection', 'features' => $this->features($source)];
        $suffix = $source === 'all' ? 'plantations-et-dons' : ($source === 'donations' ? 'dons' : 'plantations');

        if ($format === 'csv') {
            Response::download($this->csv($collection['features']), "plantons-{$suffix}.csv", 'text/csv; charset=UTF-8');
        }

        $filename = $format === 'qgis' ? "plantons-qgis-{$suffix}.geojson" : "plantons-{$suffix}.geojson";
        Response::download(
            json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n",
            $filename,
            'application/geo+json; charset=UTF-8',
        );
    }

    public function import(Request $request): never
    {
        $user = $this->auth()->currentUser();
        if (!in_array($user['role'] ?? null, ['administrateur', 'super_administrateur'], true)) {
            Response::html('Accès réservé aux administrateurs.', 403);
        }
        if (!$this->auth()->verifyCsrf((string) $request->input('csrf_token'))) {
            Response::html('Session expirée.', 403);
        }
        $upload = $request->files('plantons_file');
        if (!is_array($upload) || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_string($upload['tmp_name'] ?? null)) {
            Response::html('Sélectionnez une archive Plantons valide.', 422);
        }
        if ((int) ($upload['size'] ?? 0) > (int) ($this->app->config('security')['max_transfer_size'] ?? 104857600)) {
            Response::html('L’archive Plantons est trop volumineuse.', 422);
        }
        $temporaryBase = tempnam(sys_get_temp_dir(), 'plantons-import-');
        $temporary = $temporaryBase === false ? false : $temporaryBase . '.tar';
        if ($temporaryBase !== false) { @unlink($temporaryBase); }
        if ($temporary === false || !move_uploaded_file($upload['tmp_name'], $temporary)) {
            Response::html('Impossible de recevoir l’archive Plantons.', 422);
        }
        try {
            $result = $this->transfer()->import($temporary);
        } catch (RuntimeException $exception) {
            Response::html(htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8'), 422);
        } finally {
            @unlink($temporary);
        }
        $query = http_build_query(['onglet' => 'exports', 'import' => 'done'] + $result);
        header('Location: ' . $this->app->routeUrl('/mon-compte?' . $query), true, 303);
        exit;
    }

    /** @return array<int,array<string,mixed>> */
    private function features(string $requestedSource): array
    {
        $sources = $this->app->config('data_sources');
        $sourceKeys = $requestedSource === 'all' ? ['proposals', 'donations'] : [$requestedSource];
        $usersByEmail = [];
        foreach ((new UserRepository($this->app->config('auth')['users_file']))->all() as $user) {
            $usersByEmail[mb_strtolower((string) ($user['email'] ?? ''))] = $user;
        }

        $features = [];
        $store = new GeoJsonStore();
        foreach ($sourceKeys as $source) {
            foreach ($store->read($sources[$source]['file'])['features'] as $feature) {
                $properties = $feature['properties'] ?? [];
                $contributor = $usersByEmail[mb_strtolower((string) ($properties['email'] ?? ''))] ?? [];
                $photos = array_values(array_filter((array) ($properties['photos'] ?? []), 'is_string'));
                $feature['properties'] = array_replace($properties, [
                    'export_source' => $source === 'donations' ? 'don' : 'plantation',
                    'contributor' => [
                        'name' => trim((string) ($contributor['first_name'] ?? '') . ' ' . (string) ($contributor['last_name'] ?? '')) ?: (string) ($properties['author'] ?? ''),
                        'email' => (string) ($contributor['email'] ?? $properties['email'] ?? ''),
                        'address' => (string) ($contributor['address'] ?? ''),
                        'phone' => (string) ($contributor['phone'] ?? ''),
                        'role' => (string) ($contributor['role'] ?? ''),
                        'created_at' => (string) ($contributor['created_at'] ?? ''),
                    ],
                    'photo_urls' => array_map($this->absolutePublicUrl(...), $photos),
                ]);
                $features[] = $feature;
            }
        }

        return $features;
    }

    /** @param array<int,array<string,mixed>> $features */
    private function csv(array $features): string
    {
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            throw new \RuntimeException('Impossible de préparer le fichier CSV.');
        }

        fputcsv($handle, [
            'source', 'id', 'statut', 'date_creation', 'date_mise_a_jour', 'essence', 'objectifs', 'conditionnement', 'taille_arbre',
            'commentaire', 'adresse', 'commune_deleguee', 'longitude', 'latitude', 'auteur_saisi', 'email_proposition',
            'contributeur_nom', 'contributeur_email', 'contributeur_adresse', 'contributeur_telephone', 'contributeur_role', 'contributeur_inscrit_le',
            'photos_urls',
        ], ';', '"', '');
        foreach ($features as $feature) {
            $properties = $feature['properties'] ?? [];
            $contributor = $properties['contributor'] ?? [];
            $coordinates = $feature['geometry']['coordinates'] ?? [null, null];
            fputcsv($handle, [
                $properties['export_source'] ?? '', $properties['id'] ?? '', $properties['status'] ?? '', $properties['created_at'] ?? '', $properties['updated_at'] ?? '',
                $properties['species'] ?? '', implode(' | ', (array) ($properties['objectives'] ?? [])), $properties['conditioning'] ?? '', $properties['tree_size'] ?? '',
                $properties['comment'] ?? '', $properties['address'] ?? '', $properties['delegated_municipality'] ?? '', $coordinates[0] ?? '', $coordinates[1] ?? '',
                $properties['author'] ?? '', $properties['email'] ?? '', $contributor['name'] ?? '', $contributor['email'] ?? '', $contributor['address'] ?? '',
                $contributor['phone'] ?? '', $contributor['role'] ?? '', $contributor['created_at'] ?? '', implode(' | ', (array) ($properties['photo_urls'] ?? [])),
            ], ';', '"', '');
        }
        rewind($handle);
        $content = stream_get_contents($handle) ?: '';
        fclose($handle);
        return "\xEF\xBB\xBF" . $content;
    }

    private function absolutePublicUrl(string $path): string
    {
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        $baseUrl = rtrim((string) $this->app->config('app')['base_url'], '/');
        if ($baseUrl === '') {
            $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
            if ($host !== '') {
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $baseUrl = $scheme . '://' . $host . rtrim((string) $this->app->config('app')['base_path'], '/');
            }
        }
        return $baseUrl === '' ? $this->app->url($path) : $baseUrl . '/' . ltrim($path, '/');
    }

    private function auth(): AuthService
    {
        return new AuthService(new UserRepository($this->app->config('auth')['users_file']), $this->app->config('auth'));
    }

    private function transfer(): PlantonsTransferService
    {
        return new PlantonsTransferService($this->app->config('paths'), $this->app->config('auth'), $this->app->config('data_sources'));
    }
}
