<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\View;
use App\Helpers\Session;
use App\Models\Empresa;

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
        $next();
    }
}
