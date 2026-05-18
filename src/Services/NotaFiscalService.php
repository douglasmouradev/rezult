<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Sanitize;
use App\Models\Lancamento;
use App\Models\NotaFiscal;
use App\Policies\TenantPolicy;

final class NotaFiscalService
{
    public function __construct(
        private NotaFiscal $model = new NotaFiscal(),
        private Lancamento $lancamentos = new Lancamento(),
    ) {}

    public function salvar(int $empresaId, array $input, ?int $id = null): int
    {
        $data = [
            'empresa_id' => $empresaId,
            'tomador_nome' => Sanitize::raw($input['tomador_nome']),
            'tomador_documento' => preg_replace('/\D/', '', $input['tomador_documento'] ?? ''),
            'descricao_servico' => Sanitize::raw($input['descricao_servico']),
            'valor' => abs(Sanitize::money($input['valor'] ?? '0')),
            'status' => $input['status'] ?? 'rascunho',
            'lancamento_id' => !empty($input['lancamento_id']) ? (int) $input['lancamento_id'] : null,
        ];
        if ($id) {
            $data['id'] = $id;
        }
        return $this->model->save($data, $empresaId);
    }

    public function emitir(int $id, int $empresaId): void
    {
        $nf = $this->model->find($id, $empresaId);
        if (!$nf) {
            TenantPolicy::forbidden();
        }

        $numero = str_pad((string) $id, 6, '0', STR_PAD_LEFT);
        $codigo = strtoupper(substr(hash('sha256', $empresaId . '-' . $id . '-' . time()), 0, 8));

        $this->model->save([
            'id' => $id,
            'status' => 'emitida',
            'numero' => $numero,
            'codigo_verificacao' => $codigo,
            'emitida_em' => date('Y-m-d H:i:s'),
        ], $empresaId);

        if (empty($nf['lancamento_id'])) {
            $stmt = \App\Core\App::pdo()->prepare('SELECT id FROM contas WHERE empresa_id = :e LIMIT 1');
            $stmt->execute(['e' => $empresaId]);
            $contaId = (int) ($stmt->fetchColumn() ?: 0);
            if ($contaId) {
                $lancId = $this->lancamentos->save([
                    'empresa_id' => $empresaId,
                    'conta_id' => $contaId,
                    'tipo' => 'receita',
                    'descricao' => 'NFS-e ' . $numero . ' — ' . $nf['tomador_nome'],
                    'parceiro' => $nf['tomador_nome'],
                    'valor' => $nf['valor'],
                    'data_lancamento' => date('Y-m-d'),
                    'status' => 'pendente',
                ], $empresaId);
                $this->model->save(['id' => $id, 'lancamento_id' => $lancId], $empresaId);
            }
        }

        AuditoriaService::registrar('nfse_emitida', 'nota_fiscal', $id);
    }
}
