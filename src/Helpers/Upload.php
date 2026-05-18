<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Core\App;

final class Upload
{
    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];

    /** Retorna path relativo para download autenticado */
    public static function store(array $file, string $subdir, int $empresaId): ?string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $maxBytes = App::config('upload_max_mb', 5) * 1024 * 1024;
        if ($file['size'] > $maxBytes) {
            return null;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!isset(self::ALLOWED[$mime])) {
            return null;
        }

        $ext = self::ALLOWED[$mime];
        if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== $ext) {
            return null;
        }

        $dir = App::basePath() . '/storage/uploads/' . $empresaId . '/' . $subdir;
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }

        $name = bin2hex(random_bytes(16)) . '.' . $ext;
        $dest = $dir . '/' . $name;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return null;
        }

        return $empresaId . '/' . $subdir . '/' . $name;
    }

    public static function absolutePath(string $relative): ?string
    {
        $path = App::basePath() . '/storage/uploads/' . $relative;
        $real = realpath($path);
        $base = realpath(App::basePath() . '/storage/uploads');
        if ($real === false || $base === false || !str_starts_with($real, $base)) {
            return null;
        }
        return $real;
    }
}
