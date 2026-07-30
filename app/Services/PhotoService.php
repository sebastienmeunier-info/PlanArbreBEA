<?php

declare(strict_types=1);

namespace Plantons\Services;

use RuntimeException;

final class PhotoService
{
    public function store(?array $upload, array $security, string $uploadsDirectory): array
    {
        if ($upload === null) { return []; }
        if (!function_exists('imagewebp')) {
            throw new RuntimeException('Le serveur doit activer l’extension GD pour traiter les photos.');
        }

        $files = is_array($upload['name'] ?? null) ? $upload['name'] : [$upload['name'] ?? ''];
        if (count(array_filter($files, static fn(string $name): bool => $name !== '')) > $security['max_photos_per_proposal']) {
            throw new RuntimeException('Vous pouvez envoyer au maximum trois photos.');
        }

        $stored = [];
        foreach ($files as $index => $name) {
            if ($name === '') { continue; }
            if (($upload['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Le téléversement d’une photo a échoué.');
            }
            $tmp = $upload['tmp_name'][$index];
            if (($upload['size'][$index] ?? 0) > $security['max_upload_size']) {
                throw new RuntimeException('Chaque photo est limitée à 1 Mo.');
            }
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
            if (!in_array($mime, $security['allowed_photo_mime_types'], true)) {
                throw new RuntimeException('Format de photo non autorisé.');
            }
            $image = match ($mime) {
                'image/jpeg' => imagecreatefromjpeg($tmp),
                'image/png' => imagecreatefrompng($tmp),
                'image/webp' => imagecreatefromwebp($tmp),
            };
            if ($image === false) { throw new RuntimeException('Image illisible.'); }

            $year = date('Y');
            $directory = $uploadsDirectory . '/photos/' . $year;
            if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new RuntimeException('Impossible de créer le dossier des photos.');
            }
            $filename = bin2hex(random_bytes(16)) . '.webp';
            imagewebp($image, $directory . '/' . $filename, 80);
            imagedestroy($image);
            $stored[] = 'uploads/photos/' . $year . '/' . $filename;
        }
        return $stored;
    }
}
