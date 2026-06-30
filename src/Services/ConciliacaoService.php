<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Models\Conciliacao;
use App\Models\Lancamento;
use App\Policies\TenantPolicy;

final class ConciliacaoService
{
    public function __construct(
        private Conciliacao $model = new Conciliacao(),
        private Lancamento $lancamentos = new Lancamento(),
    ) {}

    public function importarCsv(int $empresaId, int $contaId, string $path): int
    {
        if (!TenantPolicy::contaDaEmpresa($contaId, $empresaId)) {
            throw new \InvalidArgumentException('Conta inválida.');
        }

        $concId = $this->model->save([
            'empresa_id' => $empresaId,
            'conta_id' => $contaId,
            'arquivo' => basename($path),
            'status' => 'processando',
        ], $empresaId);

        $handle = fopen($path, 'r');
        if (!$handle) {
            throw new \RuntimeException('Não foi possível ler o arquivo.');
        }

        $linhas = 0;
        $conciliados = 0;
        $pdo = App::pdo();
        $ins = $pdo->prepare(
            'INSERT INTO conciliacao_itens (conciliacao_id, data_movimento, descricao, valor, tipo_movimento, status, lancamento_id)
             VALUES (:c, :d, :desc, :v, :t, :s, :l)'
        );

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (count($row) < 3) {
                continue;
            }
            $data = $this->parseData(trim($row[0]));
            $desc = trim($row[1]);
            $valor = abs((float) str_replace(['.', ','], ['', '.'], preg_replace('/[^\d,.-]/', '', $row[2])));
            if ($valor <= 0 || !$data) {
                continue;
            }
            $tipo = (isset($row[3]) && strtolower(trim($row[3])) === 'debito')
                || (isset($row[2]) && str_contains($row[2], '-'))
                ? 'debito' : 'credito';

            $lancId = $this->buscarLancamento($empresaId, $contaId, $data, $valor, $tipo, $desc);
            $status = $lancId ? 'conciliado' : 'pendente';
            if ($lancId) {
                $conciliados++;
                $pdo->prepare('UPDATE lancamentos SET conciliado_em = NOW() WHERE id = :id AND empresa_id = :e')
                    ->execute(['id' => $lancId, 'e' => $empresaId]);
            }

            $ins->execute([
                'c' => $concId,
                'd' => $data,
                'desc' => $desc,
                'v' => $valor,
                't' => $tipo,
                's' => $status,
                'l' => $lancId,
            ]);
            $linhas++;
        }
        fclose($handle);

        $this->model->save([
            'id' => $concId,
            'status' => $conciliados === $linhas && $linhas > 0 ? 'concluida' : 'pendente',
            'total_itens' => $linhas,
            'conciliados' => $conciliados,
        ], $empresaId);

        AuditoriaService::registrar('conciliacao_importada', 'conciliacao', $concId);
        (new AutomacaoService())->aplicarGatilho($empresaId, 'import_csv');
        return $concId;
    }

    public function importarOfx(int $empresaId, int $contaId, string $path): int
    {
        if (!TenantPolicy::contaDaEmpresa($contaId, $empresaId)) {
            throw new \InvalidArgumentException('Conta inválida.');
        }

        $concId = $this->model->save([
            'empresa_id' => $empresaId,
            'conta_id' => $contaId,
            'arquivo' => basename($path),
            'status' => 'processando',
        ], $empresaId);

        $transacoes = (new OfxParser())->parse($path);
        $linhas = 0;
        $conciliados = 0;
        $pdo = App::pdo();
        $ins = $pdo->prepare(
            'INSERT INTO conciliacao_itens (conciliacao_id, data_movimento, descricao, valor, tipo_movimento, status, lancamento_id)
             VALUES (:c, :d, :desc, :v, :t, :s, :l)'
        );

        foreach ($transacoes as $tx) {
            $valor = abs((float) $tx['amount']);
            $data = $tx['date'];
            if ($valor <= 0 || !$data) {
                continue;
            }
            $tipo = (float) $tx['amount'] < 0 ? 'debito' : 'credito';

            $lancId = $this->buscarLancamento($empresaId, $contaId, $data, $valor, $tipo, (string) $tx['description']);
            $status = $lancId ? 'conciliado' : 'pendente';
            if ($lancId) {
                $conciliados++;
                $pdo->prepare('UPDATE lancamentos SET conciliado_em = NOW() WHERE id = :id AND empresa_id = :e')
                    ->execute(['id' => $lancId, 'e' => $empresaId]);
            }

            $ins->execute([
                'c' => $concId,
                'd' => $data,
                'desc' => $tx['description'],
                'v' => $valor,
                't' => $tipo,
                's' => $status,
                'l' => $lancId,
            ]);
            $linhas++;
        }

        $this->model->save([
            'id' => $concId,
            'status' => $conciliados === $linhas && $linhas > 0 ? 'concluida' : 'pendente',
            'total_itens' => $linhas,
            'conciliados' => $conciliados,
        ], $empresaId);

        AuditoriaService::registrar('conciliacao_importada', 'conciliacao', $concId);
        (new AutomacaoService())->aplicarGatilho($empresaId, 'import_csv');
        return $concId;
    }

    public function conciliarManual(int $itemId, int $lancamentoId, int $empresaId, int $conciliacaoId): void
    {
        $conc = $this->model->find($conciliacaoId, $empresaId);
        if (!$conc) {
            TenantPolicy::forbidden();
        }

        $stmt = App::pdo()->prepare(
            'SELECT * FROM conciliacao_itens WHERE id = :i AND conciliacao_id = :c AND status = \'pendente\' LIMIT 1'
        );
        $stmt->execute(['i' => $itemId, 'c' => $conciliacaoId]);
        $item = $stmt->fetch();
        if (!$item) {
            throw new \InvalidArgumentException('Item de conciliação inválido.');
        }

        $lanc = $this->lancamentos->find($lancamentoId, $empresaId);
        if (!$lanc) {
            TenantPolicy::forbidden();
        }

        $tipoEsperado = $item['tipo_movimento'] === 'credito' ? 'receita' : 'despesa';
        if ($lanc['tipo'] !== $tipoEsperado) {
            throw new \InvalidArgumentException('Tipo do lançamento não corresponde ao item do extrato.');
        }
        if (abs((float) $lanc['valor'] - (float) $item['valor']) > 0.01) {
            throw new \InvalidArgumentException('Valor do lançamento não corresponde ao item do extrato.');
        }
        if ((int) $lanc['conta_id'] !== (int) $conc['conta_id']) {
            throw new \InvalidArgumentException('Lançamento pertence a outra conta.');
        }

        App::pdo()->prepare(
            'UPDATE conciliacao_itens SET lancamento_id = :l, status = \'conciliado\' WHERE id = :i AND conciliacao_id = :c'
        )->execute(['l' => $lancamentoId, 'i' => $itemId, 'c' => $conciliacaoId]);

        App::pdo()->prepare('UPDATE lancamentos SET conciliado_em = NOW() WHERE id = :l AND empresa_id = :e')
            ->execute(['l' => $lancamentoId, 'e' => $empresaId]);

        $this->atualizarTotais($conciliacaoId, $empresaId);
    }

    public function ignorarItem(int $itemId, int $empresaId, int $conciliacaoId): void
    {
        $conc = $this->model->find($conciliacaoId, $empresaId);
        if (!$conc) {
            TenantPolicy::forbidden();
        }
        App::pdo()->prepare(
            'UPDATE conciliacao_itens SET status = \'ignorado\' WHERE id = :i AND conciliacao_id = :c'
        )->execute(['i' => $itemId, 'c' => $conciliacaoId]);
        $this->atualizarTotais($conciliacaoId, $empresaId);
    }

    public function criarLancamentoDoItem(int $itemId, int $empresaId, int $conciliacaoId, ?int $categoriaId): ?int
    {
        $conc = $this->model->find($conciliacaoId, $empresaId);
        if (!$conc) {
            TenantPolicy::forbidden();
        }
        $stmt = App::pdo()->prepare('SELECT * FROM conciliacao_itens WHERE id = :i AND conciliacao_id = :c AND status = \'pendente\'');
        $stmt->execute(['i' => $itemId, 'c' => $conciliacaoId]);
        $item = $stmt->fetch();
        if (!$item) {
            return null;
        }

        $tipo = $item['tipo_movimento'] === 'credito' ? 'receita' : 'despesa';
        $lancId = $this->lancamentos->save([
            'empresa_id' => $empresaId,
            'conta_id' => (int) $conc['conta_id'],
            'categoria_id' => $categoriaId,
            'tipo' => $tipo,
            'descricao' => $item['descricao'],
            'valor' => (float) $item['valor'],
            'data_lancamento' => $item['data_movimento'],
            'status' => 'pago',
            'conciliado_em' => date('Y-m-d H:i:s'),
        ], $empresaId);

        App::pdo()->prepare(
            'UPDATE conciliacao_itens SET lancamento_id = :l, status = \'conciliado\' WHERE id = :i'
        )->execute(['l' => $lancId, 'i' => $itemId]);
        $this->atualizarTotais($conciliacaoId, $empresaId);
        $this->lancamentos->invalidarCacheDashboard($empresaId);
        return $lancId;
    }

    private function buscarLancamento(int $empresaId, int $contaId, string $data, float $valor, string $tipo, string $descricao = ''): ?int
    {
        $tipoLanc = $tipo === 'credito' ? 'receita' : 'despesa';
        $stmt = App::pdo()->prepare(
            "SELECT id, descricao FROM lancamentos
             WHERE empresa_id = :e AND conta_id = :c AND tipo = :t
             AND ABS(valor - :v) < 0.01 AND conciliado_em IS NULL
             AND data_lancamento BETWEEN DATE_SUB(:d, INTERVAL 3 DAY) AND DATE_ADD(:d, INTERVAL 3 DAY)"
        );
        $stmt->execute(['e' => $empresaId, 'c' => $contaId, 't' => $tipoLanc, 'v' => $valor, 'd' => $data]);
        $candidatos = $stmt->fetchAll();
        if ($candidatos === []) {
            return null;
        }

        $descNorm = $this->normalizarDescricao($descricao);
        if ($descNorm !== '') {
            foreach ($candidatos as $row) {
                $lancDesc = $this->normalizarDescricao((string) ($row['descricao'] ?? ''));
                if ($lancDesc !== '' && similar_text($descNorm, $lancDesc) / max(strlen($descNorm), strlen($lancDesc), 1) >= 0.45) {
                    return (int) $row['id'];
                }
            }
        }

        return (int) $candidatos[0]['id'];
    }

    private function normalizarDescricao(string $texto): string
    {
        $t = mb_strtolower(trim($texto));
        $t = preg_replace('/\s+/', ' ', $t) ?? $t;

        return $t;
    }

    private function atualizarTotais(int $concId, int $empresaId): void
    {
        $stmt = App::pdo()->prepare(
            'SELECT COUNT(*) AS t, SUM(status = \'conciliado\') AS c FROM conciliacao_itens WHERE conciliacao_id = :id'
        );
        $stmt->execute(['id' => $concId]);
        $r = $stmt->fetch();
        $this->model->save([
            'id' => $concId,
            'total_itens' => (int) ($r['t'] ?? 0),
            'conciliados' => (int) ($r['c'] ?? 0),
            'status' => ((int) ($r['t'] ?? 0)) === (int) ($r['c'] ?? 0) && (int) ($r['t'] ?? 0) > 0 ? 'concluida' : 'pendente',
        ], $empresaId);
    }

    private function parseData(string $raw): ?string
    {
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $raw, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return $raw;
        }
        return null;
    }
}
