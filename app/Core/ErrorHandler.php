<?php

declare(strict_types=1);

namespace PlanArbreBEA\Core;

use Throwable;

final class ErrorHandler
{
    public function __construct(private readonly array $config) {}

    public function register(): void
    {
        set_exception_handler(function (Throwable $exception): never {
            error_log((string) $exception);
            $debug = $this->config['app']['debug'];
            $message = $debug ? htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8') : 'Une erreur interne est survenue.';
            Response::html('<!doctype html><html lang="fr"><meta charset="utf-8"><title>Erreur</title><body><h1>Erreur</h1><p>' . $message . '</p></body></html>', 500);
        });
    }
}
