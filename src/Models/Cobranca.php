<?php

declare(strict_types=1);

namespace App\Models;

final class Cobranca extends BaseModel
{
    protected string $table = 'cobrancas';

    public function listar(int $empresaId, array $filtros = [], int $page = 1, int $perPage = 30): array
    {
        $where = ['empresa_id = :empresa_id'];
        $params = ['empresa_id' => $empresaId];

        if (!empty($filtros['status'])) {
            $where[] = 'status = :status';
            $params['status'] = $filtros['status'];
        }

        $whereSql = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;

        $count = $this->db->prepare("SELECT COUNT(*) FROM cobrancas WHERE {$whereSql}");
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $stmt = $this->db->prepare(
            "SELECT * FROM cobrancas WHERE {$whereSql} ORDER BY vencimento DESC, id DESC LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }
}
