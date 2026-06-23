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
        'application/x-ofx' => 'ofx',
        'text/plain' => 'ofx',
        'text/xml' => 'ofx',
        'application/xml' => 'ofx',
    ];

    /** Arquivos da empresa (comprovantes, etc.) */
    public static function store(array $file, string $subdir, int $empresaId): ?string
    {
        return self::storeInNamespace((string) $empresaId, $subdir, $file);
    }

    /** Avatar e arquivos pessoais do usuário */
    public static function storeForUser(array $file, string $subdir, int $usuarioId): ?string
    {
        return self::storeInNamespace('users/' . $usuarioId, $subdir, $file);
    }

    private static function storeInNamespace(string $namespace, string $subdir, array $file): ?string
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
        $originalExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($originalExt, ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'ofx', 'xml'], true)) {
            return null;
        }

        $dir = App::basePath() . '/storage/uploads/' . $namespace . '/' . $subdir;
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }

        $name = bin2hex(random_bytes(16)) . '.' . $ext;
        $dest = $dir . '/' . $name;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return null;
        }

        return $namespace . '/' . $subdir . '/' . $name;
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
