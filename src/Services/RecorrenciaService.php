<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Logger;
use App\Services\AutomacaoService;

/** Gera próximas parcelas de lançamentos recorrentes */
final class RecorrenciaService
{
    public function processar(): int
    {
        $stmt = App::pdo()->query(
            "SELECT * FROM lancamentos
             WHERE recorrente = 1 AND status != 'cancelado'
             AND frequencia IS NOT NULL
             AND (recorrente_proximo IS NULL OR recorrente_proximo <= CURDATE())
             LIMIT 200"
        );
        $gerados = 0;
        foreach ($stmt->fetchAll() as $base) {
            if ($this->gerarProxima($base)) {
                $gerados++;
            }
        }
        if ($gerados > 0) {
            Logger::info('Recorrências geradas', ['total' => $gerados]);
        }
        return $gerados;
    }

    private function gerarProxima(array $base): bool
    {
        $proximaData = $this->calcularProximaData($base['data_lancamento'], $base['frequencia'], $base['recorrente_proximo']);
        if ($proximaData === null) {
            return false;
        }

        $pdo = App::pdo();
        $pdo->beginTransaction();
        try {
            $ins = $pdo->prepare(
                'INSERT INTO lancamentos (empresa_id, conta_id, categoria_id, centro_custo_id, meta_id, tipo, descricao, valor,
                 data_lancamento, data_vencimento, status, recorrente, frequencia, observacoes, tags)
                 VALUES (:e,:c,:cat,:cc,:m,:t,:d,:v,:dl,:dv,:st,0,NULL,:obs,:tags)'
            );
            $ins->execute([
                'e' => $base['empresa_id'],
                'c' => $base['conta_id'],
                'cat' => $base['categoria_id'],
                'cc' => $base['centro_custo_id'] ?? null,
                'm' => $base['meta_id'],
                't' => $base['tipo'],
                'd' => $base['descricao'],
                'v' => $base['valor'],
                'dl' => $proximaData,
                'dv' => $base['data_vencimento'] ? $this->calcularProximaData($base['data_vencimento'], $base['frequencia'], null) : null,
                'st' => 'pendente',
                'obs' => 'Gerado automaticamente (recorrente)',
                'tags' => $base['tags'],
            ]);
            $pdo->prepare(
                'UPDATE lancamentos SET recorrente_proximo = :np WHERE id = :id'
            )->execute([
                'np' => $this->calcularProximaData($proximaData, $base['frequencia'], null),
                'id' => $base['id'],
            ]);
            $pdo->commit();
            (new AutomacaoService())->aplicarGatilho((int) $base['empresa_id'], 'recorrente');
            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            Logger::error('Falha recorrência', ['id' => $base['id'], 'msg' => $e->getMessage()]);
            return false;
        }
    }

    private function calcularProximaData(string $de, string $freq, ?string $apos): ?string
    {
        $base = $apos ?? $de;
        $dt = new \DateTimeImmutable($base);
        $dt = match ($freq) {
            'semanal' => $dt->modify('+1 week'),
            'mensal' => $dt->modify('+1 month'),
            'anual' => $dt->modify('+1 year'),
            default => null,
        };
        return $dt?->format('Y-m-d');
    }
}
