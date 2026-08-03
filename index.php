<?php

declare(strict_types=1);

use Plantons\Core\Application;
use Plantons\Core\Autoloader;
use Plantons\Core\ErrorHandler;
use Plantons\Core\Request;

define('PLANTONS_ROOT', __DIR__);

require PLANTONS_ROOT . '/app/Core/Autoloader.php';

Autoloader::register(PLANTONS_ROOT . '/app');

$config = require PLANTONS_ROOT . '/config/config.php';
(new ErrorHandler($config))->register();

$app = new Application($config);
$app->router()->load(require PLANTONS_ROOT . '/config/routes.php');
$app->run(Request::fromGlobals());
