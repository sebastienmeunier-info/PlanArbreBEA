<?php

declare(strict_types=1);

namespace Plantons\Core;

use RuntimeException;

final class View
{
    public function __construct(
        private readonly string $directory,
        private readonly string $basePath = '',
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
        $data += ['basePath' => $basePath, 'url' => $url];
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }
}
