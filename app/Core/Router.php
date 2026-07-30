<?php

declare(strict_types=1);

namespace PlanArbreBEA\Core;

use RuntimeException;

final class Router
{
    private array $routes = [];

    public function load(array $routes): void
    {
        foreach ($routes as [$method, $path, $handler]) {
            $this->add($method, $path, $handler);
        }
    }

    public function add(string $method, string $path, array $handler): void
    {
        $this->routes[strtoupper($method)][$this->normalize($path)] = $handler;
    }

    public function dispatch(Request $request, Application $application): never
    {
        $handler = $this->routes[$request->method()][$this->normalize($request->path())] ?? null;
        if ($handler === null) {
            Response::html($application->view()->render('errors/404', ['title' => 'Page introuvable']), 404);
        }

        [$class, $method] = $handler;
        $controller = new $class($application);
        if (!method_exists($controller, $method)) {
            throw new RuntimeException('Gestionnaire de route invalide.');
        }

        $controller->{$method}($request);
        throw new RuntimeException('Un contrôleur doit envoyer une réponse.');
    }

    private function normalize(string $path): string
    {
        return '/' . trim($path, '/') ?: '/';
    }
}
