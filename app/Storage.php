<?php

declare(strict_types=1);

/**
 * ------------------------------------------------------------
 * PlanArbreBEA
 * Gestion centralisée du système de fichiers
 * Version : 1.0.0
 * Licence : GNU AGPL v3
 * ------------------------------------------------------------
 */

final class Storage
{
    /**
     * Vérifie l'existence d'un fichier.
     */
    public static function exists(string $filename): bool
    {
        return is_file($filename);
    }

    /**
     * Vérifie l'existence d'un dossier.
     */
    public static function directoryExists(string $directory): bool
    {
        return is_dir($directory);
    }

    /**
     * Lit un fichier.
     */
    public static function read(string $filename): string
    {
        if (!self::exists($filename)) {

            throw new RuntimeException(
                "Fichier introuvable : {$filename}"
            );

        }

        $content = file_get_contents($filename);

        if ($content === false) {

            throw new RuntimeException(
                "Impossible de lire le fichier : {$filename}"
            );

        }

        return $content;
    }

    /**
     * Écrit un fichier.
     */
    public static function write(
        string $filename,
        string $content
    ): void {

        $directory = dirname($filename);

        self::createDirectory($directory);

        if (
            file_put_contents(
                $filename,
                $content,
                LOCK_EX
            ) === false
        ) {

            throw new RuntimeException(
                "Impossible d'écrire le fichier : {$filename}"
            );

        }

        @chmod(
            $filename,
            Config::FILE_PERMISSIONS
        );

        clearstatcache(
            true,
            $filename
        );

    }

    /**
     * Déplace un fichier uploadé.
     */
    public static function moveUploadedFile(
        string $tmpName,
        string $destination
    ): void {

        self::createDirectory(
            dirname($destination)
        );

        if (
            !move_uploaded_file(
                $tmpName,
                $destination
            )
        ) {

            throw new RuntimeException(
                "Impossible d'enregistrer le fichier."
            );

        }

        @chmod(
            $destination,
            Config::FILE_PERMISSIONS
        );

    }

    /**
     * Supprime un fichier.
     */
    public static function delete(
        string $filename
    ): void {

        if (
            self::exists($filename)
        ) {

            @unlink($filename);

        }

    }

    /**
     * Crée un dossier.
     */
    public static function createDirectory(
        string $directory
    ): void {

        if (
            self::directoryExists($directory)
        ) {

            return;

        }

        if (
            !mkdir(
                $directory,
                Config::DIRECTORY_PERMISSIONS,
                true
            )
            &&
            !self::directoryExists($directory)
        ) {

            throw new RuntimeException(
                "Impossible de créer le dossier : {$directory}"
            );

        }

    }

    /**
     * Lit un GeoJSON.
     */
    public static function readJson(
        string $filename
    ): array {

        $json = self::read($filename);

        $data = json_decode(
            $json,
            true
        );

        if (!is_array($data)) {

            throw new RuntimeException(
                "JSON invalide : {$filename}"
            );

        }

        return $data;

    }

    /**
     * Écrit un GeoJSON.
     */
    public static function writeJson(
        string $filename,
        array $data
    ): void {

        $json = json_encode(
            $data,
            Config::JSON_OPTIONS
        );

        if ($json === false) {

            throw new RuntimeException(
                "Erreur lors de l'encodage JSON."
            );

        }

        self::write(
            $filename,
            $json
        );

    }

}
