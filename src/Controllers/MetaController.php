<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Helpers\Sanitize;
use App\Helpers\Session;
use App\Models\Meta;

final class MetaController
{
    public function __construct(private Meta $model = new Meta()) {}

    public function index(): void
    {
        $eid = (int) Session::get('empresa_id');
        $metas = $this->model->findAll($eid, 'prazo ASC');
        View::render('metas/index', ['title' => 'Metas', 'metas' => $metas]);
    }

    public function salvar(): void
    {
        $eid = (int) Session::get('empresa_id');
        $data = [
            'empresa_id' => $eid,
            'descricao' => Sanitize::raw($_POST['descricao']),
            'valor_alvo' => Sanitize::money($_POST['valor_alvo']),
            'prazo' => $_POST['prazo'] ?: null,
        ];
        if (!empty($_POST['id'])) {
            $data['id'] = (int) $_POST['id'];
            if (!$this->model->find((int) $data['id'], $eid)) {
                \App\Policies\TenantPolicy::forbidden();
            }
        }
        $id = $this->model->save($data, !empty($data['id']) ? $eid : null);
        $this->model->atualizarProgresso($id, $eid);
        Session::flash('success', 'Meta salva.');
        View::redirect('/metas');
    }

    public function excluir(int $id): void
    {
        $this->model->delete($id, (int) Session::get('empresa_id'));
        Session::flash('success', 'Meta removida.');
        View::redirect('/metas');
    }
}
