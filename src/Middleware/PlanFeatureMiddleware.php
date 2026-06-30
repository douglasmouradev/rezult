<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\View;
use App\Helpers\Session;
use App\Policies\TenantPolicy;
use App\Services\PlanService;
use Closure;

/** Bloqueia rotas quando o plano da empresa não inclui a feature. */
final class PlanFeatureMiddleware
{
    public function __construct(private string $feature) {}

    public function __invoke(Closure $next): void
    {
        $empresaId = TenantPolicy::empresaId();
        $plan = new PlanService();

        if (!$plan->temFeature($empresaId, $this->feature)) {
            Session::flash(
                'error',
                'Recurso não disponível no plano ' . $plan->planoLabel($plan->planoEmpresa($empresaId))
                . '. Faça upgrade em Meu plano.'
            );
            View::redirect('/plano');
        }

        $next();
    }
}
