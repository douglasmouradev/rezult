<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Services\PlanService;

/** Itens de menu com feature de plano e badge opcional. */
final class NavHelper
{
    /** @return list<array{0: string, 1: string, 2: string, 3: ?string, 4: ?string}> */
    public static function navMain(): array
    {
        return [
            ['/dashboard', 'chart-line', 'Dashboard', null, null],
            ['/lancamentos', 'receipt', 'Lançamentos', null, null],
            ['/contas-a-pagar', 'list-checks', 'Contas a pagar', null, null],
            ['/contas-a-receber', 'trend-up', 'Contas a receber', null, null],
            ['/cobrancas', 'invoice', 'Cobranças', 'cobrancas', 'Pro'],
            ['/conciliacoes', 'bank', 'Conciliação', 'conciliacao', 'Pro'],
            ['/contas', 'wallet', 'Contas', null, null],
        ];
    }

    /** @return list<array{0: string, 1: string, 2: string, 3: ?string, 4: ?string}> */
    public static function navAvancado(): array
    {
        return [
            ['/notas-fiscais', 'currency-circle-dollar', 'NFS-e', 'nfse', 'Business'],
            ['/automacoes', 'lightning', 'Automações', 'automacoes', 'Pro'],
            ['/assistente', 'brain', 'Assistente IA', 'assistente_ia', 'Business'],
        ];
    }

    /** @return list<array{0: string, 1: string, 2: string, 3: ?string, 4: ?string}> */
    public static function navConfig(): array
    {
        return [
            ['/categorias', 'tag', 'Categorias', null, null],
            ['/metas', 'target', 'Metas', null, null],
            ['/orcamentos', 'chart-bar', 'Orçamento', null, null],
            ['/centros-custo', 'folders', 'Centros de custo', null, null],
            ['/contatos', 'address-book', 'Contatos', null, null],
            ['/integracoes', 'plugs-connected', 'Integrações', 'integracoes', 'Pro'],
            ['/empresas', 'buildings', 'Empresas', null, null],
            ['/plano', 'crown', 'Meu plano', null, null],
        ];
    }

    public static function temFeature(int $empresaId, ?string $feature): bool
    {
        if ($feature === null || $empresaId <= 0) {
            return true;
        }

        return (new PlanService())->temFeature($empresaId, $feature);
    }

    public static function badgePlano(?string $feature): ?string
    {
        return match ($feature) {
            'nfse', 'assistente_ia', 'open_finance' => 'Business',
            'api', 'webhooks', 'automacoes', 'integracoes', 'cobrancas', 'conciliacao' => 'Pro',
            default => null,
        };
    }
}
