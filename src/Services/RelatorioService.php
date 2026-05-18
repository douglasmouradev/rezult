<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;

final class RelatorioService
{
    public function dre(int $empresaId, string $de, string $ate): array
    {
        $stmt = App::pdo()->prepare(
            "WITH totais AS (
                SELECT c.tipo, c.nome, SUM(l.valor) AS total
                FROM lancamentos l
                JOIN categorias c ON c.id = l.categoria_id
                WHERE l.empresa_id = :e AND l.status = 'pago'
                AND l.data_lancamento BETWEEN :de AND :ate
                AND l.tipo IN ('receita', 'despesa')
                GROUP BY c.id
            )
            SELECT tipo, nome, total FROM totais ORDER BY tipo, total DESC"
        );
        $stmt->execute(['e' => $empresaId, 'de' => $de, 'ate' => $ate]);
        $linhas = $stmt->fetchAll();

        $receitas = array_filter($linhas, fn ($l) => $l['tipo'] === 'receita');
        $despesas = array_filter($linhas, fn ($l) => $l['tipo'] === 'despesa');
        $totalReceitas = array_sum(array_column($receitas, 'total'));
        $totalDespesas = array_sum(array_column($despesas, 'total'));

        return [
            'receitas' => $receitas,
            'despesas' => $despesas,
            'total_receitas' => $totalReceitas,
            'total_despesas' => $totalDespesas,
            'resultado' => $totalReceitas - $totalDespesas,
        ];
    }

    public function fluxoCaixa(int $empresaId, string $de, string $ate): array
    {
        $stmt = App::pdo()->prepare(
            "SELECT data_lancamento AS data,
                SUM(CASE WHEN tipo='receita' AND status='pago' THEN valor ELSE 0 END) AS entradas,
                SUM(CASE WHEN tipo='despesa' AND status='pago' THEN valor ELSE 0 END) AS saidas
             FROM lancamentos
             WHERE empresa_id = :e AND data_lancamento BETWEEN :de AND :ate
             GROUP BY data_lancamento ORDER BY data_lancamento"
        );
        $stmt->execute(['e' => $empresaId, 'de' => $de, 'ate' => $ate]);
        return $stmt->fetchAll();
    }

    public function porCategoria(int $empresaId, string $de, string $ate, string $tipo): array
    {
        $stmt = App::pdo()->prepare(
            "SELECT c.nome, c.cor, SUM(l.valor) AS total, COUNT(*) AS qtd
             FROM lancamentos l
             JOIN categorias c ON c.id = l.categoria_id
             WHERE l.empresa_id = :e AND l.tipo = :tipo AND l.status = 'pago'
             AND l.data_lancamento BETWEEN :de AND :ate
             GROUP BY c.id ORDER BY total DESC"
        );
        $stmt->execute(['e' => $empresaId, 'de' => $de, 'ate' => $ate, 'tipo' => $tipo]);
        return $stmt->fetchAll();
    }

    public function porTag(int $empresaId, string $de, string $ate): array
    {
        $stmt = App::pdo()->prepare(
            "SELECT jt.tag, SUM(l.valor) AS total, COUNT(*) AS qtd
             FROM lancamentos l,
             JSON_TABLE(l.tags, '$[*]' COLUMNS (tag VARCHAR(50) PATH '$')) jt
             WHERE l.empresa_id = :e AND l.tipo = 'despesa' AND l.status = 'pago'
             AND l.data_lancamento BETWEEN :de AND :ate AND l.tags IS NOT NULL
             GROUP BY jt.tag ORDER BY total DESC"
        );
        $stmt->execute(['e' => $empresaId, 'de' => $de, 'ate' => $ate]);
        return $stmt->fetchAll();
    }
}
