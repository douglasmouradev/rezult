<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Helpers\Session;
use App\Models\Conta;
use App\Models\Lancamento;
use App\Policies\TenantPolicy;

final class ContaReceberController
{
    public function __construct(
        private Lancamento $model = new Lancamento(),
        private Conta $contas = new Conta(),
    ) {}

    public function index(): void
    {
        $eid = (int) Session::get('empresa_id');
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $filtros = [
            'tipo' => 'receita',
            'status' => $_GET['status'] ?? 'pendente',
            'conta_id' => $_GET['conta_id'] ?? '',
            'parceiro' => $_GET['parceiro'] ?? '',
            'vencimento_filtro' => $_GET['vencimento'] ?? '',
            'ordenar_vencimento' => true,
        ];

        View::render('financeiro/fluxo', [
            'title' => 'Contas a receber',
            'tipo' => 'receita',
            'basePath' => '/contas-a-receber',
            'criarUrl' => '/lancamentos/criar?tipo=receita',
            'resumo' => $this->model->resumoFluxo($eid, 'receita'),
            'resultado' => $this->model->listarFiltrado($eid, $filtros, $page),
            'filtros' => $filtros,
            'contas' => $this->contas->findAll($eid, 'nome ASC'),
        ]);
    }

    public function receberLote(): void
    {
        TenantPolicy::abortUnlessCanApproveLancamento();
        $eid = (int) Session::get('empresa_id');
        $ids = array_map('intval', $_POST['ids'] ?? []);
        $n = $this->model->marcarPagosEmLote($ids, $eid);
        Session::flash('success', "{$n} título(s) marcado(s) como recebido.");
        View::redirect('/contas-a-receber');
    }
}
