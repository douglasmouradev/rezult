<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Helpers\Session;
use App\Models\NotaFiscal;
use App\Services\NotaFiscalService;

final class NotaFiscalController
{
    public function __construct(
        private NotaFiscal $model = new NotaFiscal(),
        private NotaFiscalService $service = new NotaFiscalService(),
    ) {}

    private function eid(): int
    {
        return (int) Session::get('empresa_id');
    }

    public function index(): void
    {
        View::render('notas-fiscais/index', [
            'title' => 'Notas fiscais (NFS-e)',
            'resultado' => $this->model->listar($this->eid(), max(1, (int) ($_GET['page'] ?? 1))),
        ]);
    }

    public function criarForm(): void
    {
        View::render('notas-fiscais/form', ['title' => 'Nova NFS-e', 'nota' => null]);
    }

    public function editarForm(int $id): void
    {
        View::render('notas-fiscais/form', [
            'title' => 'Editar NFS-e',
            'nota' => $this->model->find($id, $this->eid()),
        ]);
    }

    public function salvar(): void
    {
        $id = !empty($_POST['id']) ? (int) $_POST['id'] : null;
        $newId = $this->service->salvar($this->eid(), $_POST, $id);
        Session::flash('success', 'Nota fiscal salva.');
        View::redirect('/notas-fiscais/' . ($id ?: $newId));
    }

    public function ver(int $id): void
    {
        $n = $this->model->find($id, $this->eid());
        if (!$n) {
            View::redirect('/notas-fiscais');
        }
        View::render('notas-fiscais/ver', ['title' => 'NFS-e', 'nota' => $n]);
    }

    public function emitir(int $id): void
    {
        $this->service->emitir($id, $this->eid());
        Session::flash('success', 'NFS-e emitida (demonstração — integração prefeitura em evolução).');
        View::redirect('/notas-fiscais/' . $id);
    }
}
