<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;

final class HealthController
{
    public function check(): void
    {
        $token = $_ENV['HEALTH_TOKEN'] ?? '';
        if ($token !== '') {
            $provided = $_GET['token'] ?? ($_SERVER['HTTP_X_HEALTH_TOKEN'] ?? '');
            if (!hash_equals($token, (string) $provided)) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Forbidden']);
                exit;
            }
        }

        $ok = true;
        $checks = ['app' => 'ok'];

        try {
            App::pdo()->query('SELECT 1');
            $checks['database'] = 'ok';
        } catch (\Throwable) {
            $checks['database'] = 'fail';
            $ok = false;
        }

        http_response_code($ok ? 200 : 503);
        header('Content-Type: application/json');
        echo json_encode(['status' => $ok ? 'healthy' : 'degraded', 'checks' => $checks], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
