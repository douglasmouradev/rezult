<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Models\Lancamento;
use App\Services\DashboardService;

final class AssistenteService
{
    public function __construct(
        private Lancamento $lancamentos = new Lancamento(),
        private DashboardService $dashboard = new DashboardService(),
    ) {}

    public function responder(int $empresaId, string $pergunta): string
    {
        $p = mb_strtolower(trim($pergunta));

        if ($this->openAiDisponivel()) {
            $ia = $this->responderOpenAi($empresaId, $pergunta);
            if ($ia !== null) {
                return $ia;
            }
        }

        if (preg_match('/lucro|resultado/i', $p)) {
            return $this->resumoLucro($empresaId);
        }
        if (preg_match('/saldo/i', $p)) {
            $d = $this->dashboard->dados($empresaId);
            return 'O saldo atual consolidado é de **R$ ' . number_format((float) ($d['saldo_total'] ?? 0), 2, ',', '.') . '**.';
        }
        if (preg_match('/receita|faturamento/i', $p)) {
            $d = $this->dashboard->dados($empresaId);
            return 'As receitas do mês somam **R$ ' . number_format((float) ($d['receitas_mes'] ?? 0), 2, ',', '.') . '**.';
        }
        if (preg_match('/despesa|gasto/i', $p)) {
            $d = $this->dashboard->dados($empresaId);
            return 'As despesas do mês somam **R$ ' . number_format((float) ($d['despesas_mes'] ?? 0), 2, ',', '.') . '**.';
        }
        if (preg_match('/venc|atrasad|pagar/i', $p)) {
            return $this->resumoVencimentos($empresaId, 'despesa');
        }
        if (preg_match('/receber|inadimpl/i', $p)) {
            return $this->resumoVencimentos($empresaId, 'receita');
        }
        if (preg_match('/dre|relatório/i', $p)) {
            return 'Acesse **Relatórios → DRE** no menu para ver receita bruta, custos e lucro líquido do período.';
        }

        return 'Posso ajudar com: lucro do mês, saldo, receitas, despesas, contas a pagar/receber e vencimentos. Reformule sua pergunta ou acesse os relatórios no menu.';
    }

    private function resumoLucro(int $empresaId): string
    {
        $stmt = App::pdo()->prepare(
            "SELECT
              COALESCE(SUM(CASE WHEN tipo='receita' AND status='pago' THEN valor END),0) AS rec,
              COALESCE(SUM(CASE WHEN tipo='despesa' AND status='pago' THEN valor END),0) AS des
             FROM lancamentos WHERE empresa_id=:e AND MONTH(data_lancamento)=MONTH(CURDATE()) AND YEAR(data_lancamento)=YEAR(CURDATE())"
        );
        $stmt->execute(['e' => $empresaId]);
        $r = $stmt->fetch();
        $lucro = (float) ($r['rec'] ?? 0) - (float) ($r['des'] ?? 0);
        return 'No mês atual, seu lucro líquido (receitas − despesas pagas) é de **R$ ' . number_format($lucro, 2, ',', '.') . '**.';
    }

    private function resumoVencimentos(int $empresaId, string $tipo): string
    {
        $res = $this->lancamentos->resumoFluxo($empresaId, $tipo);
        $label = $tipo === 'receita' ? 'a receber' : 'a pagar';
        return sprintf(
            'Você tem **%d** título(s) %s pendente(s), totalizando **R$ %s**. Atrasados: **%d** (R$ %s). Próximos 7 dias: **R$ %s**.',
            (int) ($res['qtd_pendente'] ?? 0),
            $label,
            number_format((float) ($res['total_pendente'] ?? 0), 2, ',', '.'),
            (int) ($res['qtd_atrasado'] ?? 0),
            number_format((float) ($res['total_atrasado'] ?? 0), 2, ',', '.'),
            number_format((float) ($res['total_semana'] ?? 0), 2, ',', '.')
        );
    }

    private function openAiDisponivel(): bool
    {
        return (bool) ($_ENV['OPENAI_API_KEY'] ?? '');
    }

    private function responderOpenAi(int $empresaId, string $pergunta): ?string
    {
        $ctx = $this->dashboard->dados($empresaId);
        $payload = [
            'model' => $_ENV['OPENAI_MODEL'] ?? 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => 'Você é assistente financeiro do Rezult. Responda em português, de forma breve, com valores em R$.'],
                ['role' => 'user', 'content' => "Contexto: saldo={$ctx['saldo_total']}, receitas_mes={$ctx['receitas_mes']}, despesas_mes={$ctx['despesas_mes']}. Pergunta: {$pergunta}"],
            ],
            'max_tokens' => 300,
        ];

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . ($_ENV['OPENAI_API_KEY'] ?? ''),
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 15,
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);
        if (!$raw) {
            return null;
        }
        $json = json_decode($raw, true);
        return $json['choices'][0]['message']['content'] ?? null;
    }
}
