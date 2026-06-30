<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\MailTemplate;
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

        $gateway = new GatewayService();
        $emitido = $gateway->emitir($empresaId, $c);

        $this->model->save([
            'id' => $id,
            'status' => 'emitida',
            'codigo_pix' => $emitido['codigo_pix'],
            'linha_digitavel' => $emitido['linha_digitavel'],
            'gateway_id' => $emitido['gateway_id'] ?? null,
            'gateway_provedor' => $emitido['gateway_provedor'] ?? null,
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

    public function cancelar(int $id, int $empresaId): void
    {
        $c = $this->model->find($id, $empresaId);
        if (!$c || in_array($c['status'], ['paga', 'cancelada'], true)) {
            return;
        }
        $this->model->save(['id' => $id, 'status' => 'cancelada'], $empresaId);
        if (!empty($c['lancamento_id'])) {
            $lanc = $this->lancamentos->find((int) $c['lancamento_id'], $empresaId);
            if ($lanc && $lanc['status'] === 'pendente') {
                $this->lancamentos->save(['id' => (int) $c['lancamento_id'], 'status' => 'cancelado'], $empresaId);
            }
        }
        AuditoriaService::registrar('cobranca_cancelada', 'cobranca', $id);
    }

    public function enviarEmail(int $id, int $empresaId): bool
    {
        $c = $this->model->find($id, $empresaId);
        if (!$c || empty($c['cliente_email']) || $c['status'] === 'cancelada') {
            return false;
        }
        $valor = number_format((float) $c['valor'], 2, ',', '.');
        $corpo = "Olá {$c['cliente_nome']},\n\n";
        $corpo .= "Segue sua cobrança: {$c['descricao']}\n";
        $corpo .= "Valor: R$ {$valor}\n";
        $corpo .= "Vencimento: " . date('d/m/Y', strtotime($c['vencimento'])) . "\n\n";
        if (!empty($c['codigo_pix'])) {
            $corpo .= "Pix copia e cola:\n{$c['codigo_pix']}\n\n";
        }
        if (!empty($c['linha_digitavel'])) {
            $corpo .= "Boleto: {$c['linha_digitavel']}\n\n";
        }
        $corpo .= "— Enviado pelo Rezult";
        $tpl = MailTemplate::cobranca($c['descricao'], $valor, $corpo);
        return (new MailService())->enviarTemplate($c['cliente_email'], $tpl);
    }

    public function marcarPaga(int $id, int $empresaId): void
    {
        $c = $this->model->find($id, $empresaId);
        if (!$c || $c['status'] === 'paga') {
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

        $atualizada = $this->model->find($id, $empresaId);
        if ($atualizada) {
            (new WebhookService())->dispatch('cobranca.paga', $empresaId, $atualizada);
        }
    }

    public function modoCobranca(int $empresaId): string
    {
        return (new GatewayService())->modoAtual($empresaId);
    }

    private function primeiraConta(int $empresaId): int
    {
        $stmt = \App\Core\App::pdo()->prepare('SELECT id FROM contas WHERE empresa_id = :e LIMIT 1');
        $stmt->execute(['e' => $empresaId]);
        return (int) ($stmt->fetchColumn() ?: 0);
    }

    public function marcarVencidas(): int
    {
        $stmt = \App\Core\App::pdo()->prepare(
            "UPDATE cobrancas SET status = 'vencida' WHERE status = 'emitida' AND vencimento < CURDATE()"
        );
        $stmt->execute();
        return $stmt->rowCount();
    }
}
