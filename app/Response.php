<?php

declare(strict_types=1);

/**
 * ------------------------------------------------------------
 * Plantons
 * Réponses HTTP / JSON
 * Version : 1.0.0
 * Licence : GNU AGPL v3
 * ------------------------------------------------------------
 */

final class Response
{
    /**
     * Envoie une réponse JSON.
     */
    public static function json(
        array $payload,
        int $statusCode = 200
    ): never {

        http_response_code($statusCode);

        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');

        echo json_encode(
            $payload,
            Config::JSON_OPTIONS
        );

        exit;
    }

    /**
     * Réponse 200.
     */
    public static function success(
        array $data = [],
        string $message = 'OK'
    ): never {

        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], 200);

    }

    /**
     * Réponse 201.
     */
    public static function created(
        array $data = [],
        string $message = 'Créé.'
    ): never {

        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], 201);

    }

    /**
     * Réponse 204.
     */
    public static function noContent(): never
    {
        http_response_code(204);
        exit;
    }

    /**
     * Réponse 400.
     */
    public static function badRequest(
        string $message,
        array $details = []
    ): never {

        self::error(
            $message,
            400,
            $details
        );

    }

    /**
     * Réponse 403.
     */
    public static function forbidden(
        string $message = 'Accès interdit.'
    ): never {

        self::error(
            $message,
            403
        );

    }

    /**
     * Réponse 404.
     */
    public static function notFound(
        string $message = 'Ressource introuvable.'
    ): never {

        self::error(
            $message,
            404
        );

    }

    /**
     * Réponse 409.
     */
    public static function conflict(
        string $message
    ): never {

        self::error(
            $message,
            409
        );

    }

    /**
     * Réponse 422.
     */
    public static function validationError(
        string $message,
        array $details = []
    ): never {

        self::error(
            $message,
            422,
            $details
        );

    }

    /**
     * Réponse 500.
     */
    public static function serverError(
        Throwable $exception
    ): never {

        self::error(
            $exception->getMessage(),
            500
        );

    }

    /**
     * Réponse d'erreur générique.
     */
    public static function error(
        string $message,
        int $statusCode = 400,
        array $details = []
    ): never {

        self::json([
            'success' => false,
            'message' => $message,
            'details' => $details
        ], $statusCode);

    }
}
