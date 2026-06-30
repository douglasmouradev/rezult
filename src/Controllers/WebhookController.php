<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\View;
use App\Helpers\Crypto;
use App\Helpers\Sanitize;
use App\Helpers\Session;
use App\Helpers\UrlSafety;
use App\Policies\TenantPolicy;
use App\Services\PlanService;
use App\Services\WebhookService;

final class WebhookController
{
    public function index(): void
    {
        TenantPolicy::abortUnlessCanManageConfig();
        $eid = TenantPolicy::empresaId();
        $stmt = App::pdo()->prepare(
            'SELECT id, url, eventos, ativo, criado_em FROM webhooks WHERE empresa_id = :e ORDER BY id DESC'
        );
        $stmt->execute(['e' => $eid]);

        View::render('webhooks/index', [
            'title' => 'Webhooks',
            'webhooks' => $stmt->fetchAll(),
            'eventosDisponiveis' => ['lancamento.pago', 'cobranca.paga', '*'],
        ]);
    }

    public function salvar(): void
    {
        TenantPolicy::abortUnlessCanManageConfig();
        $eid = TenantPolicy::empresaId();
        $url = Sanitize::raw($_POST['url'] ?? '');
        $eventos = array_values(array_filter((array) ($_POST['eventos'] ?? [])));
        $id = (int) ($_POST['id'] ?? 0);

        if ($url === '' || $eventos === []) {
            Session::flash('error', 'Informe URL e ao menos um evento.');
            View::redirect('/webhooks');
        }

        $check = UrlSafety::webhookPermitida($url);
        if (!$check['ok']) {
            Session::flash('error', 'URL do webhook inválida: ' . ($check['motivo'] ?? ''));
            View::redirect('/webhooks');
        }

        if ($id <= 0 && !(new PlanService())->podeCriarWebhook($eid)) {
            Session::flash('error', 'Limite de webhooks do seu plano atingido.');
            View::redirect('/webhooks');
        }

        if ($id > 0) {
            App::pdo()->prepare(
                'UPDATE webhooks SET url = :url, eventos = :eventos, ativo = :ativo
                 WHERE id = :id AND empresa_id = :e'
            )->execute([
                'url' => $url,
                'eventos' => json_encode($eventos),
                'ativo' => !empty($_POST['ativo']) ? 1 : 0,
                'id' => $id,
                'e' => $eid,
            ]);
            Session::flash('success', 'Webhook atualizado.');
        } else {
            $plainSecret = bin2hex(random_bytes(32));
            App::pdo()->prepare(
                'INSERT INTO webhooks (empresa_id, url, eventos, secret, ativo) VALUES (:e, :url, :eventos, :secret, 1)'
            )->execute([
                'e' => $eid,
                'url' => $url,
                'eventos' => json_encode($eventos),
                'secret' => Crypto::encrypt($plainSecret),
            ]);
            Session::flash('success', 'Webhook cadastrado.');
        }

        View::redirect('/webhooks');
    }

    public function excluir(int $id): void
    {
        TenantPolicy::abortUnlessCanManageConfig();
        App::pdo()->prepare('DELETE FROM webhooks WHERE id = :id AND empresa_id = :e')
            ->execute(['id' => $id, 'e' => TenantPolicy::empresaId()]);
        Session::flash('success', 'Webhook removido.');
        View::redirect('/webhooks');
    }

    public function entregas(): void
    {
        TenantPolicy::abortUnlessCanManageConfig();
        $eid = TenantPolicy::empresaId();
        $svc = new WebhookService();

        View::render('webhooks/entregas', [
            'title' => 'Entregas de Webhook',
            'entregas' => $svc->listarEntregas($eid, 100),
        ]);
    }
}
