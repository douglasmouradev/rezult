<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\View;
use App\Helpers\Session;
use App\Policies\TenantPolicy;

final class EquipeController
{
    public function index(): void
    {
        $eid = TenantPolicy::empresaId();
        TenantPolicy::abortUnlessCanManageConfig($eid);

        $pdo = App::pdo();
        $membros = $pdo->prepare(
            'SELECT u.id, u.nome, u.email, ue.papel FROM usuario_empresa ue
             JOIN usuarios u ON u.id = ue.usuario_id
             WHERE ue.empresa_id = :e ORDER BY u.nome'
        );
        $membros->execute(['e' => $eid]);

        $convites = $pdo->prepare(
            'SELECT c.*, u.nome AS convidado_por_nome FROM convites c
             LEFT JOIN usuarios u ON u.id = c.convidado_por
             WHERE c.empresa_id = :e AND c.aceito_em IS NULL AND c.expira_em > NOW()
             ORDER BY c.criado_em DESC'
        );
        $convites->execute(['e' => $eid]);

        View::render('equipe/index', [
            'title' => 'Equipe',
            'membros' => $membros->fetchAll(),
            'convites' => $convites->fetchAll(),
        ]);
    }

    public function remover(int $usuarioId): void
    {
        $eid = TenantPolicy::empresaId();
        TenantPolicy::abortUnlessCanManageConfig($eid);
        if ($usuarioId === TenantPolicy::usuarioId()) {
            Session::flash('error', 'Você não pode remover a si mesmo.');
            View::redirect('/equipe');
        }
        App::pdo()->prepare(
            'DELETE FROM usuario_empresa WHERE empresa_id = :e AND usuario_id = :u AND papel != :d'
        )->execute(['e' => $eid, 'u' => $usuarioId, 'd' => 'dono']);
        Session::flash('success', 'Membro removido.');
        View::redirect('/equipe');
    }

    public function alterarPapel(int $usuarioId): void
    {
        $eid = TenantPolicy::empresaId();
        TenantPolicy::abortUnlessCanManageConfig($eid);
        $papel = $_POST['papel'] ?? '';
        if (!in_array($papel, ['admin', 'operador'], true)) {
            Session::flash('error', 'Papel inválido.');
            View::redirect('/equipe');
        }
        if ($usuarioId === TenantPolicy::usuarioId()) {
            Session::flash('error', 'Você não pode alterar seu próprio papel.');
            View::redirect('/equipe');
        }
        App::pdo()->prepare(
            'UPDATE usuario_empresa SET papel = :p WHERE empresa_id = :e AND usuario_id = :u AND papel != :d'
        )->execute(['p' => $papel, 'e' => $eid, 'u' => $usuarioId, 'd' => 'dono']);
        Session::flash('success', 'Papel atualizado.');
        View::redirect('/equipe');
    }

    public function cancelarConvite(int $conviteId): void
    {
        $eid = TenantPolicy::empresaId();
        TenantPolicy::abortUnlessCanManageConfig($eid);
        App::pdo()->prepare(
            'DELETE FROM convites WHERE id = :id AND empresa_id = :e AND aceito_em IS NULL'
        )->execute(['id' => $conviteId, 'e' => $eid]);
        Session::flash('success', 'Convite cancelado.');
        View::redirect('/equipe');
    }
}
