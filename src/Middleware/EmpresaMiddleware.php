<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\View;
use App\Helpers\Session;
use App\Models\Empresa;
use App\Policies\SuperAdminPolicy;
use App\Services\PlanService;

final class EmpresaMiddleware
{
    public function __invoke(callable $next): void
    {
        $empresaId = Session::get('empresa_id');
        if (!$empresaId) {
            View::redirect('/empresas/criar');
        }

        $usuarioId = (int) Session::get('usuario_id');
        $empresaModel = new Empresa();
        if (!$empresaModel->usuarioTemAcesso($usuarioId, (int) $empresaId)) {
            Session::forget('empresa_id');
            View::redirect('/empresas');
        }

        if (!SuperAdminPolicy::isSuperadmin()) {
            $empresa = $empresaModel->find((int) $empresaId);
            $plan = new PlanService();
            if ($empresa) {
                $motivo = $plan->motivoBloqueio($empresa);
                if ($motivo !== null) {
                    Session::forget('empresa_id');
                    Session::flash('error', $motivo);
                    View::redirect('/empresas');
                }
            }
        }

        $next();
    }
}
