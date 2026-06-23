<?php

declare(strict_types=1);

namespace App\Models;

final class Lancamento extends BaseModel
{
    protected string $table = 'lancamentos';

    public function listarFiltrado(int $empresaId, array $filtros, int $page = 1, int $perPage = 30): array
    {
        $where = ['l.empresa_id = :empresa_id'];
        $params = ['empresa_id' => $empresaId];

        if (!empty($filtros['tipo'])) {
            $where[] = 'l.tipo = :tipo';
            $params['tipo'] = $filtros['tipo'];
        }
        if (!empty($filtros['status'])) {
            $where[] = 'l.status = :status';
            $params['status'] = $filtros['status'];
        }
        if (!empty($filtros['conta_id'])) {
            $where[] = 'l.conta_id = :conta_id';
            $params['conta_id'] = $filtros['conta_id'];
        }
        if (!empty($filtros['categoria_id'])) {
            $where[] = 'l.categoria_id = :categoria_id';
            $params['categoria_id'] = $filtros['categoria_id'];
        }
        if (!empty($filtros['centro_custo_id'])) {
            $where[] = 'l.centro_custo_id = :centro_custo_id';
            $params['centro_custo_id'] = $filtros['centro_custo_id'];
        }
        if (!empty($filtros['de'])) {
            $where[] = 'l.data_lancamento >= :de';
            $params['de'] = $filtros['de'];
        }
        if (!empty($filtros['ate'])) {
            $where[] = 'l.data_lancamento <= :ate';
            $params['ate'] = $filtros['ate'];
        }
        if (!empty($filtros['busca'])) {
            $where[] = 'l.descricao LIKE :busca';
            $params['busca'] = '%' . $filtros['busca'] . '%';
        }
        if (!empty($filtros['tag'])) {
            $where[] = 'JSON_CONTAINS(l.tags, :tag_json)';
            $params['tag_json'] = json_encode($filtros['tag']);
        }
        if (!empty($filtros['parceiro'])) {
            $where[] = 'l.parceiro LIKE :parceiro';
            $params['parceiro'] = '%' . $filtros['parceiro'] . '%';
        }
        if (!empty($filtros['vencimento_filtro'])) {
            match ($filtros['vencimento_filtro']) {
                'atrasado' => $where[] = "l.status = 'pendente' AND l.data_vencimento < CURDATE()",
                'hoje' => $where[] = "l.status = 'pendente' AND l.data_vencimento = CURDATE()",
                'semana' => $where[] = "l.status = 'pendente' AND l.data_vencimento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)",
                'mes' => $where[] = "l.status = 'pendente' AND l.data_vencimento BETWEEN CURDATE() AND LAST_DAY(CURDATE())",
                default => null,
            };
        }

        $orderBy = !empty($filtros['ordenar_vencimento'])
            ? 'l.data_vencimento ASC, l.id ASC'
            : 'l.data_lancamento DESC, l.id DESC';

        $whereSql = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM lancamentos l WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT l.*, c.nome AS conta_nome, cat.nome AS categoria_nome, cat.cor AS categoria_cor,
                       cc.nome AS centro_custo_nome
                FROM lancamentos l
                LEFT JOIN contas c ON c.id = l.conta_id
                LEFT JOIN categorias cat ON cat.id = l.categoria_id
                LEFT JOIN centros_custo cc ON cc.id = l.centro_custo_id
                WHERE {$whereSql}
                ORDER BY {$orderBy}
                LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'pages' => (int) ceil($total / $perPage),
        ];
    }

    public function vencendoEm(int $empresaId, int $dias = 7): array
    {
        $stmt = $this->db->prepare(
            "SELECT l.*, c.nome AS conta_nome FROM lancamentos l
             LEFT JOIN contas c ON c.id = l.conta_id
             WHERE l.empresa_id = :e AND l.status = 'pendente'
             AND l.data_vencimento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :d DAY)
             ORDER BY l.data_vencimento ASC LIMIT 20"
        );
        $stmt->bindValue(':e', $empresaId, \PDO::PARAM_INT);
        $stmt->bindValue(':d', $dias, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function invalidarCacheDashboard(int $empresaId): void
    {
        unset($_SESSION['dashboard_cache'][$empresaId]);
    }

    public function resumoFluxo(int $empresaId, string $tipo): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                COALESCE(SUM(CASE WHEN status = 'pendente' THEN valor ELSE 0 END), 0) AS total_pendente,
                COALESCE(SUM(CASE WHEN status = 'pendente' AND data_vencimento < CURDATE() THEN valor ELSE 0 END), 0) AS total_atrasado,
                COALESCE(SUM(CASE WHEN status = 'pendente' AND data_vencimento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN valor ELSE 0 END), 0) AS total_semana,
                COUNT(CASE WHEN status = 'pendente' THEN 1 END) AS qtd_pendente,
                COUNT(CASE WHEN status = 'pendente' AND data_vencimento < CURDATE() THEN 1 END) AS qtd_atrasado
             FROM lancamentos
             WHERE empresa_id = :e AND tipo = :t AND tipo != 'transferencia'"
        );
        $stmt->execute(['e' => $empresaId, 't' => $tipo]);
        return $stmt->fetch() ?: [];
    }

    public function marcarPagosEmLote(array $ids, int $empresaId): int
    {
        if ($ids === []) {
            return 0;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare(
            "UPDATE lancamentos SET status = 'pago', data_lancamento = COALESCE(data_lancamento, CURDATE())
             WHERE empresa_id = ? AND id IN ({$placeholders}) AND status = 'pendente'"
        );
        $params = array_merge([$empresaId], $ids);
        $stmt->execute($params);
        $this->invalidarCacheDashboard($empresaId);
        return $stmt->rowCount();
    }
}
