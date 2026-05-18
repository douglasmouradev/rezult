<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Helpers\Session;
use App\Services\LgpdService;

final class LgpdController
{
    public function __construct(private LgpdService $lgpd = new LgpdService()) {}

    public function privacidade(): void
    {
        View::render('lgpd/privacidade', ['title' => 'Privacidade'], layout: Session::get('usuario_id') ? 'app' : 'guest');
    }

    public function termos(): void
    {
        View::render('lgpd/termos', ['title' => 'Termos de uso'], layout: Session::get('usuario_id') ? 'app' : 'guest');
    }

    public function meusDados(): void
    {
        View::render('lgpd/meus-dados', ['title' => 'Meus dados (LGPD)']);
    }

    public function exportar(): void
    {
        $uid = (int) Session::get('usuario_id');
        $this->lgpd->solicitar($uid, 'exportacao');
        $dados = $this->lgpd->exportarDadosUsuario($uid);

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="meus-dados-rezult.json"');
        echo json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function exportarEmpresa(): void
    {
        $eid = (int) Session::get('empresa_id');
        \App\Policies\TenantPolicy::abortUnlessCanManageConfig($eid);
        $dados = $this->lgpd->exportarDadosEmpresa($eid);
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="empresa-' . $eid . '-rezult.json"');
        echo json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function retificar(): void
    {
        $uid = (int) Session::get('usuario_id');
        try {
            $this->lgpd->retificarDados($uid, $_POST);
            Session::flash('success', 'Dados atualizados. Solicitação de retificação registrada.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        View::redirect('/privacidade/meus-dados');
    }

    public function aceitarCookies(): void
    {
        $uid = (int) Session::get('usuario_id');
        if ($uid > 0) {
            $this->lgpd->registrarConsentimentoCookies($uid);
        } else {
            $this->lgpd->registrarConsentimentoVisitante();
        }
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }

    public function solicitarExclusao(): void
    {
        $uid = (int) Session::get('usuario_id');
        $this->lgpd->solicitar($uid, 'exclusao');
        Session::flash('success', 'Solicitação registrada. A conta será anonimizada em até 15 dias úteis, ou confirme abaixo para exclusão imediata.');
        View::redirect('/privacidade/meus-dados');
    }

    public function confirmarExclusao(): void
    {
        $uid = (int) Session::get('usuario_id');
        if (($_POST['confirmar'] ?? '') !== 'EXCLUIR') {
            Session::flash('error', 'Digite EXCLUIR para confirmar.');
            View::redirect('/privacidade/meus-dados');
        }
        $this->lgpd->processarExclusaoConta($uid);
        (new \App\Services\AuthService())->logout();
        Session::flash('success', 'Conta anonimizada conforme LGPD.');
        View::redirect('/login');
    }
}
