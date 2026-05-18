<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Helpers\Sanitize;
use App\Helpers\Session;
use App\Models\Categoria;

final class CategoriaController
{
    public function __construct(private Categoria $model = new Categoria()) {}

    public function index(): void
    {
        $eid = (int) Session::get('empresa_id');
        View::render('categorias/index', [
            'title' => 'Categorias',
            'categorias' => $this->model->findAll($eid, 'tipo, nome'),
        ]);
    }

    public function salvar(): void
    {
        $eid = (int) Session::get('empresa_id');
        $data = [
            'empresa_id' => $eid,
            'nome' => Sanitize::raw($_POST['nome']),
            'tipo' => $_POST['tipo'],
            'cor' => $_POST['cor'] ?? '#6366f1',
            'icone' => Sanitize::raw($_POST['icone'] ?? ''),
        ];
        if (!empty($_POST['id'])) {
            $data['id'] = (int) $_POST['id'];
            if (!$this->model->find((int) $data['id'], $eid)) {
                \App\Policies\TenantPolicy::forbidden();
            }
        }
        if (!in_array($data['tipo'], ['receita', 'despesa'], true)) {
            Session::flash('error', 'Tipo inválido.');
            View::redirect('/categorias');
        }
        $this->model->save($data, !empty($data['id']) ? $eid : null);
        Session::flash('success', 'Categoria salva.');
        View::redirect('/categorias');
    }

    public function excluir(int $id): void
    {
        $this->model->delete($id, (int) Session::get('empresa_id'));
        Session::flash('success', 'Categoria removida.');
        View::redirect('/categorias');
    }
}
