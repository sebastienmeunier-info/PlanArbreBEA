<?php

declare(strict_types=1);

namespace PlanArbreBEA\Controllers;

use PlanArbreBEA\Core\Application;
use PlanArbreBEA\Core\Request;
use PlanArbreBEA\Core\Response;
use PlanArbreBEA\Repositories\UserRepository;
use PlanArbreBEA\Services\AuthService;

final class AdminUserController
{
    public function __construct(private readonly Application $app) {}

    public function index(Request $request): never
    {
        $auth = $this->auth();
        $this->guard($auth);
        Response::html($this->app->view()->render('admin/users', [
            'users' => $this->repository()->all(),
            'csrfToken' => $auth->csrfToken(),
            'user' => $auth->currentUser(),
            'application' => $this->app->config('app'),
        ]));
    }

    public function changeRole(Request $request): never
    {
        $auth = $this->auth();
        $current = $this->guard($auth);
        if (!$auth->verifyCsrf((string) $request->input('csrf_token'))) {
            Response::html('Session expirée.', 403);
        }

        $target = $this->repository()->findById((string) $request->input('user_id'));
        $role = (string) $request->input('role');
        if ($target === null || !$this->mayChangeRole($current['role'], $target['role'], $role)) {
            Response::html('Cette modification de rôle n’est pas autorisée.', 403);
        }

        $this->repository()->update($target['id'], ['role' => $role]);
        header('Location: /admin/utilisateurs', true, 303);
        exit;
    }

    private function mayChangeRole(string $actor, string $target, string $next): bool
    {
        if ($target === 'super_administrateur') {
            return false;
        }
        if ($actor === 'administrateur') {
            return $target === 'contributeur' && $next === 'administrateur';
        }
        return $actor === 'super_administrateur'
            && $target === 'administrateur'
            && in_array($next, ['contributeur', 'super_administrateur'], true);
    }

    private function guard(AuthService $auth): array
    {
        $user = $auth->currentUser();
        if (!in_array($user['role'] ?? null, ['administrateur', 'super_administrateur'], true)) {
            Response::html('Accès réservé aux administrateurs.', 403);
        }
        return $user;
    }

    private function repository(): UserRepository
    {
        return new UserRepository($this->app->config('auth')['users_file']);
    }

    private function auth(): AuthService
    {
        return new AuthService($this->repository(), $this->app->config('auth'));
    }
}
