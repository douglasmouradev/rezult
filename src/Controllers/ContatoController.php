<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\View;
use App\Helpers\Sanitize;
use App\Helpers\Session;
use App\Models\Contato;
use App\Policies\TenantPolicy;

final class ContatoController
{
    public function __construct(private Contato $model = new Contato()) {}

    public function index(): void
    {
        $eid = TenantPolicy::empresaId();
        View::render('contatos/index', [
            'title' => 'Contatos',
            'contatos' => $this->model->listar($eid),
        ]);
    }

    public function salvar(): void
    {
        $eid = TenantPolicy::empresaId();
        $data = [
            'nome' => Sanitize::raw($_POST['nome']),
            'documento' => Sanitize::raw($_POST['documento'] ?? '') ?: null,
            'email' => Sanitize::raw($_POST['email'] ?? '') ?: null,
            'telefone' => Sanitize::raw($_POST['telefone'] ?? '') ?: null,
            'tipo' => $_POST['tipo'] ?? 'cliente',
            'observacoes' => Sanitize::raw($_POST['observacoes'] ?? '') ?: null,
        ];
        if (!in_array($data['tipo'], ['cliente', 'fornecedor', 'ambos'], true)) {
            Session::flash('error', 'Tipo inválido.');
            View::redirect('/contatos');
        }
        if (!empty($_POST['id'])) {
            $data['id'] = (int) $_POST['id'];
            if (!$this->model->find((int) $data['id'], $eid)) {
                TenantPolicy::forbidden();
            }
        }
        $this->model->salvar($data, $eid);
        Session::flash('success', 'Contato salvo.');
        View::redirect('/contatos');
    }

    public function excluir(int $id): void
    {
        TenantPolicy::abortUnlessCanManageConfig();
        $eid = TenantPolicy::empresaId();
        App::pdo()->prepare('UPDATE lancamentos SET contato_id = NULL WHERE contato_id = :id AND empresa_id = :e')
            ->execute(['id' => $id, 'e' => $eid]);
        App::pdo()->prepare('UPDATE contatos SET ativo = 0 WHERE id = :id AND empresa_id = :e')
            ->execute(['id' => $id, 'e' => $eid]);
        Session::flash('success', 'Contato removido.');
        View::redirect('/contatos');
    }
}
