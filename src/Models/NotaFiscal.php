<?php

declare(strict_types=1);

namespace App\Models;

final class NotaFiscal extends BaseModel
{
    protected string $table = 'notas_fiscais';

    public function listar(int $empresaId, int $page = 1, int $perPage = 30): array
    {
        $offset = ($page - 1) * $perPage;
        $count = $this->db->prepare('SELECT COUNT(*) FROM notas_fiscais WHERE empresa_id = :e');
        $count->execute(['e' => $empresaId]);
        $total = (int) $count->fetchColumn();

        $stmt = $this->db->prepare(
            "SELECT * FROM notas_fiscais WHERE empresa_id = :e ORDER BY criado_em DESC LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute(['e' => $empresaId]);

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }
}
