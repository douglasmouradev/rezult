<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Helpers\Session;

final class AuditoriaService
{
    public static function registrar(string $acao, ?string $entidade = null, ?int $entidadeId = null, array $detalhes = []): void
    {
        try {
            $stmt = App::pdo()->prepare(
                'INSERT INTO auditoria (usuario_id, empresa_id, acao, entidade, entidade_id, ip, detalhes)
                 VALUES (:u, :e, :a, :ent, :eid, :ip, :d)'
            );
            $stmt->execute([
                'u' => Session::get('usuario_id'),
                'e' => Session::get('empresa_id'),
                'a' => $acao,
                'ent' => $entidade,
                'eid' => $entidadeId,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'd' => $detalhes ? json_encode($detalhes) : null,
            ]);
        } catch (\Throwable) {
            // Não interrompe fluxo principal
        }
    }
}
