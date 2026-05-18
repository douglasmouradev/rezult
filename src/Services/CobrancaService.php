<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Sanitize;
use App\Models\Cobranca;
use App\Models\Lancamento;
use App\Policies\TenantPolicy;

final class CobrancaService
{
    public function __construct(
        private Cobranca $model = new Cobranca(),
        private Lancamento $lancamentos = new Lancamento(),
    ) {}

    public function salvar(int $empresaId, array $input, ?int $id = null): int
    {
        $data = [
            'empresa_id' => $empresaId,
            'cliente_nome' => Sanitize::raw($input['cliente_nome']),
            'cliente_email' => Sanitize::raw($input['cliente_email'] ?? '') ?: null,
            'descricao' => Sanitize::raw($input['descricao']),
            'valor' => abs(Sanitize::money($input['valor'] ?? '0')),
            'vencimento' => $input['vencimento'],
            'tipo' => in_array($input['tipo'] ?? '', ['pix', 'boleto'], true) ? $input['tipo'] : 'pix',
            'status' => $input['status'] ?? 'rascunho',
            'lancamento_id' => !empty($input['lancamento_id']) ? (int) $input['lancamento_id'] : null,
        ];

        if ($id) {
            $data['id'] = $id;
        }

        return $this->model->save($data, $empresaId);
    }

    public function emitir(int $id, int $empresaId, ?int $contaId = null): void
    {
        $c = $this->model->find($id, $empresaId);
        if (!$c) {
            TenantPolicy::forbidden();
        }

        $pix = $this->gerarCodigoPix($c);
        $boleto = $c['tipo'] === 'boleto' ? $this->gerarLinhaDigitavel($c) : null;

        $this->model->save([
            'id' => $id,
            'status' => 'emitida',
            'codigo_pix' => $pix,
            'linha_digitavel' => $boleto,
        ], $empresaId);

        if (empty($c['lancamento_id'])) {
            $lancId = $this->lancamentos->save([
                'empresa_id' => $empresaId,
                'conta_id' => $contaId ?: $this->primeiraConta($empresaId),
                'tipo' => 'receita',
                'descricao' => 'Cobrança: ' . $c['descricao'],
                'parceiro' => $c['cliente_nome'],
                'valor' => $c['valor'],
                'data_lancamento' => date('Y-m-d'),
                'data_vencimento' => $c['vencimento'],
                'status' => 'pendente',
            ], $empresaId);
            $this->model->save(['id' => $id, 'lancamento_id' => $lancId], $empresaId);
        }

        AuditoriaService::registrar('cobranca_emitida', 'cobranca', $id);
    }

    public function marcarPaga(int $id, int $empresaId): void
    {
        $c = $this->model->find($id, $empresaId);
        if (!$c) {
            return;
        }
        $this->model->save(['id' => $id, 'status' => 'paga'], $empresaId);
        if (!empty($c['lancamento_id'])) {
            $this->lancamentos->save([
                'id' => (int) $c['lancamento_id'],
                'status' => 'pago',
                'data_lancamento' => date('Y-m-d'),
            ], $empresaId);
            $this->lancamentos->invalidarCacheDashboard($empresaId);
        }
    }

    private function gerarCodigoPix(array $c): string
    {
        $valor = number_format((float) $c['valor'], 2, '.', '');
        return '00020126580014BR.GOV.BCB.PIX0136' . substr(md5($c['id'] . $c['cliente_nome']), 0, 32)
            . '520400005303986540' . str_pad(strlen($valor) + 4, 2, '0', STR_PAD_LEFT) . $valor
            . '5802BR5925REZULT COBRANCA DEMO6009SAO PAULO62070503***6304' . strtoupper(substr(md5((string) $c['id']), 0, 4));
    }

    private function gerarLinhaDigitavel(array $c): string
    {
        return sprintf(
            '23793.38128 %s %s %s %s',
            str_pad((string) ((int) ($c['valor'] * 100)), 10, '0', STR_PAD_LEFT),
            date('dmy', strtotime($c['vencimento'])),
            str_pad((string) $c['id'], 10, '0', STR_PAD_LEFT),
            substr(md5((string) $c['id']), 0, 14)
        );
    }

    private function primeiraConta(int $empresaId): int
    {
        $stmt = \App\Core\App::pdo()->prepare('SELECT id FROM contas WHERE empresa_id = :e LIMIT 1');
        $stmt->execute(['e' => $empresaId]);
        return (int) ($stmt->fetchColumn() ?: 0);
    }
}
