<?php

declare(strict_types=1);

namespace Plantons\Core;

final class Application
{
    private readonly Router $router;
    private readonly View $view;
    private readonly Logger $logger;

    public function __construct(private readonly array $config)
    {
        date_default_timezone_set($config['app']['timezone']);
        $this->ensureRuntimeDirectories();
        $this->router = new Router();
        $this->view = new View(
            $config['paths']['views'],
            $config['app']['base_path'],
            $config['app']['routing_mode'],
        );
        $this->logger = new Logger($config['logging']['file'], $config['logging']['minimum_level']);
    }

    public function run(Request $request): never { $this->router->dispatch($request, $this); }
    public function router(): Router { return $this->router; }
    public function view(): View { return $this->view; }
    public function logger(): Logger { return $this->logger; }
    public function config(string $key): mixed { return $this->config[$key] ?? null; }

    public function url(string $path = '/'): string
    {
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        return rtrim((string) $this->config['app']['base_path'], '/') . '/' . ltrim($path, '/');
    }

    public function routeUrl(string $path = '/'): string
    {
        if ($this->config['app']['routing_mode'] !== 'query' || preg_match('#^https?://#i', $path)) {
            return $this->url($path);
        }

        $parts = parse_url($path);
        $route = $parts['path'] ?? '/';
        if ($route === '/') {
            return $this->url('/');
        }

        $parameters = ['route' => $route];
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
            $parameters += $query;
        }

        return $this->url('/index.php') . '?' . http_build_query($parameters);
    }

    private function ensureRuntimeDirectories(): void
    {
        foreach (['data', 'logs', 'uploads'] as $path) {
            $directory = $this->config['paths'][$path];
            if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new \RuntimeException("Impossible de créer le répertoire {$directory}.");
            }
        }
    }
}
