<?php

declare(strict_types=1);

namespace PlanArbreBEA\Services;

use RuntimeException;

final class GeoJsonStore
{
    public function read(string $file): array
    {
        if (!is_file($file) || filesize($file) === 0) {
            return ['type' => 'FeatureCollection', 'features' => []];
        }

        $data = json_decode((string) file_get_contents($file), true);
        if (!is_array($data) || ($data['type'] ?? null) !== 'FeatureCollection') {
            throw new RuntimeException('Le fichier GeoJSON est invalide.');
        }

        return $data;
    }

    public function appendFeature(string $file, array $feature): void
    {
        $directory = dirname($file);
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Impossible de créer le répertoire de données.');
        }

        $handle = fopen($file, 'c+');
        if ($handle === false || !flock($handle, LOCK_EX)) {
            throw new RuntimeException('Impossible de verrouiller le fichier de données.');
        }

        try {
            $content = stream_get_contents($handle);
            $collection = $content === '' ? ['type' => 'FeatureCollection', 'features' => []] : json_decode($content, true);
            if (!is_array($collection) || ($collection['type'] ?? null) !== 'FeatureCollection') {
                throw new RuntimeException('Le fichier de propositions est invalide.');
            }
            $collection['features'][] = $feature;
            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
