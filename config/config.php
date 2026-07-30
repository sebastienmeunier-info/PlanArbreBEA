<?php

declare(strict_types=1);

use PlanArbreBEA\Core\Logger;

$root = dirname(__DIR__);
$environment = getenv('PLANARBRE_ENV') ?: 'development';
$territoryCenter = [47.5310, -0.1020];

return [
    'app' => [
        'name' => getenv('PLANARBRE_PROJECT_NAME') ?: 'Plan Arbre Baugé en Anjou',
        'logo_url' => getenv('PLANARBRE_PROJECT_LOGO_URL') ?: 'https://www.sebastienmeunier.info/wp-content/uploads/2024/12/sebmeunier-300x300.png',
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
        'name' => getenv('PLANARBRE_TERRITORY_NAME') ?: 'Baugé-en-Anjou',
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
        'target_count' => 1000,
        'max_objectives_per_proposal' => 3,
        'allowed_species' => [
            'Chêne', 'Érable', 'Tilleul', 'Charme', 'Frêne', 'Merisier',
            'Arbre fruitier', 'Autre',
        ],
        'objectives' => [
            'ombre' => ['label' => 'Ombre et fraîcheur', 'icon' => '🌳'],
            'biodiversite' => ['label' => 'Biodiversité', 'icon' => '🐝'],
            'paysage' => ['label' => 'Paysage', 'icon' => '🏞️'],
            'eaux_pluviales' => ['label' => 'Gestion des eaux pluviales', 'icon' => '💧'],
            'arbre_fruitier' => ['label' => 'Arbre fruitier', 'icon' => '🍎'],
            'confort_pieton' => ['label' => 'Confort piéton', 'icon' => '🚶'],
            'autre' => ['label' => 'Autre objectif', 'icon' => '✦'],
        ],
    ],
    'logging' => [
        'file' => $root . '/logs/application.log',
        'minimum_level' => $environment === 'production' ? Logger::WARNING : Logger::DEBUG,
    ],
    'security' => [
        'csrf_token_name' => 'csrf_token',
        'max_upload_size' => 512_000,
        'max_photos_per_proposal' => 3,
        'max_email_length' => 254,
        'allowed_photo_mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
    ],
    'notifications' => [
        'status_changes' => true,
        'events' => ['validee', 'rejetee', 'arbre_plante', 'localisation_deplacee'],
    ],
    'proposals' => [
        'status_labels' => [
            'a_valider' => 'Proposée',
            'refusee' => 'Refusée',
            'rejetee' => 'Refusée',
            'validee' => 'Validée',
            'arbre_plante' => 'Arbre planté',
            'realisee' => 'Arbre planté',
        ],
    ],
    'auth' => [
        'users_file' => $root . '/data/utilisateurs.json',
        'password_resets_file' => $root . '/data/reinitialisations.json',
        'session_key' => 'planarbre_user_id',
        'bootstrap_admin_email' => strtolower((string) (getenv('PLANARBRE_BOOTSTRAP_ADMIN_EMAIL') ?: '')),
        'mail_enabled' => filter_var(getenv('PLANARBRE_MAIL_ENABLED') ?: false, FILTER_VALIDATE_BOOL),
        'mail_from' => getenv('PLANARBRE_MAIL_FROM') ?: 'noreply@example.org',
    ],
    'map' => [
        'center' => $territoryCenter,
        'default_zoom' => 12,
        'min_zoom' => 10,
        'max_zoom' => 20,
    ],
];
