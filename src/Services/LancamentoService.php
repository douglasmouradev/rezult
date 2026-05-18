<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Helpers\Sanitize;
use App\Helpers\Upload;
use App\Models\Conta;
use App\Models\Categoria;
use App\Models\Lancamento;
use App\Models\Meta;
use App\Policies\TenantPolicy;

final class LancamentoService
{
    public function __construct(
        private Lancamento $model = new Lancamento(),
        private Meta $metas = new Meta(),
        private Conta $contas = new Conta(),
        private Categoria $categorias = new Categoria(),
    ) {}

    public function salvar(int $empresaId, array $input, ?int $id = null): int
    {
        if ($id && !$this->model->find($id, $empresaId)) {
            TenantPolicy::forbidden();
        }

        $contaId = (int) $input['conta_id'];
        if (!TenantPolicy::contaDaEmpresa($contaId, $empresaId)) {
            throw new \InvalidArgumentException('Conta inválida.');
        }

        $categoriaId = !empty($input['categoria_id']) ? (int) $input['categoria_id'] : null;
        if ($categoriaId && !TenantPolicy::categoriaDaEmpresa($categoriaId, $empresaId)) {
            throw new \InvalidArgumentException('Categoria inválida.');
        }

        $metaId = !empty($input['meta_id']) ? (int) $input['meta_id'] : null;
        if ($metaId && !TenantPolicy::metaDaEmpresa($metaId, $empresaId)) {
            throw new \InvalidArgumentException('Meta inválida.');
        }

        $tags = array_filter(array_map('trim', explode(',', Sanitize::raw($input['tags'] ?? ''))));
        $data = [
            'empresa_id' => $empresaId,
            'conta_id' => $contaId,
            'categoria_id' => $categoriaId,
            'meta_id' => $metaId,
            'tipo' => $input['tipo'],
            'descricao' => Sanitize::raw($input['descricao']),
            'parceiro' => Sanitize::raw($input['parceiro'] ?? '') ?: null,
            'valor' => abs(Sanitize::money($input['valor'] ?? '0')),
            'data_lancamento' => $input['data_lancamento'],
            'data_vencimento' => $input['data_vencimento'] ?: null,
            'status' => $input['status'] ?? 'pendente',
            'recorrente' => !empty($input['recorrente']) ? 1 : 0,
            'frequencia' => !empty($input['recorrente']) ? ($input['frequencia'] ?? null) : null,
            'recorrente_proximo' => !empty($input['recorrente']) ? $this->proximaDataRecorrente($input['data_lancamento'], $input['frequencia'] ?? 'mensal') : null,
            'observacoes' => Sanitize::raw($input['observacoes'] ?? ''),
            'tags' => $tags ? json_encode($tags) : null,
        ];

        if (!empty($_FILES['anexo']['name'])) {
            $stored = Upload::store($_FILES['anexo'], 'comprovantes', $empresaId);
            if ($stored) {
                $data['anexo_url'] = $stored;
            }
        }

        if ($id) {
            $data['id'] = $id;
        }

        $lancId = $this->model->save($data, $empresaId);
        $this->model->invalidarCacheDashboard($empresaId);
        $this->syncMeta($metaId, $empresaId);
        AuditoriaService::registrar($id ? 'lancamento_atualizado' : 'lancamento_criado', 'lancamento', $lancId);
        (new AutomacaoService())->aplicarDescricao($empresaId, $data['descricao'], $lancId);

        return $lancId;
    }

    public function transferir(int $empresaId, int $origemId, int $destinoId, float $valor, string $data, string $descricao): void
    {
        if ($origemId === $destinoId) {
            throw new \InvalidArgumentException('Contas devem ser diferentes.');
        }
        if ($valor <= 0) {
            throw new \InvalidArgumentException('Valor inválido.');
        }
        if (!TenantPolicy::contaDaEmpresa($origemId, $empresaId) || !TenantPolicy::contaDaEmpresa($destinoId, $empresaId)) {
            TenantPolicy::forbidden();
        }

        $valor = abs($valor);
        $db = App::pdo();
        $db->beginTransaction();
        try {
            $saidaId = $this->model->save([
                'empresa_id' => $empresaId,
                'conta_id' => $origemId,
                'tipo' => 'transferencia',
                'descricao' => $descricao . ' (saída)',
                'valor' => -$valor,
                'data_lancamento' => $data,
                'status' => 'pago',
            ], $empresaId);

            $entradaId = $this->model->save([
                'empresa_id' => $empresaId,
                'conta_id' => $destinoId,
                'tipo' => 'transferencia',
                'descricao' => $descricao . ' (entrada)',
                'valor' => $valor,
                'data_lancamento' => $data,
                'status' => 'pago',
                'transferencia_par_id' => $saidaId,
            ], $empresaId);

            $db->prepare('UPDATE lancamentos SET transferencia_par_id = :entrada WHERE id = :saida AND empresa_id = :e')
                ->execute(['entrada' => $entradaId, 'saida' => $saidaId, 'e' => $empresaId]);

            $db->commit();
            $this->model->invalidarCacheDashboard($empresaId);
            AuditoriaService::registrar('transferencia', 'lancamento', $saidaId, ['valor' => $valor]);
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /** @return array{valid: array, invalid: array} */
    public function previewCsv(int $empresaId, string $path): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            return ['valid' => [], 'invalid' => []];
        }
        $valid = $invalid = [];
        $line = 0;
        fgetcsv($handle);
        while (($row = fgetcsv($handle, 0, ';')) !== false && $line < 500) {
            $line++;
            if (count($row) < 5) {
                $invalid[] = ['linha' => $line, 'erro' => 'Colunas insuficientes', 'dados' => $row];
                continue;
            }
            $contaId = (int) ($row[5] ?? $row[4] ?? 0);
            if (!TenantPolicy::contaDaEmpresa($contaId, $empresaId)) {
                $invalid[] = ['linha' => $line, 'erro' => 'Conta inválida', 'dados' => $row];
                continue;
            }
            if (!in_array($row[2] ?? '', ['receita', 'despesa'], true)) {
                $invalid[] = ['linha' => $line, 'erro' => 'Tipo inválido', 'dados' => $row];
                continue;
            }
            $valid[] = ['linha' => $line, 'data' => $row[0], 'descricao' => $row[1], 'tipo' => $row[2], 'valor' => $row[3], 'conta_id' => $contaId];
        }
        fclose($handle);
        return ['valid' => $valid, 'invalid' => $invalid];
    }

    public function importarCsv(int $empresaId, string $path): int
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            return 0;
        }
        $count = 0;
        $max = 500;
        fgetcsv($handle);
        $db = App::pdo();
        $db->beginTransaction();
        try {
            while (($row = fgetcsv($handle, 0, ';')) !== false && $count < $max) {
                if (count($row) < 5) {
                    continue;
                }
                $contaId = (int) $row[4];
                if (!TenantPolicy::contaDaEmpresa($contaId, $empresaId)) {
                    continue;
                }
                $this->model->save([
                    'empresa_id' => $empresaId,
                    'conta_id' => $contaId,
                    'tipo' => in_array($row[2], ['receita', 'despesa'], true) ? $row[2] : 'despesa',
                    'descricao' => Sanitize::raw($row[1]),
                    'valor' => abs((float) str_replace(',', '.', $row[3])),
                    'data_lancamento' => $row[0],
                    'status' => in_array($row[5] ?? '', ['pago', 'pendente', 'cancelado'], true) ? $row[5] : 'pendente',
                ], $empresaId);
                $count++;
            }
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            fclose($handle);
            throw $e;
        }
        fclose($handle);
        $this->model->invalidarCacheDashboard($empresaId);
        AuditoriaService::registrar('importacao_csv', 'lancamento', null, ['linhas' => $count]);
        (new AutomacaoService())->aplicarGatilho($empresaId, 'import_csv');
        return $count;
    }

    public function excluir(int $id, int $empresaId): void
    {
        TenantPolicy::abortUnlessCanDeleteLancamento();
        $lanc = $this->model->find($id, $empresaId);
        if (!$lanc) {
            TenantPolicy::forbidden();
        }
        if (!empty($lanc['transferencia_par_id'])) {
            $this->model->delete((int) $lanc['transferencia_par_id'], $empresaId);
        }
        $metaId = $lanc['meta_id'] ?? null;
        $this->model->delete($id, $empresaId);
        $this->model->invalidarCacheDashboard($empresaId);
        $this->syncMeta($metaId ? (int) $metaId : null, $empresaId);
        AuditoriaService::registrar('lancamento_excluido', 'lancamento', $id);
    }

    private function syncMeta(?int $metaId, int $empresaId): void
    {
        if ($metaId) {
            $this->metas->atualizarProgresso($metaId, $empresaId);
        }
    }

    private function proximaDataRecorrente(string $data, string $freq): string
    {
        $dt = new \DateTimeImmutable($data);
        $dt = match ($freq) {
            'semanal' => $dt->modify('+1 week'),
            'anual' => $dt->modify('+1 year'),
            default => $dt->modify('+1 month'),
        };
        return $dt->format('Y-m-d');
    }
}
