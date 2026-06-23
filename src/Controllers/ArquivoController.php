<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Upload;
use App\Policies\TenantPolicy;

final class ArquivoController
{
    public function download(): void
    {
        $path = $_GET['path'] ?? '';
        if ($path === '' || str_contains($path, '..')) {
            http_response_code(404);
            exit;
        }

        $uid = TenantPolicy::usuarioId();
        $empresaId = TenantPolicy::empresaId();

        $permitido = str_starts_with($path, "users/{$uid}/")
            || ($empresaId > 0 && str_starts_with($path, "{$empresaId}/"));

        if (!$permitido) {
            http_response_code(403);
            exit;
        }

        $absolute = Upload::absolutePath($path);
        if (!$absolute || !is_file($absolute)) {
            http_response_code(404);
            exit;
        }

        $mime = mime_content_type($absolute) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . basename($absolute) . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=3600');
        readfile($absolute);
        exit;
    }
}
