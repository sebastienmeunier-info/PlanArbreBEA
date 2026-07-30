<?php

declare(strict_types=1);

use PlanArbreBEA\Core\Application;
use PlanArbreBEA\Core\Autoloader;
use PlanArbreBEA\Core\ErrorHandler;
use PlanArbreBEA\Core\Request;

define('PLANARBRE_ROOT', __DIR__);

require PLANARBRE_ROOT . '/app/Core/Autoloader.php';

Autoloader::register(PLANARBRE_ROOT . '/app');

$config = require PLANARBRE_ROOT . '/config/config.php';
(new ErrorHandler($config))->register();

$app = new Application($config);
$app->router()->load(require PLANARBRE_ROOT . '/config/routes.php');
$app->run(Request::fromGlobals());
