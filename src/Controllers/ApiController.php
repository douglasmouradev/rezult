<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Helpers\Session;
use App\Models\Lancamento;
use App\Services\LancamentoService;

final class ApiController
{
    public function lancamentos(): void
    {
        $eid = (int) Session::get('empresa_id');
        $result = (new Lancamento())->listarFiltrado($eid, $_GET, max(1, (int) ($_GET['page'] ?? 1)), 50);
        View::json(['data' => $result['items'], 'meta' => ['page' => $result['page'], 'pages' => $result['pages']]]);
    }

    public function criarLancamento(): void
    {
        $eid = (int) Session::get('empresa_id');
        $input = json_decode(file_get_contents('php://input') ?: '{}', true) ?: $_POST;
        $id = (new LancamentoService())->salvar($eid, $input);
        View::json(['id' => $id], 201);
    }
}
