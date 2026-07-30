<?php

declare(strict_types=1);

namespace PlanArbreBEA\Controllers;

use PlanArbreBEA\Core\Application;
use PlanArbreBEA\Core\Request;
use PlanArbreBEA\Core\Response;

final class HomeController
{
    public function __construct(private readonly Application $app) {}

    public function index(Request $request): never
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));

        Response::html($this->app->view()->render('home', [
            'application' => $this->app->config('app'),
            'territory' => $this->app->config('territory'),
            'planting' => $this->app->config('planting'),
            'security' => $this->app->config('security'),
            'map' => $this->app->config('map'),
            'dataSources' => $this->app->config('data_sources'),
            'csrfToken' => $_SESSION['csrf_token'],
        ]));
    }

    public function health(Request $request): never
    {
        Response::json(['status' => 'ok', 'version' => $this->app->config('app')['version']]);
    }
}
