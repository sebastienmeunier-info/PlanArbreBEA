<?php

declare(strict_types=1);

namespace Plantons\Controllers;

use InvalidArgumentException;
use Plantons\Core\Application;
use Plantons\Core\Request;
use Plantons\Core\Response;
use Plantons\Repositories\UserRepository;
use Plantons\Services\AuthService;
use Plantons\Services\NotificationService;
use RuntimeException;

final class AdminUserController
{
    public function __construct(private readonly Application $app) {}

    public function index(Request $request): never
    {
        $this->guard($this->auth());
        header('Location: ' . $this->app->routeUrl('/mon-compte?onglet=utilisateurs'), true, 303);
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
            $address = mb_substr(trim((string) $request->input('address')), 0, 255);
            $phone = $this->phone($request->input('phone'));
            $notificationConsentAt = date(DATE_ATOM);
            if ($firstName === '' || $lastName === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) { throw new InvalidArgumentException('Nom, prénom et e-mail valides sont requis.'); }
            $role = $this->requestedRole($current = $this->guard($auth), 'contributeur');
            $repository = $this->repository();
            $user = $repository->findByEmail($email);
            if ($user !== null && $user['password_hash'] !== null) { throw new InvalidArgumentException('Cette adresse e-mail est déjà utilisée.'); }
            if ($user === null) {
                $user = ['id' => bin2hex(random_bytes(16)), 'first_name' => mb_substr($firstName, 0, 80), 'last_name' => mb_substr($lastName, 0, 80), 'email' => $email, 'address' => $address, 'phone' => $phone, 'password_hash' => null, 'role' => $role, 'created_at' => date(DATE_ATOM), 'notification_consent_at' => $notificationConsentAt];
                $repository->create($user);
            } else {
                $repository->update($user['id'], ['first_name' => mb_substr($firstName, 0, 80), 'last_name' => mb_substr($lastName, 0, 80), 'address' => $address, 'phone' => $phone, 'role' => $role, 'notification_consent_at' => $notificationConsentAt]);
                $user = $repository->findById($user['id']);
            }
            $token = bin2hex(random_bytes(32));
            $this->storeInvitationToken($email, $token);
            $this->sendInvitation($user, $token);
            header('Location: ' . $this->app->routeUrl('/mon-compte?onglet=utilisateurs&invite=sent'), true, 303);
            exit;
        } catch (InvalidArgumentException $exception) {
            Response::html(htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8'), 422);
        } catch (RuntimeException $exception) {
            $this->app->logger()->warning($exception->getMessage());
            header('Location: ' . $this->app->routeUrl('/mon-compte?onglet=utilisateurs&invite=failed'), true, 303);
            exit;
        }
    }

    public function changeRole(Request $request): never
    {
        $auth = $this->auth(); $current = $this->guard($auth);
        if (!$auth->verifyCsrf((string) $request->input('csrf_token'))) { Response::html('Session expirée.', 403); }
        $target = $this->repository()->findById((string) $request->input('user_id')); $role = (string) $request->input('role');
        if ($target === null || !$this->mayChangeRole($current, $target, $role)) { Response::html('Cette modification de rôle n’est pas autorisée.', 403); }
        $this->repository()->update($target['id'], ['role' => $role]);
        header('Location: ' . $this->app->routeUrl('/mon-compte?onglet=utilisateurs'), true, 303); exit;
    }

    public function delete(Request $request): never
    {
        $auth = $this->auth();
        $current = $this->guard($auth);
        if (!$auth->verifyCsrf((string) $request->input('csrf_token'))) {
            Response::html('Session expirée.', 403);
        }
        $target = $this->repository()->findById((string) $request->input('user_id'));
        if ($target === null || !$this->mayDelete($current, $target)) {
            Response::html('Cette suppression n’est pas autorisée.', 403);
        }
        $this->repository()->delete($target['id']);
        header('Location: ' . $this->app->routeUrl('/mon-compte?onglet=utilisateurs'), true, 303);
        exit;
    }

    public function updateProfile(Request $request): never
    {
        try {
            $auth = $this->auth(); $current = $this->guard($auth);
            if (!$auth->verifyCsrf((string) $request->input('csrf_token'))) { Response::html('Session expirée.', 403); }
            $repository = $this->repository(); $target = $repository->findById((string) $request->input('user_id'));
            $firstName = trim((string) $request->input('first_name')); $lastName = trim((string) $request->input('last_name')); $email = mb_strtolower(trim((string) $request->input('email'))); $address = mb_substr(trim((string) $request->input('address')), 0, 255); $phone = $this->phone($request->input('phone'));
            if ($target === null || $firstName === '' || $lastName === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) { throw new InvalidArgumentException('Informations utilisateur invalides.'); }
            $existing = $repository->findByEmail($email);
            if ($existing !== null && $existing['id'] !== $target['id']) { throw new InvalidArgumentException('Cette adresse e-mail est déjà utilisée.'); }
            $role = $this->requestedRole($current, $target['role']);
            if (!$this->mayEditRole($current, $target, $role)) { Response::html('Cette modification de rôle n’est pas autorisée.', 403); }
            $repository->update($target['id'], ['first_name' => mb_substr($firstName, 0, 80), 'last_name' => mb_substr($lastName, 0, 80), 'email' => $email, 'address' => $address, 'phone' => $phone, 'role' => $role]);
            header('Location: ' . $this->app->routeUrl('/mon-compte?onglet=utilisateurs'), true, 303); exit;
        } catch (InvalidArgumentException $exception) { Response::html(htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8'), 422); }
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
        $url = $this->app->absoluteRouteUrl('/reinitialiser-mot-de-passe?token=' . rawurlencode($token));
        if (!$this->notifier()->send('account_invitation', $user['email'], [
            'project_name' => (string) $this->app->config('app')['name'],
            'first_name' => (string) $user['first_name'],
            'last_name' => (string) $user['last_name'],
            'url' => $url,
        ])) {
            throw new RuntimeException('Le courriel d’invitation n’a pas pu être envoyé. Consultez logs/application.log.');
        }
    }

    private function mayChangeRole(array $actor, array $target, string $next): bool
    {
        if ($this->isPrimarySuperAdministrator($target)) { return false; }
        if ($this->isPrimarySuperAdministrator($actor)) { return in_array($next, ['contributeur', 'administrateur', 'super_administrateur'], true); }
        if ($target['role'] === 'super_administrateur') { return false; }
        if ($actor['role'] === 'administrateur') { return $target['role'] === 'contributeur' && $next === 'administrateur'; }
        return $actor['role'] === 'super_administrateur' && $target['role'] === 'administrateur' && in_array($next, ['contributeur', 'super_administrateur'], true);
    }

    private function mayEditRole(array $actor, array $target, string $next): bool
    {
        if ($this->isPrimarySuperAdministrator($target)) { return false; }
        if ($this->isPrimarySuperAdministrator($actor)) { return in_array($next, ['contributeur', 'administrateur', 'super_administrateur'], true); }
        if ($target['role'] === 'super_administrateur') { return $actor['role'] === 'super_administrateur' && $next === 'super_administrateur'; }
        if ($actor['role'] === 'administrateur') { return in_array($target['role'], ['contributeur', 'administrateur'], true) && in_array($next, ['contributeur', 'administrateur'], true); }
        return $actor['role'] === 'super_administrateur' && !( $target['role'] === 'contributeur' && $next === 'super_administrateur');
    }

    private function mayDelete(array $actor, array $target): bool
    {
        if ($this->isPrimarySuperAdministrator($target)) { return false; }
        if ($this->isPrimarySuperAdministrator($actor)) { return true; }
        return $actor['role'] === 'super_administrateur' && in_array($target['role'], ['contributeur', 'administrateur'], true);
    }

    private function isPrimarySuperAdministrator(array $user): bool
    {
        $primary = $this->repository()->primarySuperAdministrator();
        return $primary !== null && hash_equals((string) $primary['id'], (string) ($user['id'] ?? ''));
    }

    private function requestedRole(array $current, string $fallback): string
    {
        $role = (string) ($_POST['role'] ?? '');
        if ($current['role'] === 'administrateur') { return $role === 'administrateur' ? 'administrateur' : 'contributeur'; }
        if ($role === '') { return $fallback; }
        return in_array($role, ['contributeur', 'administrateur', 'super_administrateur'], true) ? $role : $fallback;
    }

    private function phone(mixed $value): string
    {
        $phone = mb_substr(trim((string) $value), 0, 30);
        if ($phone !== '' && preg_match('/^[0-9+().\-\s]+$/', $phone) !== 1) { throw new InvalidArgumentException('Numéro de téléphone invalide.'); }
        return $phone;
    }

    private function guard(AuthService $auth): array
    {
        $user = $auth->currentUser();
        if (!in_array($user['role'] ?? null, ['administrateur', 'super_administrateur'], true)) { Response::html('Accès réservé aux administrateurs.', 403); }
        return $user;
    }

    private function repository(): UserRepository { return new UserRepository($this->app->config('auth')['users_file']); }
    private function auth(): AuthService { return new AuthService($this->repository(), $this->app->config('auth')); }
    private function notifier(): NotificationService { return new NotificationService($this->app->config('smtp'), $this->app->config('notifications'), $this->app->logger()); }
}
