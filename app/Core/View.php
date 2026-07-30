<?php

declare(strict_types=1);

namespace Plantons\Core;

use RuntimeException;

final class View
{
    public function __construct(
        private readonly string $directory,
        private readonly string $basePath = '',
        private readonly string $routingMode = 'query',
    ) {}

    public function render(string $template, array $data = []): string
    {
        $file = $this->directory . DIRECTORY_SEPARATOR . $template . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("Vue introuvable : {$template}");
        }

        $basePath = rtrim($this->basePath, '/');
        $url = static function (string $path = '/') use ($basePath): string {
            if (preg_match('#^https?://#i', $path)) {
                return $path;
            }

            return $basePath . '/' . ltrim($path, '/');
        };
        $routeUrl = static function (string $path = '/') use ($url): string {
            if (preg_match('#^https?://#i', $path)) {
                return $path;
            }

            $parts = parse_url($path);
            $route = $parts['path'] ?? '/';
            if ($route === '/') {
                return $url('/');
            }

            $parameters = ['route' => $route];
            if (isset($parts['query'])) {
                parse_str($parts['query'], $query);
                $parameters += $query;
            }

            return $url('/index.php') . '?' . http_build_query($parameters);
        };
        if ($this->routingMode !== 'query') {
            $routeUrl = $url;
        }
        $data += ['basePath' => $basePath, 'url' => $url, 'routeUrl' => $routeUrl];
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }
}
