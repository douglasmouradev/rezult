<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\View;
use App\Helpers\Session;
use App\Policies\TenantPolicy;
use App\Services\PlanService;
use Closure;

/** Verificação opcional de limites do plano (empresa ou convite). */
final class PlanMiddleware
{
    public function __construct(private string $acao = 'empresa') {}

    public function __invoke(Closure $next): void
    {
        $plan = new PlanService();

        if ($this->acao === 'empresa' && !$plan->podeCriarEmpresa(TenantPolicy::usuarioId())) {
            Session::flash('error', 'Limite de empresas do seu plano foi atingido. Faça upgrade para criar mais.');
            View::redirect('/empresas');
        }

        if ($this->acao === 'convite') {
            $empresaId = $this->empresaIdDaRota();
            if ($empresaId > 0 && !$plan->podeConvidar($empresaId)) {
                Session::flash('error', 'Limite de usuários do plano desta empresa foi atingido.');
                View::redirect('/empresas');
            }
        }

        $next();
    }

    private function empresaIdDaRota(): int
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        if (preg_match('#/empresas/(\d+)/convidar#', $path, $m)) {
            return (int) $m[1];
        }

        return 0;
    }
}
