<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Helpers\Session;
use App\Models\Categoria;
use App\Models\Cobranca;
use App\Models\Conta;
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
        try {
            $id = (new LancamentoService())->salvar($eid, $input);
            View::json(['data' => ['id' => $id]], 201);
        } catch (\InvalidArgumentException $e) {
            View::json(['errors' => [['message' => $e->getMessage()]]], 422);
        }
    }

    public function contas(): void
    {
        $eid = (int) Session::get('empresa_id');
        $items = (new Conta())->findAll($eid, 'nome ASC');
        View::json(['data' => $items]);
    }

    public function categorias(): void
    {
        $eid = (int) Session::get('empresa_id');
        $items = (new Categoria())->findAll($eid, 'nome ASC');
        View::json(['data' => $items]);
    }

    public function cobrancas(): void
    {
        $eid = (int) Session::get('empresa_id');
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = (new Cobranca())->listar($eid, $_GET, $page, 50);
        View::json(['data' => $result['items'], 'meta' => ['page' => $result['page'], 'pages' => $result['pages'], 'total' => $result['total']]]);
    }
}
