<?php

declare(strict_types=1);

/**
 * ------------------------------------------------------------
 * PlanArbreBEA
 * Vérification d'appartenance au territoire
 * Version : 1.0.0
 * Licence : GNU AGPL v3
 * ------------------------------------------------------------
 */

final class Territory
{
    /**
     * Géométrie chargée en mémoire.
     */
    private static ?array $geometry = null;

    /**
     * Vérifie si un point est dans le territoire.
     */
    public static function contains(
        float $latitude,
        float $longitude
    ): bool {

        $geometry = self::geometry();

        return match ($geometry['type']) {

            'Polygon' =>
                self::polygonContains(
                    $geometry['coordinates'],
                    $longitude,
                    $latitude
                ),

            'MultiPolygon' =>
                self::multiPolygonContains(
                    $geometry['coordinates'],
                    $longitude,
                    $latitude
                ),

            default => false

        };

    }

    /**
     * Charge la géométrie.
     */
    private static function geometry(): array
    {
        if (self::$geometry !== null) {
            return self::$geometry;
        }

        if (!file_exists(Config::TERRITORY_FILE)) {

            throw new RuntimeException(
                "Fichier territoire introuvable."
            );

        }

        $json = file_get_contents(
            Config::TERRITORY_FILE
        );

        if ($json === false) {

            throw new RuntimeException(
                "Lecture impossible du territoire."
            );

        }

        $geojson = json_decode(
            $json,
            true
        );

        if (!is_array($geojson)) {

            throw new RuntimeException(
                "GeoJSON invalide."
            );

        }

        if (
            !isset($geojson['features'][0]['geometry'])
        ) {

            throw new RuntimeException(
                "Aucune géométrie trouvée."
            );

        }

        self::$geometry =
            $geojson['features'][0]['geometry'];

        return self::$geometry;

    }

    /**
     * Polygon.
     */
    private static function polygonContains(
        array $rings,
        float $x,
        float $y
    ): bool {

        if (empty($rings)) {
            return false;
        }

        if (!self::pointInRing($rings[0], $x, $y)) {
            return false;
        }

        foreach (array_slice($rings, 1) as $hole) {

            if (self::pointInRing($hole, $x, $y)) {
                return false;
            }

        }

        return true;

    }

    /**
     * MultiPolygon.
     */
    private static function multiPolygonContains(
        array $polygons,
        float $x,
        float $y
    ): bool {

        foreach ($polygons as $polygon) {

            if (
                self::polygonContains(
                    $polygon,
                    $x,
                    $y
                )
            ) {

                return true;

            }

        }

        return false;

    }

    /**
     * Algorithme Ray Casting.
     */
    private static function pointInRing(
        array $ring,
        float $x,
        float $y
    ): bool {

        $inside = false;

        $count = count($ring);

        if ($count < 3) {
            return false;
        }

        for (
            $i = 0, $j = $count - 1;
            $i < $count;
            $j = $i++
        ) {

            $xi = $ring[$i][0];
            $yi = $ring[$i][1];

            $xj = $ring[$j][0];
            $yj = $ring[$j][1];

            $intersect =
                (
                    ($yi > $y)
                    !==
                    ($yj > $y)
                )
                &&
                (
                    $x <
                    (
                        ($xj - $xi)
                        *
                        ($y - $yi)
                        /
                        (($yj - $yi) ?: 1e-12)
                    )
                    + $xi
                );

            if ($intersect) {
                $inside = !$inside;
            }

        }

        return $inside;

    }

    /**
     * Vide le cache.
     */
    public static function clearCache(): void
    {
        self::$geometry = null;
    }

}
