<?php

class Territory
{
    /**
     * Charge le fichier GeoJSON représentant le territoire.
     *
     * @return array
     * @throws RuntimeException
     */
    public static function loadBoundary(): array
    {
        $file = __DIR__ . '/../data/territoire.geojson';

        if (!is_file($file)) {
            throw new RuntimeException("Fichier territoire introuvable : $file");
        }

        $json = file_get_contents($file);

        if ($json === false) {
            throw new RuntimeException("Impossible de lire le fichier : $file");
        }

        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(
                "GeoJSON invalide : " . json_last_error_msg()
            );
        }

        if (!is_array($data)) {
            throw new RuntimeException("Le GeoJSON ne contient pas un tableau valide.");
        }

        return $data;
    }

    public static function contains(float $lat, float $lng): bool
    {
        $geo = self::loadBoundary();

        foreach (($geo['features'] ?? []) as $feature) {
            $geometry = $feature['geometry'] ?? [];

            switch ($geometry['type'] ?? '') {
                case 'Polygon':
                    if (self::polygonContains($lng, $lat, $geometry['coordinates'])) {
                        return true;
                    }
                    break;

                case 'MultiPolygon':
                    foreach ($geometry['coordinates'] as $polygon) {
                        if (self::polygonContains($lng, $lat, $polygon)) {
                            return true;
                        }
                    }
                    break;
            }
        }

        return false;
    }

    private static function polygonContains(float $x, float $y, array $polygon): bool
    {
        foreach ($polygon as $ring) {
            if (self::pointInRing($x, $y, $ring)) {
                return true;
            }
        }

        return false;
    }

    private static function pointInRing(float $x, float $y, array $ring): bool
    {
        $inside = false;
        $n = count($ring);

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            [$xi, $yi] = $ring[$i];
            [$xj, $yj] = $ring[$j];

            $intersects =
                (($yi > $y) !== ($yj > $y))
                && ($x < ($xj - $xi) * ($y - $yi) / (($yj - $yi) ?: 1e-12) + $xi);

            if ($intersects) {
                $inside = !$inside;
            }
        }

        return $inside;
    }
}
