<?php

declare(strict_types=1);

/**
 * ------------------------------------------------------------
 * PlanArbreBEA
 * Gestion des photos
 * Version : 1.0.0
 * Licence : GNU AGPL v3
 * ------------------------------------------------------------
 */

final class Photo
{
    /**
     * Télécharge une photo.
     *
     * @param array|null $photo Élément $_FILES['photo']
     * @return string|null Chemin relatif enregistré dans le GeoJSON
     */
    public static function upload(?array $photo): ?string
    {
        if (
            $photo === null ||
            !isset($photo['error']) ||
            $photo['error'] === UPLOAD_ERR_NO_FILE
        ) {
            return null;
        }

        if ($photo['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException(
                self::uploadError($photo['error'])
            );
        }

        if (!is_uploaded_file($photo['tmp_name'])) {
            throw new RuntimeException(
                'Fichier temporaire invalide.'
            );
        }

        if ($photo['size'] > Config::PHOTO_MAX_SIZE) {
            throw new RuntimeException(
                'La photo dépasse la taille maximale autorisée.'
            );
        }

        $mime = self::mimeType($photo['tmp_name']);

        if (
            !in_array(
                $mime,
                Config::PHOTO_ALLOWED_TYPES,
                true
            )
        ) {
            throw new RuntimeException(
                'Format de photo non autorisé.'
            );
        }

        $directory = Config::photoDirectory();

        $filename =
            bin2hex(random_bytes(16))
            . '.'
            . Config::PHOTO_EXTENSION;

        $destination =
            $directory
            . DIRECTORY_SEPARATOR
            . $filename;

        if (
            !move_uploaded_file(
                $photo['tmp_name'],
                $destination
            )
        ) {
            throw new RuntimeException(
                "Impossible d'enregistrer la photo."
            );
        }

        return sprintf(
            'uploads/photos/%s/%s',
            date('Y'),
            $filename
        );
    }

    /**
     * Suppression d'une photo.
     */
    public static function delete(?string $relativePath): void
    {
        if (empty($relativePath)) {
            return;
        }

        $absolutePath =
            dirname(__DIR__)
            . DIRECTORY_SEPARATOR
            . str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $relativePath
            );

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    /**
     * Détermine le type MIME.
     */
    private static function mimeType(
        string $filename
    ): string {

        $finfo = new finfo(FILEINFO_MIME_TYPE);

        return $finfo->file($filename) ?: '';

    }

    /**
     * Messages d'erreur upload PHP.
     */
    private static function uploadError(
        int $code
    ): string {

        return match ($code) {

            UPLOAD_ERR_INI_SIZE,
            UPLOAD_ERR_FORM_SIZE =>
                'Le fichier est trop volumineux.',

            UPLOAD_ERR_PARTIAL =>
                'Le téléchargement est incomplet.',

            UPLOAD_ERR_NO_TMP_DIR =>
                'Dossier temporaire manquant.',

            UPLOAD_ERR_CANT_WRITE =>
                'Impossible d\'écrire le fichier.',

            UPLOAD_ERR_EXTENSION =>
                'Le téléchargement a été interrompu.',

            default =>
                'Erreur lors du téléchargement.'

        };

    }
}
