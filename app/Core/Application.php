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
        $this->view = new View($config['paths']['views']);
        $this->logger = new Logger($config['logging']['file'], $config['logging']['minimum_level']);
    }

    public function run(Request $request): never { $this->router->dispatch($request, $this); }
    public function router(): Router { return $this->router; }
    public function view(): View { return $this->view; }
    public function logger(): Logger { return $this->logger; }
    public function config(string $key): mixed { return $this->config[$key] ?? null; }

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
