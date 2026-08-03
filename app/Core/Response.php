<?php

declare(strict_types=1);

namespace Plantons\Core;

final class Response
{
    public static function html(string $content, int $status = 200): never
    {
        http_response_code($status);
        self::securityHeaders();
        header('Content-Type: text/html; charset=UTF-8');
        echo $content;
        exit;
    }

    public static function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        self::securityHeaders();
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function download(string $content, string $filename, string $contentType): never
    {
        self::securityHeaders();
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');
        echo $content;
        exit;
    }

    private static function securityHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(self), camera=(), microphone=()');
        header('Cache-Control: no-store, private');
    }
}
