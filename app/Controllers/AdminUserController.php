<?php
declare(strict_types=1);
namespace PlanArbreBEA\Controllers;
use PlanArbreBEA\Core\Application; use PlanArbreBEA\Core\Request; use PlanArbreBEA\Core\Response; use PlanArbreBEA\Repositories\UserRepository; use PlanArbreBEA\Services\AuthService;
final class AdminUserController {
    public function __construct(private readonly Application $app) {}
    public function index(Request $request): never { $auth = $this->auth(); $this->guard($auth); Response::html($this->app->view()->render('admin/users', ['users' => $this->repository()->all(), 'csrfToken' => $auth->csrfToken(), 'application' => $this->app->config('app')])); }
    public function promote(Request $request): never { $auth = $this->auth(); $this->guard($auth); if (!$auth->verifyCsrf((string) $request->input('csrf_token'))) { Response::html('Session expirée.', 403); } $this->repository()->update((string) $request->input('user_id'), ['role' => 'administrateur']); header('Location: /admin/utilisateurs', true, 303); exit; }
    private function guard(AuthService $auth): void { if (($auth->currentUser()['role'] ?? null) !== 'administrateur') { Response::html('Accès réservé aux administrateurs.', 403); } }
    private function repository(): UserRepository { return new UserRepository($this->app->config('auth')['users_file']); }
    private function auth(): AuthService { return new AuthService($this->repository(), $this->app->config('auth')); }
}
