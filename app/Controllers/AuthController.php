<?php

declare(strict_types=1);

namespace PlanArbreBEA\Controllers;

use InvalidArgumentException;
use PlanArbreBEA\Core\Application;
use PlanArbreBEA\Core\Request;
use PlanArbreBEA\Core\Response;
use PlanArbreBEA\Repositories\UserRepository;
use PlanArbreBEA\Services\AuthService;

final class AuthController
{
    public function __construct(private readonly Application $app) {}
    public function registerForm(Request $request): never { $this->page('auth/register'); }
    public function loginForm(Request $request): never { $this->page('auth/login'); }
    public function forgotForm(Request $request): never { $this->page('auth/forgot'); }
    public function resetForm(Request $request): never { $this->page('auth/reset', null, 200, ['token' => (string) $request->query('token')]); }

    public function register(Request $request): never
    {
        try {
            $this->csrf($request); $firstName = trim((string) $request->input('first_name')); $lastName = trim((string) $request->input('last_name'));
            $email = mb_strtolower(trim((string) $request->input('email'))); $password = (string) $request->input('password');
            $address = mb_substr(trim((string) $request->input('address')), 0, 255); $phone = $this->phone($request->input('phone'));
            if ($firstName === '' || $lastName === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false || strlen($password) < 12) { throw new InvalidArgumentException('Renseignez vos nom, prénom, e-mail et un mot de passe de 12 caractères minimum.'); }
            $repository = $this->repository(); if ($repository->findByEmail($email)) { throw new InvalidArgumentException('Cette adresse e-mail est déjà utilisée.'); }
            $authConfig = $this->app->config('auth');
            $isFirstUser = $repository->all() === [];
            $role = ($isFirstUser || ($authConfig['bootstrap_super_admin_email'] !== '' && $email === $authConfig['bootstrap_super_admin_email']))
                ? 'super_administrateur'
                : ((($authConfig['bootstrap_admin_email'] !== '' && $email === $authConfig['bootstrap_admin_email']) || in_array($email, $authConfig['administrator_emails'], true)) ? 'administrateur' : 'contributeur');
            $user = ['id' => bin2hex(random_bytes(16)), 'first_name' => mb_substr($firstName, 0, 80), 'last_name' => mb_substr($lastName, 0, 80), 'email' => $email, 'address' => $address, 'phone' => $phone, 'password_hash' => password_hash($password, PASSWORD_DEFAULT), 'role' => $role, 'created_at' => date(DATE_ATOM)];
            $repository->create($user); $this->auth()->login($email, $password); $this->redirect('/');
        } catch (InvalidArgumentException $exception) { $this->page('auth/register', $exception->getMessage(), 422); }
    }

    public function login(Request $request): never
    {
        if (!$this->auth()->verifyCsrf((string) $request->input('csrf_token')) || !$this->auth()->login((string) $request->input('email'), (string) $request->input('password'))) { $this->page('auth/login', 'Identifiants invalides.', 422); }
        $this->redirect('/');
    }

    public function logout(Request $request): never { $this->csrf($request); $this->auth()->logout(); $this->redirect('/'); }
    public function forgot(Request $request): never
    {
        $this->csrf($request); $email = mb_strtolower(trim((string) $request->input('email'))); $user = $this->repository()->findByEmail($email);
        if ($user) { $token = bin2hex(random_bytes(32)); $resets = array_filter($this->resets(), static fn(array $reset): bool => $reset['email'] !== $email); $resets[] = ['email' => $email, 'token_hash' => password_hash($token, PASSWORD_DEFAULT), 'expires_at' => time() + 3600]; $this->writeResets($resets); $url = rtrim($this->app->config('app')['base_url'], '/') . '/reinitialiser-mot-de-passe?token=' . $token; if ($this->app->config('auth')['mail_enabled']) { @mail($email, 'Réinitialisation de votre mot de passe', "Utilisez ce lien valable une heure : {$url}", 'From: ' . $this->app->config('auth')['mail_from']); } else { $this->app->logger()->info('Lien de réinitialisation généré.', ['email' => $email, 'url' => $url]); } }
        $this->page('auth/forgot', 'Si cette adresse existe, un lien de réinitialisation lui a été envoyé.');
    }
    public function reset(Request $request): never
    {
        try { $this->csrf($request); $token = (string) $request->input('token'); $password = (string) $request->input('password'); if (strlen($password) < 12) { throw new InvalidArgumentException('Le mot de passe doit contenir 12 caractères minimum.'); } foreach ($this->resets() as $reset) { if ($reset['expires_at'] >= time() && password_verify($token, $reset['token_hash'])) { $this->repository()->update($this->repository()->findByEmail($reset['email'])['id'], ['password_hash' => password_hash($password, PASSWORD_DEFAULT)]); $this->writeResets(array_values(array_filter($this->resets(), static fn(array $item): bool => $item !== $reset))); $this->redirect('/connexion'); } } throw new InvalidArgumentException('Ce lien est invalide ou expiré.'); } catch (InvalidArgumentException $exception) { $this->page('auth/reset', $exception->getMessage(), 422, ['token' => (string) $request->input('token')]); }
    }

    private function page(string $template, ?string $message = null, int $status = 200, array $extra = []): never
    {
        Response::html($this->app->view()->render($template, array_replace(['application' => $this->app->config('app'), 'csrfToken' => $this->auth()->csrfToken(), 'user' => $this->auth()->currentUser(), 'message' => $message], $extra)), $status);
    }
    private function auth(): AuthService { return new AuthService($this->repository(), $this->app->config('auth')); }
    private function repository(): UserRepository { return new UserRepository($this->app->config('auth')['users_file']); }
    private function csrf(Request $request): void { if (!$this->auth()->verifyCsrf((string) $request->input('csrf_token'))) { throw new InvalidArgumentException('Session expirée.'); } }
    private function phone(mixed $value): string { $phone = mb_substr(trim((string) $value), 0, 30); if ($phone !== '' && preg_match('/^[0-9+().\-\s]+$/', $phone) !== 1) { throw new InvalidArgumentException('Numéro de téléphone invalide.'); } return $phone; }
    private function resets(): array { $file = $this->app->config('auth')['password_resets_file']; return is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : []; }
    private function writeResets(array $resets): void { file_put_contents($this->app->config('auth')['password_resets_file'], json_encode($resets), LOCK_EX); }
    private function redirect(string $location): never { header('Location: ' . $location, true, 303); exit; }
}
