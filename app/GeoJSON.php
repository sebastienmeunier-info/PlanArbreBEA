<?php

declare(strict_types=1);

/**
 * ------------------------------------------------------------
 * PlanArbreBEA
 * Gestion des fichiers GeoJSON
 * Version : 1.0.0
 * Licence : GNU AGPL v3
 * ------------------------------------------------------------
 */

final class GeoJSON
{
    /**
     * Charge un fichier GeoJSON.
     */
    private static function load(string $filename): array
    {
        if (!file_exists($filename)) {

            return [
                'type' => 'FeatureCollection',
                'features' => []
            ];

        }

        $json = file_get_contents($filename);

        if ($json === false) {

            throw new RuntimeException(
                "Impossible de lire le fichier : {$filename}"
            );

        }

        $collection = json_decode($json, true);

        if (!is_array($collection)) {

            throw new RuntimeException(
                "GeoJSON invalide : {$filename}"
            );

        }

        $collection['type'] ??= 'FeatureCollection';
        $collection['features'] ??= [];

        return $collection;
    }

    /**
     * Sauvegarde un fichier GeoJSON.
     */
    private static function save(
        string $filename,
        array $collection
    ): void {

        $json = json_encode(
            $collection,
            Config::JSON_OPTIONS
        );

        if ($json === false) {

            throw new RuntimeException(
                "Erreur lors de la génération du GeoJSON."
            );

        }

        if (
            file_put_contents(
                $filename,
                $json,
                LOCK_EX
            ) === false
        ) {

            throw new RuntimeException(
                "Impossible d'écrire le fichier : {$filename}"
            );

        }

        @chmod(
            $filename,
            Config::FILE_PERMISSIONS
        );

        clearstatcache(
            true,
            $filename
        );

    }

    /**
     * Charge les arbres.
     */
    public static function loadTrees(): array
    {
        return self::load(
            Config::TREES_FILE
        );
    }

    /**
     * Charge les propositions.
     */
    public static function loadProposals(): array
    {
        return self::load(
            Config::PROPOSALS_FILE
        );
    }

    /**
     * Ajoute une proposition.
     */
    public static function appendProposal(
        array $feature
    ): void {

        $collection = self::loadProposals();

        $collection['features'][] = $feature;

        /*
         * Tri des propositions
         * Les plus récentes en premier
         */

        usort(

            $collection['features'],

            static function (
                array $a,
                array $b
            ): int {

                return strcmp(
                    $b['properties']['date_creation'],
                    $a['properties']['date_creation']
                );

            }

        );

        self::save(
            Config::PROPOSALS_FILE,
            $collection
        );

    }

    /**
     * Nombre de propositions.
     */
    public static function proposalCount(): int
    {
        return count(
            self::loadProposals()['features']
        );
    }

    /**
     * Vérifie qu'un GeoJSON est valide.
     */
    public static function isValid(
        array $collection
    ): bool {

        return
            isset($collection['type'])
            &&
            $collection['type'] === 'FeatureCollection'
            &&
            isset($collection['features'])
            &&
            is_array($collection['features']);

    }

}
