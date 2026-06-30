<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\View;
use App\Helpers\Sanitize;
use App\Helpers\Session;
use App\Policies\TenantPolicy;
use App\Services\IntegracaoService;
use App\Services\PlanService;

final class IntegracaoController
{
    public function __construct(
        private IntegracaoService $service = new IntegracaoService(),
        private PlanService $plan = new PlanService(),
    ) {}

    public function index(): void
    {
        $eid = TenantPolicy::empresaId();
        View::render('integracoes/index', [
            'title' => 'Integrações',
            'openFinance' => $this->service->getConfigForDisplay($eid, IntegracaoService::PROVEDOR_OPEN_FINANCE),
            'gateway' => $this->service->getConfigForDisplay($eid, IntegracaoService::PROVEDOR_GATEWAY),
            'nfse' => $this->service->getConfigForDisplay($eid, IntegracaoService::PROVEDOR_NFSE),
            'temOpenFinance' => $this->plan->temFeature($eid, 'open_finance'),
            'temNfse' => $this->plan->temFeature($eid, 'nfse'),
            'temIntegracoes' => $this->plan->temFeature($eid, 'integracoes'),
            'isProduction' => App::config('env') === 'production',
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

        if (!$this->plan->temFeature($eid, 'integracoes')) {
            Session::flash('error', 'Integrações disponíveis a partir do plano Pro.');
            View::redirect('/integracoes');
        }

        $featurePorProvedor = [
            IntegracaoService::PROVEDOR_OPEN_FINANCE => 'open_finance',
            IntegracaoService::PROVEDOR_NFSE => 'nfse',
            IntegracaoService::PROVEDOR_GATEWAY => 'integracoes',
        ];
        $feature = $featurePorProvedor[$provedor] ?? 'integracoes';
        if (!$this->plan->temFeature($eid, $feature)) {
            Session::flash('error', 'Este recurso não está incluído no seu plano atual.');
            View::redirect('/integracoes');
        }

        $ativo = !empty($_POST['ativo']);

        if ($provedor === IntegracaoService::PROVEDOR_OPEN_FINANCE && $ativo) {
            Session::flash('error', 'Open Finance ainda em desenvolvimento. Use conciliação via CSV por enquanto.');
            View::redirect('/integracoes');
        }

        $configExistente = $this->service->getConfig($eid, $provedor);
        $cfgAtual = $configExistente['config'] ?? [];

        $config = match ($provedor) {
            IntegracaoService::PROVEDOR_OPEN_FINANCE => [
                'client_id' => Sanitize::raw($_POST['client_id'] ?? ''),
                'client_secret' => Sanitize::raw($_POST['client_secret'] ?? ''),
                'ambiente' => in_array($_POST['ambiente'] ?? '', ['sandbox', 'producao'], true)
                    ? $_POST['ambiente'] : 'sandbox',
            ],
            IntegracaoService::PROVEDOR_GATEWAY => [
                'provedor' => Sanitize::raw($_POST['gateway_provedor'] ?? 'asaas'),
                'ambiente' => in_array($_POST['ambiente'] ?? '', ['sandbox', 'producao'], true)
                    ? $_POST['ambiente'] : 'sandbox',
                'api_key' => Sanitize::raw($_POST['api_key'] ?? ''),
                'webhook_url' => rtrim((string) App::config('url'), '/') . '/webhooks/gateway/asaas',
                'webhook_token' => Sanitize::raw($_POST['webhook_token'] ?? ''),
            ],
            IntegracaoService::PROVEDOR_NFSE => [
                'cnpj' => preg_replace('/\D/', '', Sanitize::raw($_POST['cnpj'] ?? '')),
                'inscricao_municipal' => Sanitize::raw($_POST['inscricao_municipal'] ?? ''),
                'token' => Sanitize::raw($_POST['token'] ?? ''),
            ],
            default => [],
        };

        if ($provedor === IntegracaoService::PROVEDOR_GATEWAY && $ativo) {
            $apiKeyNova = trim((string) ($config['api_key'] ?? ''));
            $apiKeySalva = !empty($cfgAtual['api_key']);
            if ($apiKeyNova === '' && !$apiKeySalva) {
                Session::flash('error', 'Informe a API Key do Asaas para ativar o gateway.');
                View::redirect('/integracoes');
            }

            $tokenNovo = trim((string) ($config['webhook_token'] ?? ''));
            $tokenSalvo = !empty($cfgAtual['webhook_token']);
            if (App::config('env') === 'production' && $tokenNovo === '' && !$tokenSalvo) {
                Session::flash('error', 'Token do webhook é obrigatório em produção.');
                View::redirect('/integracoes');
            }
        }

        $this->service->saveConfig($eid, $provedor, $config, $ativo);
        Session::flash('success', 'Configuração salva.');
        View::redirect('/integracoes');
    }
}
