<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Helpers\Sanitize;
use App\Helpers\Session;
use App\Helpers\Validator;
use App\Models\Conta;
use App\Services\LancamentoService;

final class ContaController
{
    public function __construct(
        private Conta $model = new Conta(),
        private LancamentoService $lancamentos = new LancamentoService(),
    ) {}

    private function empresaId(): int
    {
        return (int) Session::get('empresa_id');
    }

    public function index(): void
    {
        $eid = $this->empresaId();
        View::render('contas/index', [
            'title' => 'Contas',
            'contas' => $this->model->saldosPorEmpresa($eid),
        ]);
    }

    public function criarForm(): void
    {
        View::render('contas/form', ['title' => 'Nova conta', 'conta' => null]);
    }

    public function criar(): void
    {
        $eid = $this->empresaId();
        $this->model->save([
            'empresa_id' => $eid,
            'nome' => Sanitize::raw($_POST['nome']),
            'tipo' => $_POST['tipo'],
            'saldo_inicial' => Sanitize::money($_POST['saldo_inicial'] ?? '0'),
            'cor' => $_POST['cor'] ?? '#10b981',
        ]);
        Session::flash('success', 'Conta criada.');
        View::redirect('/contas');
    }

    public function editarForm(int $id): void
    {
        View::render('contas/form', [
            'title' => 'Editar conta',
            'conta' => $this->model->find($id, $this->empresaId()),
        ]);
    }

    public function editar(int $id): void
    {
        $eid = $this->empresaId();
        if (!$this->model->find($id, $eid)) {
            \App\Policies\TenantPolicy::forbidden();
        }
        $this->model->save([
            'id' => $id,
            'nome' => Sanitize::raw($_POST['nome']),
            'tipo' => $_POST['tipo'],
            'saldo_inicial' => Sanitize::money($_POST['saldo_inicial'] ?? '0'),
            'cor' => $_POST['cor'] ?? '#10b981',
            'ativo' => isset($_POST['ativo']) ? 1 : 0,
        ], $eid);
        Session::flash('success', 'Conta atualizada.');
        View::redirect('/contas');
    }

    public function extrato(int $id): void
    {
        $eid = $this->empresaId();
        View::render('contas/extrato', [
            'title' => 'Extrato',
            'conta' => $this->model->find($id, $eid),
            'movimentos' => $this->model->extrato($id, $eid, $_GET['de'] ?? null, $_GET['ate'] ?? null),
            'saldo' => $this->model->saldoAtual($id, $eid),
        ]);
    }

    public function transferirForm(): void
    {
        View::render('contas/transferir', [
            'title' => 'Transferência',
            'contas' => $this->model->findAll($this->empresaId(), 'nome ASC'),
        ]);
    }

    public function transferir(): void
    {
        try {
            $this->lancamentos->transferir(
            $this->empresaId(),
            (int) $_POST['origem_id'],
            (int) $_POST['destino_id'],
            Sanitize::money($_POST['valor']),
            $_POST['data'],
            Sanitize::raw($_POST['descricao'])
            );
            Session::flash('success', 'Transferência realizada.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        View::redirect('/contas');
    }
}
