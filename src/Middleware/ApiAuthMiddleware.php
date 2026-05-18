<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\App;
use App\Helpers\Session;
use Closure;

final class ApiAuthMiddleware
{
    public function __invoke(Closure $next): void
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
            $this->unauthorized();
        }
        $token = $m[1];
        $prefix = substr($token, 0, 12);
        $stmt = App::pdo()->prepare(
            'SELECT at.*, u.id AS uid FROM api_tokens at
             JOIN usuarios u ON u.id = at.usuario_id
             WHERE at.prefixo = :p LIMIT 1'
        );
        $stmt->execute(['p' => $prefix]);
        $row = $stmt->fetch();
        if (!$row || !password_verify($token, $row['token_hash'])) {
            $this->unauthorized();
        }
        Session::set('usuario_id', (int) $row['uid']);
        Session::set('empresa_id', (int) $row['empresa_id']);
        App::pdo()->prepare('UPDATE api_tokens SET ultimo_uso = NOW() WHERE id = :id')->execute(['id' => $row['id']]);
        $next();
    }

    private function unauthorized(): never
    {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}
