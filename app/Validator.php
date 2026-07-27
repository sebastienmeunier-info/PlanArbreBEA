<?php

declare(strict_types=1);

/**
 * ------------------------------------------------------------
 * PlanArbreBEA
 * Validation des données
 * Version : 1.0.0
 * Licence : GNU AGPL v3
 * ------------------------------------------------------------
 */

final class Validator
{
    /**
     * Nettoyage d'un texte.
     */
    public static function text(
        mixed $value,
        int $maxLength
    ): string {

        $value = trim((string)$value);

        $value = strip_tags($value);

        $value = html_entity_decode(
            $value,
            ENT_QUOTES | ENT_HTML5,
            Config::CHARSET
        );

        $value = preg_replace('/\s+/u', ' ', $value);

        return mb_substr(
            $value,
            0,
            $maxLength
        );

    }

    /**
     * Latitude.
     */
    public static function latitude(
        mixed $value
    ): float {

        if (!is_numeric($value)) {
            throw new InvalidArgumentException(
                "Latitude invalide."
            );
        }

        $lat = (float)$value;

        if ($lat < -90 || $lat > 90) {
            throw new InvalidArgumentException(
                "Latitude hors limites."
            );
        }

        return $lat;

    }

    /**
     * Longitude.
     */
    public static function longitude(
        mixed $value
    ): float {

        if (!is_numeric($value)) {
            throw new InvalidArgumentException(
                "Longitude invalide."
            );
        }

        $lon = (float)$value;

        if ($lon < -180 || $lon > 180) {
            throw new InvalidArgumentException(
                "Longitude hors limites."
            );
        }

        return $lon;

    }

    /**
     * Essence.
     */
    public static function species(
        mixed $value
    ): string {

        $species = self::text(
            $value,
            80
        );

        if ($species === '') {

            throw new InvalidArgumentException(
                "Veuillez choisir une essence."
            );

        }

        if (!Config::isAllowedSpecies($species)) {

            throw new InvalidArgumentException(
                "Essence non autorisée."
            );

        }

        return $species;

    }

    /**
     * Objectifs.
     */
    public static function objectifs(
        mixed $value
    ): array {

        if (!is_array($value)) {
            return [];
        }

        $allowed = [

            'ombre',

            'biodiversite',

            'paysage',

            'eaux',

            'climat',

            'fruitier',

            'autre'

        ];

        $result = [];

        foreach ($value as $objectif) {

            $objectif = self::text(
                $objectif,
                40
            );

            if (
                $objectif !== ''
                && in_array(
                    $objectif,
                    $allowed,
                    true
                )
            ) {

                $result[] = $objectif;

            }

        }

        return array_values(
            array_unique($result)
        );

    }

    /**
     * Validation complète d'une proposition.
     */
    public static function proposal(
        array $post
    ): array {

        return [

            'latitude' => self::latitude(
                $post['latitude'] ?? null
            ),

            'longitude' => self::longitude(
                $post['longitude'] ?? null
            ),

            'author' => self::text(
                $post['author'] ?? '',
                Config::MAX_AUTHOR_LENGTH
            ),

            'address' => self::text(
                $post['address'] ?? '',
                Config::MAX_ADDRESS_LENGTH
            ),

            'commune' => self::text(
                $post['commune'] ?? '',
                80
            ),

            'species' => self::species(
                $post['species'] ?? ''
            ),

            'objectifs' => self::objectifs(
                $post['objectifs'] ?? []
            ),

            'comment' => self::text(
                $post['comment'] ?? '',
                Config::MAX_COMMENT_LENGTH
            )

        };

    }

    /**
     * Validation d'une adresse e-mail.
     */
    public static function email(
        mixed $value
    ): string {

        $email = trim((string)$value);

        if ($email === '') {
            return '';
        }

        if (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            throw new InvalidArgumentException(
                "Adresse e-mail invalide."
            );

        }

        return $email;

    }

}
