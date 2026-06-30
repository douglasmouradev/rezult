<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Logger;

final class OrcamentoService
{
    /** @return list<array<string, mixed>> */
    public function listarPorMes(int $empresaId, string $mes): array
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
            $mes = date('Y-m');
        }

        if (!$this->tabelaExiste()) {
            Logger::error('Tabela orcamentos ausente', ['empresa_id' => $empresaId]);
            throw new \RuntimeException('Módulo de orçamento não instalado. Execute: php bin/migrate.php');
        }

        $stmt = App::pdo()->prepare(
            'SELECT o.id, o.empresa_id, o.categoria_id, o.mes, o.valor_planejado,
                    c.nome AS categoria_nome,
                    COALESCE((
                        SELECT SUM(l.valor)
                        FROM lancamentos l
                        INNER JOIN categorias cat ON cat.id = l.categoria_id
                        WHERE l.empresa_id = o.empresa_id
                          AND l.categoria_id = o.categoria_id
                          AND l.status = \'pago\'
                          AND l.tipo = cat.tipo
                          AND DATE_FORMAT(l.data_lancamento, \'%Y-%m\') = o.mes
                    ), 0) AS realizado
             FROM orcamentos o
             LEFT JOIN categorias c ON c.id = o.categoria_id AND c.empresa_id = o.empresa_id
             WHERE o.empresa_id = :e AND o.mes = :m
             ORDER BY c.nome ASC, o.id ASC'
        );
        $stmt->execute(['e' => $empresaId, 'm' => $mes]);

        return $stmt->fetchAll() ?: [];
    }

    private function tabelaExiste(): bool
    {
        try {
            $stmt = App::pdo()->query("SHOW TABLES LIKE 'orcamentos'");

            return (bool) $stmt->fetch();
        } catch (\Throwable) {
            return false;
        }
    }
}
