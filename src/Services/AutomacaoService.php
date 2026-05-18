<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Models\Lancamento;
use App\Models\RegraAutomacao;
use App\Services\NotificationService;

final class AutomacaoService
{
    public function __construct(
        private RegraAutomacao $regras = new RegraAutomacao(),
        private Lancamento $lancamentos = new Lancamento(),
    ) {}

    public function processarVencimentos(): int
    {
        $processados = 0;
        $stmt = App::pdo()->query(
            "SELECT DISTINCT empresa_id FROM lancamentos
             WHERE status = 'pendente' AND data_vencimento IS NOT NULL
             AND data_vencimento <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)"
        );
        foreach ($stmt->fetchAll() as $row) {
            $eid = (int) $row['empresa_id'];
            $processados += $this->aplicarGatilho($eid, 'vencimento');
        }
        return $processados;
    }

    public function aplicarGatilho(int $empresaId, string $gatilho, ?array $contexto = null): int
    {
        $aplicadas = 0;
        foreach ($this->regras->listarAtivas($empresaId, $gatilho) as $regra) {
            if ($this->executarRegra($regra, $empresaId, $contexto)) {
                $aplicadas++;
            }
        }
        return $aplicadas;
    }

    public function aplicarDescricao(int $empresaId, string $descricao, int $lancamentoId): void
    {
        foreach ($this->regras->listarAtivas($empresaId, 'descricao_contem') as $regra) {
            $cond = json_decode($regra['condicao'] ?? '{}', true) ?: [];
            $texto = $cond['texto'] ?? '';
            if ($texto !== '' && stripos($descricao, $texto) !== false) {
                $this->executarRegra($regra, $empresaId, ['lancamento_id' => $lancamentoId]);
            }
        }
    }

    private function executarRegra(array $regra, int $empresaId, ?array $ctx): bool
    {
        $params = json_decode($regra['parametros'], true) ?: [];
        $lancId = $ctx['lancamento_id'] ?? null;

        return match ($regra['acao']) {
            'categorizar' => $this->acaoCategorizar($lancId, $empresaId, (int) ($params['categoria_id'] ?? 0)),
            'notificar' => $this->acaoNotificar($empresaId, $params['mensagem'] ?? $regra['nome']),
            'marcar_pago' => $this->acaoMarcarPago($lancId, $empresaId),
            'criar_lancamento' => $this->acaoCriarLancamento($empresaId, $params),
            default => false,
        };
    }

    private function acaoCategorizar(?int $lancId, int $empresaId, int $catId): bool
    {
        if (!$lancId || !$catId) {
            return false;
        }
        $this->lancamentos->save(['id' => $lancId, 'categoria_id' => $catId], $empresaId);
        return true;
    }

    private function acaoNotificar(int $empresaId, string $msg): bool
    {
        $stmt = App::pdo()->prepare(
            'SELECT usuario_id FROM usuario_empresa WHERE empresa_id = :e AND papel IN (\'dono\',\'admin\') LIMIT 5'
        );
        $stmt->execute(['e' => $empresaId]);
        $notif = new NotificationService();
        foreach ($stmt->fetchAll() as $u) {
            $notif->criar((int) $u['usuario_id'], 'Automação', $msg, $empresaId);
        }
        return true;
    }

    private function acaoMarcarPago(?int $lancId, int $empresaId): bool
    {
        if (!$lancId) {
            return false;
        }
        $this->lancamentos->save(['id' => $lancId, 'status' => 'pago'], $empresaId);
        $this->lancamentos->invalidarCacheDashboard($empresaId);
        return true;
    }

    private function acaoCriarLancamento(int $empresaId, array $params): bool
    {
        if (empty($params['conta_id']) || empty($params['valor'])) {
            return false;
        }
        $this->lancamentos->save([
            'empresa_id' => $empresaId,
            'conta_id' => (int) $params['conta_id'],
            'tipo' => $params['tipo'] ?? 'despesa',
            'descricao' => $params['descricao'] ?? 'Lançamento automático',
            'valor' => (float) $params['valor'],
            'data_lancamento' => date('Y-m-d'),
            'status' => $params['status'] ?? 'pendente',
        ], $empresaId);
        return true;
    }
}
