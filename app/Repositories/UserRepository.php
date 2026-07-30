<?php

declare(strict_types=1);

namespace PlanArbreBEA\Repositories;

use RuntimeException;

final class UserRepository
{
    public function __construct(private readonly string $file) {}

    public function findByEmail(string $email): ?array
    {
        foreach ($this->all() as $user) {
            if (mb_strtolower($user['email']) === mb_strtolower($email)) { return $user; }
        }
        return null;
    }

    public function findById(string $id): ?array
    {
        foreach ($this->all() as $user) {
            if ($user['id'] === $id) { return $user; }
        }
        return null;
    }

    public function create(array $user): void
    {
        $users = $this->all();
        $users[] = $user;
        $this->write($users);
    }

    public function all(): array
    {
        if (!is_file($this->file) || filesize($this->file) === 0) { return []; }
        $users = json_decode((string) file_get_contents($this->file), true);
        if (!is_array($users)) { throw new RuntimeException('Le fichier des utilisateurs est invalide.'); }
        return $users;
    }

    public function update(string $id, array $changes): void
    {
        $users = $this->all();
        foreach ($users as &$user) {
            if ($user['id'] === $id) { $user = array_replace($user, $changes); $this->write($users); return; }
        }
        throw new RuntimeException('Utilisateur introuvable.');
    }

    private function write(array $users): void
    {
        $directory = dirname($this->file);
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) { throw new RuntimeException('Impossible de créer le dossier utilisateurs.'); }
        $temporary = $this->file . '.tmp';
        if (file_put_contents($temporary, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) === false || !rename($temporary, $this->file)) { throw new RuntimeException('Impossible d’enregistrer les utilisateurs.'); }
    }
}
