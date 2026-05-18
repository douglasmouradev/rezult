<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\View;
use App\Helpers\Sanitize;
use App\Helpers\Session;
use App\Models\Categoria;
use App\Models\Conta;
use App\Models\RegraAutomacao;
use App\Policies\TenantPolicy;

final class AutomacaoController
{
    public function index(): void
    {
        $eid = TenantPolicy::empresaId();
        View::render('automacoes/index', [
            'title' => 'Automações',
            'regras' => (new RegraAutomacao())->findAll($eid, 'id DESC'),
            'categorias' => (new Categoria())->findAll($eid, 'nome ASC'),
            'contas' => (new Conta())->findAll($eid, 'nome ASC'),
        ]);
    }

    public function salvar(): void
    {
        TenantPolicy::abortUnlessCanManageConfig();
        $eid = TenantPolicy::empresaId();
        $params = match ($_POST['acao'] ?? '') {
            'categorizar' => ['categoria_id' => (int) ($_POST['categoria_id'] ?? 0)],
            'notificar' => ['mensagem' => Sanitize::raw($_POST['mensagem'] ?? '')],
            'criar_lancamento' => [
                'conta_id' => (int) ($_POST['conta_id'] ?? 0),
                'tipo' => $_POST['tipo_lanc'] ?? 'despesa',
                'valor' => Sanitize::money($_POST['valor_auto'] ?? '0'),
                'descricao' => Sanitize::raw($_POST['descricao_auto'] ?? 'Automático'),
            ],
            default => [],
        };
        $cond = $_POST['gatilho'] === 'descricao_contem'
            ? json_encode(['texto' => Sanitize::raw($_POST['texto_condicao'] ?? '')])
            : null;

        App::pdo()->prepare(
            'INSERT INTO regras_automacao (empresa_id, nome, ativo, gatilho, condicao, acao, parametros)
             VALUES (:e,:n,1,:g,:c,:a,:p)'
        )->execute([
            'e' => $eid,
            'n' => Sanitize::raw($_POST['nome']),
            'g' => $_POST['gatilho'],
            'c' => $cond,
            'a' => $_POST['acao'],
            'p' => json_encode($params),
        ]);
        Session::flash('success', 'Regra criada.');
        View::redirect('/automacoes');
    }

    public function toggle(int $id): void
    {
        TenantPolicy::abortUnlessCanManageConfig();
        $eid = TenantPolicy::empresaId();
        App::pdo()->prepare(
            'UPDATE regras_automacao SET ativo = NOT ativo WHERE id = :id AND empresa_id = :e'
        )->execute(['id' => $id, 'e' => $eid]);
        View::redirect('/automacoes');
    }

    public function excluir(int $id): void
    {
        TenantPolicy::abortUnlessCanManageConfig();
        (new RegraAutomacao())->delete($id, TenantPolicy::empresaId());
        Session::flash('success', 'Regra removida.');
        View::redirect('/automacoes');
    }
}
