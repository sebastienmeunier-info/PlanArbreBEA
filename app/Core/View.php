<?php

declare(strict_types=1);

namespace PlanArbreBEA\Core;

use RuntimeException;

final class View
{
    public function __construct(private readonly string $directory) {}

    public function render(string $template, array $data = []): string
    {
        $file = $this->directory . DIRECTORY_SEPARATOR . $template . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("Vue introuvable : {$template}");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }
}
