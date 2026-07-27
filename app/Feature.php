<?php

declare(strict_types=1);

/**
 * ------------------------------------------------------------
 * PlanArbreBEA
 * Fabrique d'objets GeoJSON
 * Version : 1.0.0
 * Licence : GNU AGPL v3
 * ------------------------------------------------------------
 */

final class Feature
{
    /**
     * Crée une proposition de plantation.
     */
    public static function proposal(
        array $data,
        ?string $photo = null
    ): array {

        return [

            'type' => 'Feature',

            'geometry' => [

                'type' => 'Point',

                'coordinates' => [

                    (float) $data['longitude'],

                    (float) $data['latitude']

                ]

            ],

            'properties' => [

                'id' => self::uuid(),

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

    /**
     * Génère un identifiant unique.
     */
    private static function uuid(): string
    {
        return bin2hex(
            random_bytes(16)
        );
    }

}
