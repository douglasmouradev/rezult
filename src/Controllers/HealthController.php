<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;

final class HealthController
{
    public function check(): void
    {
        $ok = true;
        $checks = ['app' => 'ok'];

        try {
            App::pdo()->query('SELECT 1');
            $checks['database'] = 'ok';
        } catch (\Throwable $e) {
            $checks['database'] = 'fail';
            $ok = false;
        }

        http_response_code($ok ? 200 : 503);
        header('Content-Type: application/json');
        echo json_encode(['status' => $ok ? 'healthy' : 'degraded', 'checks' => $checks], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
