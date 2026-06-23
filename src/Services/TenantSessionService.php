<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Session;
use App\Models\Empresa;
use App\Policies\SuperAdminPolicy;

/** Mantém empresas e loja ativa sincronizadas na sessão. */
final class TenantSessionService
{
    public function sincronizar(): void
    {
        $usuarioId = (int) Session::get('usuario_id');
        if ($usuarioId <= 0) {
            return;
        }

        $model = new Empresa();
        $lista = $model->listarPorUsuario($usuarioId);
        Session::set('empresas', $lista);

        $empresaId = (int) Session::get('empresa_id');
        if ($empresaId > 0) {
            $atual = null;
            foreach ($lista as $e) {
                if ((int) $e['id'] === $empresaId) {
                    $atual = $e;
                    break;
                }
            }
            if ($atual === null) {
                Session::forget('empresa_id');
                Session::forget('empresa');
                $empresaId = 0;
            } else {
                Session::set('empresa', $atual);
            }
        }

        if ($empresaId > 0) {
            return;
        }

        if (empty($lista)) {
            return;
        }

        $plan = new PlanService();
        $auth = new AuthService();
        foreach ($lista as $e) {
            if ($plan->empresaOperacional($e) || SuperAdminPolicy::isSuperadmin()) {
                $auth->definirEmpresaAtiva((int) $e['id'], $lista);
                return;
            }
        }
    }

    public function urlNavegacaoSemEmpresa(): string
    {
        if ((int) Session::get('empresa_id') > 0) {
            return '';
        }

        $empresas = Session::get('empresas', []);
        return empty($empresas) ? '/empresas/criar' : '/empresas';
    }

    public function temEmpresaAtiva(): bool
    {
        return (int) Session::get('empresa_id') > 0;
    }
}
