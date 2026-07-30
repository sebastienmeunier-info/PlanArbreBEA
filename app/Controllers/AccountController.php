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
        $allowedTabs = $isAdministrator ? ['profil', 'plantations', 'dons', 'utilisateurs'] : ['profil', 'plantations'];
        if (!in_array($tab, $allowedTabs, true)) { $tab = 'profil'; }

        $store = new GeoJsonStore();
        $sources = $this->app->config('data_sources');
        $isDonationTab = $tab === 'dons';
        $allFeatures = $store->read($sources[$isDonationTab ? 'donations' : 'proposals']['file'])['features'];
        $features = array_values(array_filter($allFeatures, static fn(array $feature): bool => ($feature['properties']['email'] ?? '') === $user['email']));
        $displayFeatures = $isAdministrator ? $allFeatures : $features;
        $counts = ['proposed' => 0, 'validated' => 0, 'rejected' => 0, 'planted' => 0];
        foreach ($displayFeatures as $feature) {
            $status = $feature['properties']['status'] ?? 'a_valider';
            if ($status === 'validee') { $counts['validated']++; }
            elseif (in_array($status, ['arbre_plante', 'realisee'], true)) { $counts['planted']++; }
            elseif (in_array($status, ['refusee', 'rejetee'], true)) { $counts['rejected']++; }
            else { $counts['proposed']++; }
        }

        Response::html($this->app->view()->render('account/index', [
            'application' => $this->app->config('app'), 'csrfToken' => $auth->csrfToken(), 'user' => $user,
            'tab' => $tab, 'isAdministrator' => $isAdministrator, 'counts' => $counts, 'features' => $features,
            'displayFeatures' => $displayFeatures, 'statuses' => $this->app->config('proposals')['status_labels'],
            'planting' => $this->app->config('planting'), 'isDonationTab' => $isDonationTab,
            'notice' => match ($request->query('invite')) {
                'sent' => 'Le compte a été créé et l’invitation a été envoyée.',
                'failed' => 'Le compte est créé, mais l’invitation n’a pas pu être envoyée. Vérifiez la configuration SMTP puis renvoyez l’invitation.',
                default => null,
            },
            'users' => $isAdministrator ? (new UserRepository($this->app->config('auth')['users_file']))->all() : [],
        ]));
    }

    public function update(Request $request): never
    {
        $auth = $this->auth(); $user = $auth->currentUser();
        if (!$user || !$auth->verifyCsrf((string) $request->input('csrf_token'))) { Response::html('Accès refusé.', 403); }
        $first = trim((string) $request->input('first_name')); $last = trim((string) $request->input('last_name'));
        $email = mb_strtolower(trim((string) $request->input('email')));
        $address = mb_substr(trim((string) $request->input('address')), 0, 255);
        $phone = $this->phone($request->input('phone'));
        $password = (string) $request->input('password');
        if ($first === '' || $last === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) { Response::html('Informations personnelles invalides.', 422); }
        if ($password !== '' && strlen($password) < 12) { Response::html('Le mot de passe doit contenir au moins 12 caractères.', 422); }
        $repository = new UserRepository($this->app->config('auth')['users_file']); $existing = $repository->findByEmail($email);
        if ($existing !== null && $existing['id'] !== $user['id']) { Response::html('Cette adresse e-mail est déjà utilisée.', 422); }
        $changes = ['first_name' => $first, 'last_name' => $last, 'email' => $email, 'address' => $address, 'phone' => $phone];
        if ($password !== '') { $changes['password_hash'] = password_hash($password, PASSWORD_DEFAULT); }
        $repository->update($user['id'], $changes);
        header('Location: /mon-compte?onglet=profil', true, 303); exit;
    }

    private function auth(): AuthService
    {
        return new AuthService(new UserRepository($this->app->config('auth')['users_file']), $this->app->config('auth'));
    }
    private function phone(mixed $value): string
    {
        $phone = mb_substr(trim((string) $value), 0, 30);
        if ($phone !== '' && preg_match('/^[0-9+().\-\s]+$/', $phone) !== 1) { Response::html('Numéro de téléphone invalide.', 422); }
        return $phone;
    }
}
