<?php

declare(strict_types=1);

/**
 * ------------------------------------------------------------
 * Plantons
 * Configuration générale
 * Version : 1.0.0
 * Build   : 20260727
 * Licence : GNU AGPL v3
 * ------------------------------------------------------------
 */

final class Config
{
    /*
    ============================================================
    APPLICATION
    ============================================================
    */

    public const APP_NAME = 'Plantons';

    public const VERSION = '1.0.0';

    public const BUILD = '20260727';

    public const APP_ENV = 'development';

    public const DEBUG = true;

    public const BASE_URL = '';

    public const CHARSET = 'UTF-8';

    public const TIMEZONE = 'Europe/Paris';

    /*
    ============================================================
    REPERTOIRES
    ============================================================
    */

    public const DATA_DIR = __DIR__ . '/../data';

    public const UPLOAD_DIR = __DIR__ . '/../uploads';

    public const PHOTO_DIR = self::UPLOAD_DIR . '/photos';

    public const EXPORT_DIR = self::UPLOAD_DIR . '/exports';

    public const DIRECTORY_PERMISSIONS = 0755;

    public const FILE_PERMISSIONS = 0644;

    /*
    ============================================================
    FICHIERS
    ============================================================
    */

    public const TREES_FILE =
        self::DATA_DIR . '/arbres.geojson';

    public const PROPOSALS_FILE =
        self::DATA_DIR . '/propositions.geojson';

    public const TERRITORY_FILE =
        self::DATA_DIR . '/territoire.geojson';

    public const COMMUNES_FILE =
        self::DATA_DIR . '/communes-deleguees.geojson';

    /*
    ============================================================
    PHOTOS
    ============================================================
    */

    public const PHOTO_MAX_SIZE = 1024 * 1024;

    public const PHOTO_MAX_WIDTH = 1600;

    public const PHOTO_QUALITY = 80;

    public const PHOTO_EXTENSION = 'webp';

    public const PHOTO_ALLOWED_TYPES = [

        'image/jpeg',

        'image/png',

        'image/webp'

    ];

    /*
    ============================================================
    CHAMPS TEXTE
    ============================================================
    */

    public const MAX_AUTHOR_LENGTH = 80;

    public const MAX_ADDRESS_LENGTH = 255;

    public const MAX_COMMENT_LENGTH = 1000;

    /*
    ============================================================
    ESSENCES
    ============================================================
    */

    public const ALLOWED_SPECIES = [

        'Chêne',

        'Érable',

        'Tilleul',

        'Charme',

        'Frêne',

        'Merisier',

        'Arbre fruitier',

        'Autre'

    ];

    /*
    ============================================================
    OBJECTIFS
    ============================================================
    */

    public const ALLOWED_OBJECTIVES = [

        'ombre',

        'biodiversite',

        'paysage',

        'eaux',

        'climat',

        'fruitier',

        'autre'

    ];

    /*
    ============================================================
    CARTE
    ============================================================
    */

    public const MAP_CENTER_LAT = 47.5310;

    public const MAP_CENTER_LON = -0.1020;

    public const MAP_DEFAULT_ZOOM = 12;

    public const MAP_MIN_ZOOM = 10;

    public const MAP_MAX_ZOOM = 20;

    /*
    ============================================================
    STATUTS
    ============================================================
    */

    public const STATUS_PENDING = 'a_valider';

    public const STATUS_VALIDATED = 'validee';

    public const STATUS_REJECTED = 'refusee';

    public const STATUS_DONE = 'realisee';

    /*
    ============================================================
    JSON
    ============================================================
    */

    public const JSON_OPTIONS =
        JSON_PRETTY_PRINT
        | JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES;

    /*
    ============================================================
    INITIALISATION
    ============================================================
    */

    public static function init(): void
    {
        date_default_timezone_set(self::TIMEZONE);

        mb_internal_encoding(self::CHARSET);
    }

    /*
    ============================================================
    DOSSIERS
    ============================================================
    */

    public static function uploadDirectory(
        string $type
    ): string {

        $directory =
            self::UPLOAD_DIR
            . DIRECTORY_SEPARATOR
            . $type
            . DIRECTORY_SEPARATOR
            . date('Y');

        if (!is_dir($directory)) {

            if (
                !mkdir(
                    $directory,
                    self::DIRECTORY_PERMISSIONS,
                    true
                )
                && !is_dir($directory)
            ) {

                throw new RuntimeException(
                    "Impossible de créer le dossier : {$directory}"
                );

            }

        }

        return $directory;

    }

    public static function photoDirectory(): string
    {
        return self::uploadDirectory('photos');
    }

    public static function exportDirectory(): string
    {
        return self::uploadDirectory('exports');
    }

    /*
    ============================================================
    CHEMINS RELATIFS
    ============================================================
    */

    public static function photoRelativePath(
        string $filename
    ): string {

        return sprintf(
            'uploads/photos/%s/%s',
            date('Y'),
            $filename
        );

    }

    /*
    ============================================================
    VALIDATION
    ============================================================
    */

    public static function isAllowedSpecies(
        string $species
    ): bool {

        return in_array(
            $species,
            self::ALLOWED_SPECIES,
            true
        );

    }

    public static function isAllowedObjective(
        string $objective
    ): bool {

        return in_array(
            $objective,
            self::ALLOWED_OBJECTIVES,
            true
        );

    }

    /*
    ============================================================
    DIVERS
    ============================================================
    */

    public static function isDebug(): bool
    {
        return self::DEBUG;
    }

    public static function isProduction(): bool
    {
        return self::APP_ENV === 'production';
    }

}
