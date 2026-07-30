<?php

declare(strict_types=1);

namespace PlanArbreBEA\Controllers;

use PlanArbreBEA\Core\Application;
use PlanArbreBEA\Core\Request;
use PlanArbreBEA\Core\Response;
use PlanArbreBEA\Repositories\UserRepository;
use PlanArbreBEA\Services\AuthService;
use PlanArbreBEA\Services\GeoJsonStore;

final class AccountController
{
    public function __construct(private readonly Application $app) {}

    public function index(Request $request): never
    {
        $auth = $this->auth();
        $user = $auth->currentUser();
        if (!$user) { header('Location: /connexion', true, 303); exit; }

        $isAdministrator = in_array($user['role'], ['administrateur', 'super_administrateur'], true);
        $tab = (string) $request->query('onglet', 'profil');
        $allowedTabs = $isAdministrator ? ['profil', 'contributions', 'propositions', 'utilisateurs'] : ['profil', 'contributions'];
        if (!in_array($tab, $allowedTabs, true)) { $tab = 'profil'; }

        $store = new GeoJsonStore();
        $sources = $this->app->config('data_sources');
        $allFeatures = $store->read($sources['proposals']['file'])['features'];
        $features = array_values(array_filter($allFeatures, static fn(array $feature): bool => ($feature['properties']['email'] ?? '') === $user['email']));
        $counts = ['proposed' => 0, 'validated' => 0, 'planted' => 0];
        foreach ($features as $feature) {
            $status = $feature['properties']['status'] ?? 'a_valider';
            if ($status === 'validee') { $counts['validated']++; }
            elseif (in_array($status, ['arbre_plante', 'realisee'], true)) { $counts['planted']++; }
            else { $counts['proposed']++; }
        }

        Response::html($this->app->view()->render('account/index', [
            'application' => $this->app->config('app'), 'csrfToken' => $auth->csrfToken(), 'user' => $user,
            'tab' => $tab, 'isAdministrator' => $isAdministrator, 'counts' => $counts, 'features' => $features,
            'adminFeatures' => $allFeatures, 'statuses' => $this->app->config('proposals')['status_labels'],
            'users' => $isAdministrator ? (new UserRepository($this->app->config('auth')['users_file']))->all() : [],
        ]));
    }

    public function update(Request $request): never
    {
        $auth = $this->auth(); $user = $auth->currentUser();
        if (!$user || !$auth->verifyCsrf((string) $request->input('csrf_token'))) { Response::html('Accès refusé.', 403); }
        $first = trim((string) $request->input('first_name')); $last = trim((string) $request->input('last_name'));
        $email = mb_strtolower(trim((string) $request->input('email')));
        if ($first === '' || $last === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) { Response::html('Informations personnelles invalides.', 422); }
        $repository = new UserRepository($this->app->config('auth')['users_file']); $existing = $repository->findByEmail($email);
        if ($existing !== null && $existing['id'] !== $user['id']) { Response::html('Cette adresse e-mail est déjà utilisée.', 422); }
        $repository->update($user['id'], ['first_name' => $first, 'last_name' => $last, 'email' => $email]);
        header('Location: /mon-compte?onglet=profil', true, 303); exit;
    }

    private function auth(): AuthService
    {
        return new AuthService(new UserRepository($this->app->config('auth')['users_file']), $this->app->config('auth'));
    }
}
