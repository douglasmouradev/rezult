<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\View;
use App\Helpers\Session;
use App\Models\Empresa;
use App\Policies\SuperAdminPolicy;
use App\Services\PlanService;
use App\Services\TenantSessionService;

final class EmpresaMiddleware
{
    public function __invoke(callable $next): void
    {
        (new TenantSessionService())->sincronizar();

        $empresaId = Session::get('empresa_id');
        if (!$empresaId) {
            $empresas = Session::get('empresas', []);
            $destino = !empty($empresas) ? '/empresas' : '/empresas/criar';

            if (!empty($empresas)) {
                Session::flash('error', 'Nenhuma loja ativa no momento. Verifique o status do plano em Empresas.');
            }
            View::redirect($destino);
        }

        $usuarioId = (int) Session::get('usuario_id');
        $empresaModel = new Empresa();
        if (!$empresaModel->usuarioTemAcesso($usuarioId, (int) $empresaId)) {
            Session::forget('empresa_id');
            Session::forget('empresa');
            View::redirect('/empresas');
        }

        if (!SuperAdminPolicy::isSuperadmin()) {
            $empresa = $empresaModel->find((int) $empresaId);
            $plan = new PlanService();
            if ($empresa) {
                $motivo = $plan->motivoBloqueio($empresa);
                if ($motivo !== null) {
                    Session::forget('empresa_id');
                    Session::forget('empresa');
                    Session::flash('error', $motivo);
                    View::redirect('/empresas');
                }
            }
        }

        $next();
    }
}
