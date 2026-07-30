<?php

declare(strict_types=1);

namespace PlanArbreBEA\Controllers;

use InvalidArgumentException;
use PlanArbreBEA\Core\Application;
use PlanArbreBEA\Core\Request;
use PlanArbreBEA\Core\Response;
use PlanArbreBEA\Repositories\UserRepository;
use PlanArbreBEA\Services\AuthService;
use PlanArbreBEA\Services\GeoJsonStore;
use PlanArbreBEA\Services\TerritoryService;
use RuntimeException;

final class AdminProposalController
{
    public function __construct(private readonly Application $app) {}

    public function index(Request $request): never
    {
        $auth = $this->auth();
        $this->guard($auth);
        $sources = $this->app->config('data_sources');
        $features = (new GeoJsonStore())->read($sources['proposals']['file'])['features'];
        Response::html($this->app->view()->render('admin/proposals', [
            'application' => $this->app->config('app'),
            'csrfToken' => $auth->csrfToken(),
            'user' => $auth->currentUser(),
            'features' => $features,
            'statuses' => $this->app->config('proposals')['status_labels'],
        ]));
    }

    public function update(Request $request): never
    {
        try {
            $auth = $this->auth();
            $current = $this->guard($auth);
            if (!$auth->verifyCsrf((string) $request->input('csrf_token'))) {
                Response::html('Session expirée.', 403);
            }
            $status = (string) $request->input('status');
            $statuses = $this->app->config('proposals')['status_labels'];
            if (!array_key_exists($status, $statuses)) {
                throw new InvalidArgumentException('Statut invalide.');
            }
            $longitude = $this->coordinate($request->input('longitude'));
            $latitude = $this->coordinate($request->input('latitude'));
            $store = new GeoJsonStore();
            $sources = $this->app->config('data_sources');
            $territory = $store->read($sources['territory']['file']);
            $territoryService = new TerritoryService();
            if (!$territoryService->contains($territory, $longitude, $latitude)) {
                throw new InvalidArgumentException('La localisation doit rester dans le territoire autorisé.');
            }
            $municipalities = $store->read($sources['delegated_municipalities']['file']);
            $store->updateFeature($sources['proposals']['file'], (string) $request->input('id'), [
                'status' => $status,
                'delegated_municipality' => $territoryService->municipality($municipalities, $longitude, $latitude),
                'updated_at' => date(DATE_ATOM),
                'updated_by' => $current['id'],
            ], [$longitude, $latitude]);
            header('Location: /admin/propositions', true, 303);
            exit;
        } catch (InvalidArgumentException|RuntimeException $exception) {
            Response::html(htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8'), 422);
        }
    }

    private function coordinate(mixed $value): float
    {
        if (!is_numeric($value)) { throw new InvalidArgumentException('Coordonnées invalides.'); }
        return (float) $value;
    }

    private function guard(AuthService $auth): array
    {
        $user = $auth->currentUser();
        if (!in_array($user['role'] ?? null, ['administrateur', 'super_administrateur'], true)) {
            Response::html('Accès réservé aux administrateurs.', 403);
        }
        return $user;
    }

    private function auth(): AuthService
    {
        return new AuthService(new UserRepository($this->app->config('auth')['users_file']), $this->app->config('auth'));
    }
}
