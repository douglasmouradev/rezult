<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Helpers\Session;
use App\Helpers\Upload;
use App\Models\Conciliacao;
use App\Models\Conta;
use App\Models\Lancamento;
use App\Services\ConciliacaoService;

final class ConciliacaoController
{
    public function __construct(
        private Conciliacao $model = new Conciliacao(),
        private ConciliacaoService $service = new ConciliacaoService(),
        private Conta $contas = new Conta(),
        private Lancamento $lancamentos = new Lancamento(),
    ) {}

    private function eid(): int
    {
        return (int) Session::get('empresa_id');
    }

    public function index(): void
    {
        View::render('conciliacoes/index', [
            'title' => 'Conciliação bancária',
            'lista' => $this->model->listar($this->eid()),
            'contas' => $this->contas->findAll($this->eid(), 'nome ASC'),
        ]);
    }

    public function importar(): void
    {
        $eid = $this->eid();
        $contaId = (int) ($_POST['conta_id'] ?? 0);
        if (empty($_FILES['arquivo']['tmp_name'])) {
            Session::flash('error', 'Envie um arquivo CSV ou OFX.');
            View::redirect('/conciliacoes');
        }

        try {
            $file = Upload::validateImport($_FILES['arquivo']);
        } catch (\InvalidArgumentException $e) {
            Session::flash('error', $e->getMessage());
            View::redirect('/conciliacoes');
        }

        $id = match ($file['ext']) {
            'ofx' => $this->service->importarOfx($eid, $contaId, $file['path']),
            'csv' => $this->service->importarCsv($eid, $contaId, $file['path']),
            default => null,
        };

        if ($id === null) {
            Session::flash('error', 'Não foi possível importar o extrato.');
            View::redirect('/conciliacoes');
        }

        Session::flash('success', 'Extrato importado. Revise os itens pendentes.');
        View::redirect('/conciliacoes/' . $id);
    }

    public function ver(int $id): void
    {
        $eid = $this->eid();
        $conc = $this->model->find($id, $eid);
        if (!$conc) {
            View::redirect('/conciliacoes');
        }
        View::render('conciliacoes/ver', [
            'title' => 'Conciliação',
            'conciliacao' => $conc,
            'itens' => $this->model->itens($id),
            'lancamentos' => $this->lancamentos->listarFiltrado($eid, ['status' => 'pago'], 1, 100)['items'],
            'categorias' => (new \App\Models\Categoria())->findAll($eid, 'nome ASC'),
        ]);
    }

    public function conciliar(int $id): void
    {
        try {
            $this->service->conciliarManual(
                (int) $_POST['item_id'],
                (int) $_POST['lancamento_id'],
                $this->eid(),
                $id
            );
            Session::flash('success', 'Item conciliado.');
        } catch (\InvalidArgumentException $e) {
            Session::flash('error', $e->getMessage());
        }
        View::redirect('/conciliacoes/' . $id);
    }

    public function ignorar(int $id): void
    {
        $this->service->ignorarItem((int) $_POST['item_id'], $this->eid(), $id);
        Session::flash('success', 'Item ignorado.');
        View::redirect('/conciliacoes/' . $id);
    }

    public function criarLancamento(int $id): void
    {
        $eid = $this->eid();
        $lancId = $this->service->criarLancamentoDoItem(
            (int) $_POST['item_id'],
            $eid,
            $id,
            (int) ($_POST['categoria_id'] ?? 0) ?: null
        );
        Session::flash('success', $lancId ? 'Lançamento criado e conciliado.' : 'Não foi possível criar o lançamento.');
        View::redirect('/conciliacoes/' . $id);
    }
}
