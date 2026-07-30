<?php

declare(strict_types=1);

namespace PlanArbreBEA\Core;

final class Request
{
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query = [],
        private readonly array $body = [],
    ) {
    }

    public static function fromGlobals(): self
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $scriptName = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')));

        if ($scriptName !== '/' && $scriptName !== '.' && str_starts_with($path, $scriptName)) {
            $path = substr($path, strlen($scriptName)) ?: '/';
        }

        return new self(
            strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            '/' . ltrim(rawurldecode($path), '/'),
            $_GET,
            $_POST,
        );
    }

    public function method(): string { return $this->method; }
    public function path(): string { return $this->path; }
    public function query(string $key, mixed $default = null): mixed { return $this->query[$key] ?? $default; }
    public function input(string $key, mixed $default = null): mixed { return $this->body[$key] ?? $default; }
}
