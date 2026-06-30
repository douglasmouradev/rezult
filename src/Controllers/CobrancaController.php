<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Helpers\Session;
use App\Models\Cobranca;
use App\Models\Conta;
use App\Services\CobrancaService;

final class CobrancaController
{
    public function __construct(
        private Cobranca $model = new Cobranca(),
        private CobrancaService $service = new CobrancaService(),
        private Conta $contas = new Conta(),
    ) {}

    private function eid(): int
    {
        return (int) Session::get('empresa_id');
    }

    public function index(): void
    {
        $eid = $this->eid();
        View::render('cobrancas/index', [
            'title' => 'Cobranças',
            'resultado' => $this->model->listar($eid, ['status' => $_GET['status'] ?? ''], max(1, (int) ($_GET['page'] ?? 1))),
        ]);
    }

    public function criarForm(): void
    {
        $this->form(null);
    }

    public function editarForm(int $id): void
    {
        $this->form($this->model->find($id, $this->eid()));
    }

    private function form(?array $c): void
    {
        View::render('cobrancas/form', [
            'title' => $c ? 'Editar cobrança' : 'Nova cobrança',
            'cobranca' => $c,
            'contas' => $this->contas->findAll($this->eid(), 'nome ASC'),
        ]);
    }

    public function salvar(): void
    {
        $eid = $this->eid();
        $id = !empty($_POST['id']) ? (int) $_POST['id'] : null;
        $this->service->salvar($eid, $_POST, $id);
        Session::flash('success', 'Cobrança salva.');
        View::redirect('/cobrancas');
    }

    public function emitir(int $id): void
    {
        try {
            $this->service->emitir($id, $this->eid(), !empty($_POST['conta_id']) ? (int) $_POST['conta_id'] : null);
            Session::flash('success', 'Cobrança emitida.');
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }
        View::redirect('/cobrancas/' . $id);
    }

    public function ver(int $id): void
    {
        $c = $this->model->find($id, $this->eid());
        if (!$c) {
            View::redirect('/cobrancas');
        }
        View::render('cobrancas/ver', ['title' => 'Cobrança', 'cobranca' => $c, 'contas' => $this->contas->findAll($this->eid(), 'nome ASC')]);
    }

    public function marcarPaga(int $id): void
    {
        $this->service->marcarPaga($id, $this->eid());
        Session::flash('success', 'Cobrança marcada como paga.');
        View::redirect('/cobrancas/' . $id);
    }

    public function cancelar(int $id): void
    {
        $this->service->cancelar($id, $this->eid());
        Session::flash('success', 'Cobrança cancelada.');
        View::redirect('/cobrancas');
    }

    public function enviarEmail(int $id): void
    {
        $ok = $this->service->enviarEmail($id, $this->eid());
        Session::flash($ok ? 'success' : 'error', $ok ? 'Cobrança enviada por e-mail.' : 'Informe o e-mail do cliente.');
        View::redirect('/cobrancas/' . $id);
    }
}
