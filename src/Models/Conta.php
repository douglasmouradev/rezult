<?php

declare(strict_types=1);

namespace App\Models;

final class Conta extends BaseModel
{
    protected string $table = 'contas';

    public function saldoAtual(int $contaId, int $empresaId): float
    {
        $conta = $this->find($contaId, $empresaId);
        if (!$conta) {
            return 0.0;
        }

        $sql = "SELECT COALESCE(SUM(
            CASE
                WHEN tipo = 'receita' AND status = 'pago' THEN valor
                WHEN tipo = 'despesa' AND status = 'pago' THEN -valor
                WHEN tipo = 'transferencia' AND status = 'pago' THEN valor
                ELSE 0
            END
        ), 0) AS movimentacao
        FROM lancamentos WHERE conta_id = :cid AND empresa_id = :eid";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cid' => $contaId, 'eid' => $empresaId]);
        $mov = (float) ($stmt->fetch()['movimentacao'] ?? 0);

        return (float) $conta['saldo_inicial'] + $mov;
    }

    public function saldosPorEmpresa(int $empresaId): array
    {
        $contas = $this->findAll($empresaId, 'nome ASC');
        foreach ($contas as &$c) {
            $c['saldo_atual'] = $this->saldoAtual((int) $c['id'], $empresaId);
        }
        return $contas;
    }

    /** Extrato com saldo acumulado (window function) */
    public function extrato(int $contaId, int $empresaId, ?string $de = null, ?string $ate = null): array
    {
        $conta = $this->find($contaId, $empresaId);
        $saldoInicial = (float) ($conta['saldo_inicial'] ?? 0);

        $sql = "WITH mov AS (
            SELECT l.*,
                CASE
                    WHEN l.tipo = 'receita' AND l.status = 'pago' THEN l.valor
                    WHEN l.tipo = 'despesa' AND l.status = 'pago' THEN -l.valor
                    WHEN l.tipo = 'transferencia' AND l.status = 'pago' THEN l.valor
                    ELSE 0
                END AS valor_signed
            FROM lancamentos l
            WHERE l.conta_id = :cid AND l.empresa_id = :eid
            AND (:de IS NULL OR l.data_lancamento >= :de)
            AND (:ate IS NULL OR l.data_lancamento <= :ate)
        )
        SELECT m.*,
            :saldo_inicial + SUM(m.valor_signed) OVER (
                ORDER BY m.data_lancamento, m.id
                ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
            ) AS saldo_acumulado
        FROM mov m
        ORDER BY m.data_lancamento DESC, m.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'cid' => $contaId,
            'eid' => $empresaId,
            'de' => $de,
            'ate' => $ate,
            'saldo_inicial' => $saldoInicial,
        ]);
        return $stmt->fetchAll();
    }
}
