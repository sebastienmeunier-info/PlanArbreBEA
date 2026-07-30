<?php

declare(strict_types=1);

namespace Plantons\Services;

use Plantons\Repositories\UserRepository;

final class AuthService
{
    public function __construct(private readonly UserRepository $users, private readonly array $config) {}

    public function currentUser(): ?array
    {
        $this->startSession();
        $id = $_SESSION[$this->config['session_key']] ?? null;
        return is_string($id) ? $this->users->findById($id) : null;
    }

    public function login(string $email, string $password): ?array
    {
        $user = $this->users->findByEmail($email);
        if ($user === null || !is_string($user['password_hash'] ?? null) || !password_verify($password, $user['password_hash'])) { return null; }
        $this->startSession(); session_regenerate_id(true); $_SESSION[$this->config['session_key']] = $user['id'];
        return $user;
    }

    public function logout(): void
    {
        $this->startSession(); unset($_SESSION[$this->config['session_key']]);
    }

    public function csrfToken(): string
    {
        $this->startSession(); return $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
    }

    public function verifyCsrf(string $token): bool
    {
        $this->startSession(); return $token !== '' && hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token);
    }

    private function startSession(): void { if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); } }
}
