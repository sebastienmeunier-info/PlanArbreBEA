<?php

declare(strict_types=1);

namespace Plantons\Services;

final class TerritoryService
{
    public function contains(array $collection, float $longitude, float $latitude): bool
    {
        foreach ($collection['features'] ?? [] as $feature) {
            $geometry = $feature['geometry'] ?? [];
            if ($this->geometryContains($geometry, $longitude, $latitude)) {
                return true;
            }
        }
        return false;
    }

    public function municipality(array $collection, float $longitude, float $latitude): ?string
    {
        foreach ($collection['features'] ?? [] as $feature) {
            if ($this->geometryContains($feature['geometry'] ?? [], $longitude, $latitude)) {
                $properties = $feature['properties'] ?? [];
                return $properties['nom'] ?? $properties['name'] ?? $properties['libelle'] ?? null;
            }
        }
        return null;
    }

    private function geometryContains(array $geometry, float $longitude, float $latitude): bool
    {
        $type = $geometry['type'] ?? '';
        $coordinates = $geometry['coordinates'] ?? [];
        $polygons = $type === 'Polygon' ? [$coordinates] : ($type === 'MultiPolygon' ? $coordinates : []);
        foreach ($polygons as $polygon) {
            if (isset($polygon[0]) && $this->pointInRing($polygon[0], $longitude, $latitude)) {
                $inHole = false;
                foreach (array_slice($polygon, 1) as $hole) {
                    $inHole = $inHole || $this->pointInRing($hole, $longitude, $latitude);
                }
                if (!$inHole) { return true; }
            }
        }
        return false;
    }

    private function pointInRing(array $ring, float $x, float $y): bool
    {
        $inside = false;
        for ($i = 0, $j = count($ring) - 1; $i < count($ring); $j = $i++) {
            [$xi, $yi] = $ring[$i];
            [$xj, $yj] = $ring[$j];
            $intersects = (($yi > $y) !== ($yj > $y)) && ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi);
            if ($intersects) { $inside = !$inside; }
        }
        return $inside;
    }
}
