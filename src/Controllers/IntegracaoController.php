<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Helpers\Sanitize;
use App\Helpers\Session;
use App\Policies\TenantPolicy;
use App\Services\IntegracaoService;

final class IntegracaoController
{
    public function __construct(private IntegracaoService $service = new IntegracaoService()) {}

    public function index(): void
    {
        $eid = TenantPolicy::empresaId();
        View::render('integracoes/index', [
            'title' => 'Integrações',
            'openFinance' => $this->service->getConfig($eid, IntegracaoService::PROVEDOR_OPEN_FINANCE),
            'gateway' => $this->service->getConfig($eid, IntegracaoService::PROVEDOR_GATEWAY),
            'nfse' => $this->service->getConfig($eid, IntegracaoService::PROVEDOR_NFSE),
        ]);
    }

    public function salvar(): void
    {
        TenantPolicy::abortUnlessCanManageConfig();
        $eid = TenantPolicy::empresaId();
        $provedor = Sanitize::raw($_POST['provedor'] ?? '');

        if (!in_array($provedor, IntegracaoService::provedoresValidos(), true)) {
            Session::flash('error', 'Provedor inválido.');
            View::redirect('/integracoes');
        }

        $config = match ($provedor) {
            IntegracaoService::PROVEDOR_OPEN_FINANCE => [
                'client_id' => Sanitize::raw($_POST['client_id'] ?? ''),
                'client_secret' => Sanitize::raw($_POST['client_secret'] ?? ''),
                'ambiente' => $_POST['ambiente'] ?? 'sandbox',
            ],
            IntegracaoService::PROVEDOR_GATEWAY => [
                'api_key' => Sanitize::raw($_POST['api_key'] ?? ''),
                'webhook_url' => Sanitize::raw($_POST['webhook_url'] ?? ''),
            ],
            IntegracaoService::PROVEDOR_NFSE => [
                'cnpj' => Sanitize::raw($_POST['cnpj'] ?? ''),
                'inscricao_municipal' => Sanitize::raw($_POST['inscricao_municipal'] ?? ''),
                'token' => Sanitize::raw($_POST['token'] ?? ''),
            ],
            default => [],
        };

        $ativo = !empty($_POST['ativo']);
        $this->service->saveConfig($eid, $provedor, $config, $ativo);
        Session::flash('success', 'Configuração salva.');
        View::redirect('/integracoes');
    }
}
