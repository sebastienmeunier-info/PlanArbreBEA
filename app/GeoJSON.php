<?php

declare(strict_types=1);

/**
 * ------------------------------------------------------------
 * Plantons
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
                "Impossible de lire le fichier GeoJSON."
            );
        }

        $collection = json_decode($json, true);

        if (!is_array($collection)) {

            return [
                'type' => 'FeatureCollection',
                'features' => []
            ];

        }

        $collection['features'] ??= [];

        return $collection;
    }

    /**
     * Sauvegarde un GeoJSON.
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
                "Impossible d'enregistrer le fichier."
            );

        }

        clearstatcache(true, $filename);

    }

    /**
     * Arbres.
     */
    public static function loadTrees(): array
    {
        return self::load(
            Config::TREES_FILE
        );
    }

    /**
     * Propositions.
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
     * Création d'une Feature Point.
     */
    public static function createProposal(
        array $data,
        ?string $photo = null
    ): array {

        return [

            'type' => 'Feature',

            'geometry' => [

                'type' => 'Point',

                'coordinates' => [

                    $data['longitude'],

                    $data['latitude']

                ]

            ],

            'properties' => [

                'id' => bin2hex(
                    random_bytes(16)
                ),

                'date_creation' => date('c'),

                'nom' => $data['author'],

                'adresse' => $data['address'],

                'commune' => $data['commune'],

                'essence' => $data['species'],

                'objectifs' => $data['objectifs'],

                'commentaire' => $data['comment'],

                'photo' => $photo,

                'statut' => Config::STATUS_PENDING,

                'visible' => true,

                'version' => Config::VERSION

            ]

        ];

    }

}
