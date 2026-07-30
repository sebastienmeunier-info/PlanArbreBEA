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
        Response::html($this->app->view()->render('home', [
            'application' => $this->app->config('app'),
        ]));
    }

    public function health(Request $request): never
    {
        Response::json(['status' => 'ok', 'version' => $this->app->config('app')['version']]);
    }
}
