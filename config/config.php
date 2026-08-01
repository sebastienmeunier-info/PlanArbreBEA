<?php

declare(strict_types=1);

use Plantons\Core\Logger;

$root = dirname(__DIR__);
$environment = getenv('PLANTONS_ENV') ?: 'development';
$projectName = getenv('PLANTONS_PROJECT_NAME') ?: 'Plantons';
$territoryName = getenv('PLANTONS_TERRITORY_NAME') ?: 'Baugé-en-Anjou';
$territoryCenter = [47.5310, -0.1020];
$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$detectedBasePath = str_ends_with($scriptName, '.php') ? str_replace('\\', '/', dirname($scriptName)) : '';
$basePath = str_replace('\\', '/', rtrim((string) (getenv('PLANTONS_BASE_PATH') ?: $detectedBasePath), '/.'));
$basePath = $basePath === '/' ? '' : $basePath;

return [
    'app' => [
        'name' => $projectName,
        'header_text' => getenv('PLANTONS_HEADER_TEXT') ?: 'Plantons des arbres dans notre commune',
        'logo_url' => getenv('PLANTONS_PROJECT_LOGO_URL') ?: 'https://www.sebastienmeunier.info/wp-content/uploads/2024/12/sebmeunier-300x300.png',
        'environment' => $environment,
        'debug' => filter_var(getenv('PLANTONS_DEBUG') ?: $environment !== 'production', FILTER_VALIDATE_BOOL),
        'base_url' => rtrim((string) (getenv('PLANTONS_BASE_URL') ?: ''), '/'),
        // Détecté automatiquement (ex. /PlanArbreBEA). À surcharger avec
        // PLANTONS_BASE_PATH seulement si l'hébergement utilise une règle particulière.
        'base_path' => $basePath,
        // Le mode query ne dépend pas de mod_rewrite et fonctionne sur les
        // hébergements mutualisés où les règles .htaccess sont désactivées.
        'routing_mode' => getenv('PLANTONS_ROUTING_MODE') ?: 'query',
        'timezone' => getenv('PLANTONS_TIMEZONE') ?: 'Europe/Paris',
        'version' => trim((string) file_get_contents($root . '/VERSION')),
    ],
    /*
     * Adapter uniquement cette section pour déployer l'application sur un
     * autre territoire. Les géométries et les listes métier restent des
     * fichiers de données séparés afin de pouvoir les mettre à jour sans code.
     */
    'territory' => [
        'name' => $territoryName,
        'center' => $territoryCenter,
        'timezone' => getenv('PLANTONS_TIMEZONE') ?: 'Europe/Paris',
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
            'url' => getenv('PLANTONS_TREES_GEOJSON_URL') ?: '/api/data/arbres',
        ],
        'proposals' => [
            'file' => $root . '/data/propositions.geojson',
            'url' => getenv('PLANTONS_PROPOSALS_GEOJSON_URL') ?: '/api/data/propositions',
        ],
        'donations' => [
            'file' => $root . '/data/dons.geojson',
            'url' => getenv('PLANTONS_DONATIONS_GEOJSON_URL') ?: '/api/data/dons',
        ],
        'territory' => [
            'file' => $root . '/data/territoire.geojson',
            'url' => getenv('PLANTONS_TERRITORY_GEOJSON_URL') ?: '/api/data/territoire',
        ],
        'delegated_municipalities' => [
            'file' => $root . '/data/communes-deleguees.geojson',
            'url' => getenv('PLANTONS_MUNICIPALITIES_GEOJSON_URL') ?: '/api/data/communes-deleguees',
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
        'tree_conditioning' => [
            'pot' => ['label' => 'En pot', 'icon' => '🪴'],
            'pleine_terre' => ['label' => 'Pleine terre', 'icon' => '🌱'],
        ],
        'tree_sizes' => [
            'moins_1m' => ['label' => 'Moins d’1 m', 'icon' => '🌱'],
            '1_a_2m' => ['label' => 'De 1 à 2 m', 'icon' => '🌿'],
            '2_a_4m' => ['label' => 'De 2 à 4 m', 'icon' => '🌳'],
            'plus_4m' => ['label' => 'Plus de 4 m', 'icon' => '🌲'],
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
        'max_transfer_size' => 104857600,
        'max_email_length' => 254,
        'allowed_photo_mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
    ],
    'privacy' => [
        // Données affichées sur les cartes publiques. 4 décimales représentent
        // environ 11 m : suffisant pour visualiser une proposition sans publier
        // son emplacement exact.
        'public_coordinate_precision' => 4,
        'public_proposal_properties' => ['status', 'species', 'objectives', 'conditioning', 'tree_size'],
        // Mentions à adapter par chaque collectivité avant mise en production.
        'controller_name' => getenv('PLANTONS_PRIVACY_CONTROLLER') ?: 'Commune de ' . $territoryName,
        'contact_email' => getenv('PLANTONS_PRIVACY_CONTACT_EMAIL') ?: 'contact@sebastienmeunier.info',
        'dpo_email' => getenv('PLANTONS_DPO_EMAIL') ?: '',
        'legal_basis' => getenv('PLANTONS_PRIVACY_LEGAL_BASIS') ?: 'mission d’intérêt public exercée par la collectivité',
        'account_retention' => getenv('PLANTONS_ACCOUNT_RETENTION') ?: 'pendant la durée d’utilisation du compte, puis 3 ans après sa dernière activité',
        'proposal_retention' => getenv('PLANTONS_PROPOSAL_RETENTION') ?: '5 ans après la clôture de la proposition',
    ],
    'notifications' => [
        'status_changes' => true,
        'events' => ['validee', 'rejetee', 'arbre_plante', 'localisation_deplacee'],
        // Variables disponibles : {{project_name}}, {{first_name}}, {{last_name}},
        // {{proposal_id}}, {{species}}, {{location}}, {{comment}}, {{administrator_name}}, {{administrator_email}}, {{instance_url}}, {{url}}.
        'messages' => [
            'account_invitation' => [
                'subject' => 'Activation de votre compte · {{project_name}}',
                'body' => "Bonjour {{first_name}},\n\nVotre compte a été créé. Définissez votre mot de passe en suivant ce lien, valable 7 jours :\n{{url}}\n\nÀ bientôt,\n{{project_name}}",
            ],
            'password_reset' => [
                'subject' => 'Réinitialisation de votre mot de passe · {{project_name}}',
                'body' => "Bonjour {{first_name}},\n\nUtilisez ce lien valable une heure pour choisir un nouveau mot de passe :\n{{url}}\n\n{{project_name}}",
            ],
            'account_imported' => [
                'subject' => 'Votre compte a été importé · {{project_name}}',
                'body' => "Bonjour {{first_name}},\n\nVotre compte a été importé sur cette instance :\n{{instance_url}}\n\nPour choisir votre nouveau mot de passe, utilisez ce lien valable 7 jours :\n{{url}}\n\n{{project_name}}",
            ],
            'proposal_validated' => [
                'subject' => 'Votre proposition a été validée · {{project_name}}',
                'body' => "Bonjour {{first_name}},\n\nVotre proposition {{proposal_id}} pour {{species}} a été validée.\n\nCommentaire de l’administration :\n{{comment}}\n\nModification réalisée par : {{administrator_name}} ({{administrator_email}})\n\n{{project_name}}",
            ],
            'proposal_rejected' => [
                'subject' => 'Décision concernant votre proposition · {{project_name}}',
                'body' => "Bonjour {{first_name}},\n\nVotre proposition {{proposal_id}} pour {{species}} n’a pas été retenue.\n\nCommentaire de l’administration :\n{{comment}}\n\nModification réalisée par : {{administrator_name}} ({{administrator_email}})\n\n{{project_name}}",
            ],
            'tree_planted' => [
                'subject' => 'Plantation réalisée · {{project_name}}',
                'body' => "Bonjour {{first_name}},\n\nL’arbre proposé dans le cadre de la proposition {{proposal_id}} a été planté.\n\nCommentaire de l’administration :\n{{comment}}\n\nModification réalisée par : {{administrator_name}} ({{administrator_email}})\n\nMerci pour votre contribution,\n{{project_name}}",
            ],
            'location_moved' => [
                'subject' => 'Localisation mise à jour · {{project_name}}',
                'body' => "Bonjour {{first_name}},\n\nLa localisation de votre proposition {{proposal_id}} a été mise à jour : {{location}}.\n\nCommentaire de l’administration :\n{{comment}}\n\nModification réalisée par : {{administrator_name}} ({{administrator_email}})\n\n{{project_name}}",
            ],
        ],
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
        'session_key' => 'plantons_user_id',
        'bootstrap_admin_email' => strtolower((string) (getenv('PLANTONS_BOOTSTRAP_ADMIN_EMAIL') ?: '')),
        'administrator_emails' => ['contact@sebastienmeunier.info'],
        'bootstrap_super_admin_email' => strtolower((string) (getenv('PLANTONS_BOOTSTRAP_SUPER_ADMIN_EMAIL') ?: '')),
        'roles' => ['contributeur', 'administrateur', 'super_administrateur'],
    ],
    'smtp' => [
        // Identifiants sensibles : définir PLANTONS_SMTP_PASSWORD dans
        // l’hébergement, jamais dans le dépôt Git.
        'host' => getenv('PLANTONS_SMTP_HOST') ?: 'ssl0.ovh.net',
        'port' => (int) (getenv('PLANTONS_SMTP_PORT') ?: 465),
        'encryption' => getenv('PLANTONS_SMTP_ENCRYPTION') ?: 'ssl',
        'username' => getenv('PLANTONS_SMTP_USERNAME') ?: 'contact@sebastienmeunier.info',
        // PLANARBRE_SMTP_PASSWORD est accepté pour les installations déjà configurées.
        'password' => getenv('PLANTONS_SMTP_PASSWORD') ?: (getenv('PLANARBRE_SMTP_PASSWORD') ?: ''),
        'from_email' => getenv('PLANTONS_SMTP_FROM_EMAIL') ?: 'contact@sebastienmeunier.info',
        'from_name' => getenv('PLANTONS_SMTP_FROM_NAME') ?: $projectName,
    ],
    'map' => [
        'center' => $territoryCenter,
        'default_zoom' => 12,
        'min_zoom' => 10,
        'max_zoom' => 20,
    ],
];
