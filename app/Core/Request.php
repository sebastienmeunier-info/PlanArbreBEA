<?php

declare(strict_types=1);

namespace Plantons\Core;

final class Request
{
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query = [],
        private readonly array $body = [],
        private readonly array $files = [],
    ) {
    }

    public static function fromGlobals(): self
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $requestedRoute = $_GET['route'] ?? null;
        $path = is_string($requestedRoute) && str_starts_with($requestedRoute, '/')
            ? $requestedRoute
            : (parse_url($uri, PHP_URL_PATH) ?: '/');
        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        // The PHP development server reports the requested virtual path as
        // SCRIPT_NAME for routes below an existing directory (for example /admin/…).
        // Only a real PHP front controller may define an installation prefix.
        $scriptName = str_ends_with($script, '.php') ? dirname($script) : '/';

        if ($scriptName !== '/' && $scriptName !== '.' && str_starts_with($path, $scriptName)) {
            $path = substr($path, strlen($scriptName)) ?: '/';
        }

        return new self(
            strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            '/' . ltrim(rawurldecode($path), '/'),
            $_GET,
            $_POST,
            $_FILES,
        );
    }

    public function method(): string { return $this->method; }
    public function path(): string { return $this->path; }
    public function query(string $key, mixed $default = null): mixed { return $this->query[$key] ?? $default; }
    public function input(string $key, mixed $default = null): mixed { return $this->body[$key] ?? $default; }
    public function files(string $key): ?array { return $this->files[$key] ?? null; }
}
