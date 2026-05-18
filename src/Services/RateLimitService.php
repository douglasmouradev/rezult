<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;

final class RateLimitService
{
    public function excedido(string $acao, string $chave, int $max = 5, int $minutos = 15): bool
    {
        $stmt = App::pdo()->prepare(
            'SELECT COUNT(*) FROM rate_limits
             WHERE acao = :a AND chave = :c AND criado_em > DATE_SUB(NOW(), INTERVAL :m MINUTE)'
        );
        $stmt->execute(['a' => $acao, 'c' => $chave, 'm' => $minutos]);
        return (int) $stmt->fetchColumn() >= $max;
    }

    public function registrar(string $acao, string $chave): void
    {
        App::pdo()->prepare(
            'INSERT INTO rate_limits (acao, chave, ip) VALUES (:a, :c, :ip)'
        )->execute([
            'a' => $acao,
            'c' => $chave,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }
}
