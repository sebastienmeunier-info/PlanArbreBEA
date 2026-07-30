<?php

declare(strict_types=1);

use PlanArbreBEA\Core\Logger;

$root = dirname(__DIR__);
$environment = getenv('PLANARBRE_ENV') ?: 'development';
$territoryCenter = [47.5310, -0.1020];

return [
    'app' => [
        'name' => getenv('PLANARBRE_PROJECT_NAME') ?: 'PlanArbreBEA',
        'environment' => $environment,
        'debug' => filter_var(getenv('PLANARBRE_DEBUG') ?: $environment !== 'production', FILTER_VALIDATE_BOOL),
        'base_url' => rtrim((string) (getenv('PLANARBRE_BASE_URL') ?: ''), '/'),
        'timezone' => getenv('PLANARBRE_TIMEZONE') ?: 'Europe/Paris',
        'version' => trim((string) file_get_contents($root . '/VERSION')),
    ],
    /*
     * Adapter uniquement cette section pour déployer l'application sur un
     * autre territoire. Les géométries et les listes métier restent des
     * fichiers de données séparés afin de pouvoir les mettre à jour sans code.
     */
    'territory' => [
        'name' => getenv('PLANARBRE_TERRITORY_NAME') ?: 'Territoire à configurer',
        'center' => $territoryCenter,
        'timezone' => getenv('PLANARBRE_TIMEZONE') ?: 'Europe/Paris',
    ],
    'paths' => [
        'root' => $root,
        'data' => $root . '/data',
        'logs' => $root . '/logs',
        'uploads' => $root . '/uploads',
        'views' => $root . '/resources/templates',
        'public' => $root . '/public',
    ],
    'data_sources' => [
        'trees' => [
            'file' => $root . '/data/arbres.geojson',
            'url' => getenv('PLANARBRE_TREES_GEOJSON_URL') ?: '/api/data/arbres',
        ],
        'proposals' => [
            'file' => $root . '/data/propositions.geojson',
            'url' => getenv('PLANARBRE_PROPOSALS_GEOJSON_URL') ?: '/api/data/propositions',
        ],
        'territory' => [
            'file' => $root . '/data/territoire.geojson',
            'url' => getenv('PLANARBRE_TERRITORY_GEOJSON_URL') ?: '/api/data/territoire',
        ],
        'delegated_municipalities' => [
            'file' => $root . '/data/communes-deleguees.geojson',
            'url' => getenv('PLANARBRE_MUNICIPALITIES_GEOJSON_URL') ?: '/api/data/communes-deleguees',
        ],
    ],
    'planting' => [
        'allowed_species' => [
            'Chêne', 'Érable', 'Tilleul', 'Charme', 'Frêne', 'Merisier',
            'Arbre fruitier', 'Autre',
        ],
        'objectives' => [
            'ombre' => 'Ombre et fraîcheur',
            'biodiversite' => 'Biodiversité',
            'paysage' => 'Paysage',
            'eaux_pluviales' => 'Gestion des eaux pluviales',
            'arbre_fruitier' => 'Arbre fruitier',
            'confort_pieton' => 'Confort piéton',
            'autre' => 'Autre objectif',
        ],
    ],
    'logging' => [
        'file' => $root . '/logs/application.log',
        'minimum_level' => $environment === 'production' ? Logger::WARNING : Logger::DEBUG,
    ],
    'security' => [
        'csrf_token_name' => 'csrf_token',
        'max_upload_size' => 1_048_576,
        'max_photos_per_proposal' => 3,
        'allowed_photo_mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
    ],
    'map' => [
        'center' => $territoryCenter,
        'default_zoom' => 12,
        'min_zoom' => 10,
        'max_zoom' => 20,
    ],
];
