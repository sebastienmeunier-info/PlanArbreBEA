<?php

declare(strict_types=1);

namespace PlanArbreBEA\Controllers;

use PlanArbreBEA\Core\Application;
use PlanArbreBEA\Core\Request;
use PlanArbreBEA\Core\Response;
use PlanArbreBEA\Services\GeoJsonStore;
use PlanArbreBEA\Services\AuthService;
use PlanArbreBEA\Repositories\UserRepository;

final class HomeController
{
    public function __construct(private readonly Application $app) {}

    public function index(Request $request): never
    {
        $auth = new AuthService(new UserRepository($this->app->config('auth')['users_file']), $this->app->config('auth'));
        $user = $auth->currentUser();
        $planting = $this->app->config('planting');
        $proposalFeatures = (new GeoJsonStore())->read($this->app->config('data_sources')['proposals']['file'])['features'];
        $statistics = ['proposed' => 0, 'validated' => 0, 'planted' => 0];
        foreach ($proposalFeatures as $proposal) {
            $status = $proposal['properties']['status'] ?? 'a_valider';
            if ($status === 'validee') { $statistics['validated']++; }
            elseif (in_array($status, ['arbre_plante', 'realisee'], true)) { $statistics['planted']++; }
            else { $statistics['proposed']++; }
        }

        Response::html($this->app->view()->render('home', [
            'application' => $this->app->config('app'),
            'territory' => $this->app->config('territory'),
            'planting' => $planting,
            'statistics' => $statistics,
            'security' => $this->app->config('security'),
            'map' => $this->app->config('map'),
            'proposals' => $this->app->config('proposals'),
            'dataSources' => $this->app->config('data_sources'),
            'csrfToken' => $auth->csrfToken(),
            'user' => $user,
        ]));
    }

    public function health(Request $request): never
    {
        Response::json(['status' => 'ok', 'version' => $this->app->config('app')['version']]);
    }
}
