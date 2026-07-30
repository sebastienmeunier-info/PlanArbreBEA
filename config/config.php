<?php

declare(strict_types=1);

use PlanArbreBEA\Core\Logger;

$root = dirname(__DIR__);
$environment = getenv('PLANARBRE_ENV') ?: 'development';

return [
    'app' => [
        'name' => 'PlanArbreBEA',
        'environment' => $environment,
        'debug' => filter_var(getenv('PLANARBRE_DEBUG') ?: $environment !== 'production', FILTER_VALIDATE_BOOL),
        'base_url' => rtrim((string) (getenv('PLANARBRE_BASE_URL') ?: ''), '/'),
        'timezone' => getenv('PLANARBRE_TIMEZONE') ?: 'Europe/Paris',
        'version' => trim((string) file_get_contents($root . '/VERSION')),
    ],
    'paths' => [
        'root' => $root,
        'data' => $root . '/data',
        'logs' => $root . '/logs',
        'uploads' => $root . '/uploads',
        'views' => $root . '/resources/templates',
        'public' => $root . '/public',
    ],
    'logging' => [
        'file' => $root . '/logs/application.log',
        'minimum_level' => $environment === 'production' ? Logger::WARNING : Logger::DEBUG,
    ],
    'security' => [
        'csrf_token_name' => 'csrf_token',
        'max_upload_size' => 1_048_576,
        'allowed_photo_mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
    ],
    'map' => [
        'center' => [47.5310, -0.1020],
        'default_zoom' => 12,
        'min_zoom' => 10,
        'max_zoom' => 20,
    ],
];
