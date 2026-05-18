<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Helpers\Session;
use App\Models\Conta;
use App\Models\Lancamento;

final class DashboardService
{
    public function __construct(
        private Lancamento $lancamentos = new Lancamento(),
        private Conta $contas = new Conta(),
    ) {}

    public function dados(int $empresaId): array
    {
        $ttl = App::config('dashboard_cache_ttl', 300);
        $cache = Session::get('dashboard_cache', [])[$empresaId] ?? null;

        if ($cache && ($cache['expires'] ?? 0) > time()) {
            return $cache['data'];
        }

        $db = App::pdo();
        $mesAtual = date('Y-m');

        // Saldo total
        $saldos = $this->contas->saldosPorEmpresa($empresaId);
        $saldoTotal = array_sum(array_column($saldos, 'saldo_atual'));

        $stmt = $db->prepare(
            "SELECT
                COALESCE(SUM(CASE WHEN tipo='receita' AND status='pago' THEN valor END), 0) AS receitas,
                COALESCE(SUM(CASE WHEN tipo='despesa' AND status='pago' THEN valor END), 0) AS despesas
             FROM lancamentos
             WHERE empresa_id = :e AND data_lancamento >= :de AND data_lancamento <= :ate"
        );
        $stmt->execute(['e' => $empresaId, 'de' => $mesAtual . '-01', 'ate' => date('Y-m-t')]);
        $mes = $stmt->fetch();

        // Fluxo 12 meses
        $fluxoStmt = $db->prepare(
            "WITH meses AS (
                SELECT DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL n MONTH), '%Y-%m') AS ym
                FROM (SELECT 0 n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
                      UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11) t
            )
            SELECT m.ym,
                COALESCE(SUM(CASE WHEN l.tipo='receita' AND l.status='pago' THEN l.valor END), 0) -
                COALESCE(SUM(CASE WHEN l.tipo='despesa' AND l.status='pago' THEN l.valor END), 0) AS saldo
            FROM meses m
            LEFT JOIN lancamentos l ON DATE_FORMAT(l.data_lancamento, '%Y-%m') = m.ym AND l.empresa_id = :e
            GROUP BY m.ym ORDER BY m.ym"
        );
        $fluxoStmt->execute(['e' => $empresaId]);
        $fluxo = $fluxoStmt->fetchAll();

        // Despesas por categoria
        $catStmt = $db->prepare(
            "SELECT cat.nome, cat.cor, SUM(l.valor) AS total
             FROM lancamentos l
             JOIN categorias cat ON cat.id = l.categoria_id
             WHERE l.empresa_id = :e AND l.tipo = 'despesa' AND l.status = 'pago'
             AND l.data_lancamento >= :de AND l.data_lancamento <= :ate
             GROUP BY cat.id ORDER BY total DESC LIMIT 8"
        );
        $catStmt->execute(['e' => $empresaId, 'de' => $mesAtual . '-01', 'ate' => date('Y-m-t')]);
        $despesasCat = $catStmt->fetchAll();

        // Receitas vs despesas por mês (6 meses)
        $compStmt = $db->prepare(
            "SELECT DATE_FORMAT(data_lancamento, '%Y-%m') AS ym,
                SUM(CASE WHEN tipo='receita' AND status='pago' THEN valor ELSE 0 END) AS receitas,
                SUM(CASE WHEN tipo='despesa' AND status='pago' THEN valor ELSE 0 END) AS despesas
             FROM lancamentos WHERE empresa_id = :e
             AND data_lancamento >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
             GROUP BY ym ORDER BY ym"
        );
        $compStmt->execute(['e' => $empresaId]);
        $comparativo = $compStmt->fetchAll();

        $ultimos = $this->lancamentos->listarFiltrado($empresaId, [], 1, 8);
        $vencendo = $this->lancamentos->vencendoEm($empresaId);

        $data = [
            'saldo_total' => $saldoTotal,
            'receitas_mes' => (float) $mes['receitas'],
            'despesas_mes' => (float) $mes['despesas'],
            'resultado_mes' => (float) $mes['receitas'] - (float) $mes['despesas'],
            'fluxo_12m' => $fluxo,
            'despesas_categoria' => $despesasCat,
            'comparativo_mensal' => $comparativo,
            'ultimos_lancamentos' => $ultimos['items'],
            'vencendo' => $vencendo,
        ];

        $cacheAll = Session::get('dashboard_cache', []);
        $cacheAll[$empresaId] = [
            'expires' => time() + $ttl,
            'data' => $data,
        ];
        Session::set('dashboard_cache', $cacheAll);

        return $data;
    }
}
