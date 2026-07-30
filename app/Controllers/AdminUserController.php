<?php

declare(strict_types=1);

namespace PlanArbreBEA\Controllers;

use InvalidArgumentException;
use PlanArbreBEA\Core\Application;
use PlanArbreBEA\Core\Request;
use PlanArbreBEA\Core\Response;
use PlanArbreBEA\Repositories\UserRepository;
use PlanArbreBEA\Services\AuthService;
use PlanArbreBEA\Services\SmtpMailer;
use RuntimeException;

final class AdminUserController
{
    public function __construct(private readonly Application $app) {}

    public function index(Request $request): never
    {
        $this->guard($this->auth());
        header('Location: /mon-compte?onglet=utilisateurs', true, 303);
        exit;
    }

    public function create(Request $request): never
    {
        try {
            $auth = $this->auth();
            $this->guard($auth);
            if (!$auth->verifyCsrf((string) $request->input('csrf_token'))) { Response::html('Session expirée.', 403); }
            $firstName = trim((string) $request->input('first_name'));
            $lastName = trim((string) $request->input('last_name'));
            $email = mb_strtolower(trim((string) $request->input('email')));
            if ($firstName === '' || $lastName === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) { throw new InvalidArgumentException('Nom, prénom et e-mail valides sont requis.'); }
            $repository = $this->repository();
            $user = $repository->findByEmail($email);
            if ($user !== null && $user['password_hash'] !== null) { throw new InvalidArgumentException('Cette adresse e-mail est déjà utilisée.'); }
            if ($user === null) {
                $user = ['id' => bin2hex(random_bytes(16)), 'first_name' => mb_substr($firstName, 0, 80), 'last_name' => mb_substr($lastName, 0, 80), 'email' => $email, 'password_hash' => null, 'role' => 'contributeur', 'created_at' => date(DATE_ATOM)];
                $repository->create($user);
            }
            $token = bin2hex(random_bytes(32));
            $this->storeInvitationToken($email, $token);
            $this->sendInvitation($user, $token);
            header('Location: /mon-compte?onglet=utilisateurs&invite=sent', true, 303);
            exit;
        } catch (InvalidArgumentException $exception) {
            Response::html(htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8'), 422);
        } catch (RuntimeException $exception) {
            $this->app->logger()->warning($exception->getMessage());
            header('Location: /mon-compte?onglet=utilisateurs&invite=failed', true, 303);
            exit;
        }
    }

    public function changeRole(Request $request): never
    {
        $auth = $this->auth(); $current = $this->guard($auth);
        if (!$auth->verifyCsrf((string) $request->input('csrf_token'))) { Response::html('Session expirée.', 403); }
        $target = $this->repository()->findById((string) $request->input('user_id')); $role = (string) $request->input('role');
        if ($target === null || !$this->mayChangeRole($current['role'], $target['role'], $role)) { Response::html('Cette modification de rôle n’est pas autorisée.', 403); }
        $this->repository()->update($target['id'], ['role' => $role]);
        header('Location: /mon-compte?onglet=utilisateurs', true, 303); exit;
    }

    private function storeInvitationToken(string $email, string $token): void
    {
        $file = $this->app->config('auth')['password_resets_file'];
        $resets = is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];
        $resets = array_values(array_filter($resets, static fn(array $reset): bool => ($reset['email'] ?? '') !== $email));
        $resets[] = ['email' => $email, 'token_hash' => password_hash($token, PASSWORD_DEFAULT), 'expires_at' => time() + 7 * 24 * 3600];
        file_put_contents($file, json_encode($resets, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    private function sendInvitation(array $user, string $token): void
    {
        $baseUrl = rtrim((string) $this->app->config('app')['base_url'], '/');
        $url = $baseUrl . '/reinitialiser-mot-de-passe?token=' . rawurlencode($token);
        $subject = 'Activation de votre compte';
        $message = "Bonjour {$user['first_name']},\n\nVotre compte a été créé. Définissez votre mot de passe en suivant ce lien, valable 7 jours :\n{$url}\n";
        (new SmtpMailer($this->app->config('smtp')))->send($user['email'], $subject, $message);
    }

    private function mayChangeRole(string $actor, string $target, string $next): bool
    {
        if ($target === 'super_administrateur') { return false; }
        if ($actor === 'administrateur') { return $target === 'contributeur' && $next === 'administrateur'; }
        return $actor === 'super_administrateur' && $target === 'administrateur' && in_array($next, ['contributeur', 'super_administrateur'], true);
    }

    private function guard(AuthService $auth): array
    {
        $user = $auth->currentUser();
        if (!in_array($user['role'] ?? null, ['administrateur', 'super_administrateur'], true)) { Response::html('Accès réservé aux administrateurs.', 403); }
        return $user;
    }

    private function repository(): UserRepository { return new UserRepository($this->app->config('auth')['users_file']); }
    private function auth(): AuthService { return new AuthService($this->repository(), $this->app->config('auth')); }
}
