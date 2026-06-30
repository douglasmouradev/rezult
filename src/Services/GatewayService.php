<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Logger;
use App\Helpers\FinancialMode;

/** Emissão de cobranças via gateway configurado ou modo demonstração. */
final class GatewayService
{
    public function __construct(private IntegracaoService $integracoes = new IntegracaoService()) {}

    /**
     * @param array<string, mixed> $cobranca
     * @return array{modo: string, codigo_pix: ?string, linha_digitavel: ?string, gateway_id: ?string, gateway_provedor: ?string}
     */
    public function emitir(int $empresaId, array $cobranca): array
    {
        if ($this->integracoes->gatewayAtivo($empresaId)) {
            return $this->emitirViaGateway($empresaId, $cobranca);
        }

        if (!FinancialMode::permiteSimulacao(
            (string) App::config('financial_mode', 'demo'),
            (string) App::config('env', 'local'),
            false,
        )) {
            throw new \RuntimeException(
                'Modo financeiro live exige gateway de pagamento configurado e ativo.'
            );
        }

        return $this->emitirSimulado($cobranca);
    }

    public function modoAtual(int $empresaId): string
    {
        return $this->integracoes->gatewayAtivo($empresaId) ? 'gateway' : 'simulacao';
    }

    /** @param array<string, mixed> $cobranca */
    private function emitirViaGateway(int $empresaId, array $cobranca): array
    {
        $cfg = $this->integracoes->getConfig($empresaId, IntegracaoService::PROVEDOR_GATEWAY);
        $config = $cfg['config'];
        $provedor = (string) ($config['provedor'] ?? 'asaas');

        return match ($provedor) {
            'asaas' => $this->emitirViaAsaas($empresaId, $cobranca, $config),
            default => throw new \RuntimeException('Provedor de gateway não suportado: ' . $provedor),
        };
    }

    /** @param array<string, mixed> $cobranca @param array<string, mixed> $config */
    private function emitirViaAsaas(int $empresaId, array $cobranca, array $config): array
    {
        $apiKey = (string) ($config['api_key'] ?? '');
        if ($apiKey === '') {
            throw new \RuntimeException('API Key do Asaas não configurada.');
        }

        $sandbox = ($config['ambiente'] ?? 'sandbox') !== 'producao';
        $client = new AsaasClient($apiKey, $sandbox);
        $cobrancaId = (int) ($cobranca['id'] ?? 0);
        $externalRef = "rezult:{$empresaId}:{$cobrancaId}";

        $customerBody = [
            'name' => (string) $cobranca['cliente_nome'],
            'email' => (string) ($cobranca['cliente_email'] ?? '') ?: null,
            'notificationDisabled' => true,
        ];
        $customerBody = array_filter($customerBody, fn ($v) => $v !== null && $v !== '');
        $customer = $client->post('customers', $customerBody);
        $customerId = (string) ($customer['id'] ?? '');
        if ($customerId === '') {
            throw new \RuntimeException('Não foi possível criar cliente no Asaas.');
        }

        $tipo = ($cobranca['tipo'] ?? 'pix') === 'boleto' ? 'BOLETO' : 'PIX';
        $payment = $client->post('payments', [
            'customer' => $customerId,
            'billingType' => $tipo,
            'value' => round((float) $cobranca['valor'], 2),
            'dueDate' => (string) $cobranca['vencimento'],
            'description' => mb_substr((string) $cobranca['descricao'], 0, 200),
            'externalReference' => $externalRef,
        ]);

        $paymentId = (string) ($payment['id'] ?? '');
        if ($paymentId === '') {
            throw new \RuntimeException('Pagamento não criado no Asaas.');
        }

        $pix = null;
        $boleto = null;

        if ($tipo === 'PIX') {
            $qr = $client->get('payments/' . $paymentId . '/pixQrCode');
            $pix = (string) ($qr['payload'] ?? '');
        } else {
            $boleto = (string) ($payment['bankSlipUrl'] ?? $payment['identificationField'] ?? '');
            if ($boleto === '' && !empty($payment['id'])) {
                $detail = $client->get('payments/' . $paymentId);
                $boleto = (string) ($detail['identificationField'] ?? '');
            }
        }

        Logger::info('Cobrança emitida via Asaas', [
            'empresa_id' => $empresaId,
            'cobranca_id' => $cobrancaId,
            'payment_id' => $paymentId,
        ]);

        return [
            'modo' => 'gateway',
            'codigo_pix' => $pix !== '' ? $pix : null,
            'linha_digitavel' => $boleto !== '' ? $boleto : null,
            'gateway_id' => $paymentId,
            'gateway_provedor' => 'asaas',
        ];
    }

    /** @param array<string, mixed> $cobranca */
    private function emitirSimulado(array $cobranca): array
    {
        $valor = number_format((float) $cobranca['valor'], 2, '.', '');
        $pix = '00020126580014BR.GOV.BCB.PIX0136' . substr(md5(($cobranca['id'] ?? '') . ($cobranca['cliente_nome'] ?? '')), 0, 32)
            . '520400005303986540' . str_pad(strlen($valor) + 4, 2, '0', STR_PAD_LEFT) . $valor
            . '5802BR5925REZULT COBRANCA DEMO6009SAO PAULO62070503***6304' . strtoupper(substr(md5((string) ($cobranca['id'] ?? '')), 0, 4));

        $boleto = ($cobranca['tipo'] ?? '') === 'boleto'
            ? sprintf(
                '23793.38128 %s %s %s %s',
                str_pad((string) ((int) ((float) $cobranca['valor'] * 100)), 10, '0', STR_PAD_LEFT),
                date('dmy', strtotime((string) $cobranca['vencimento'])),
                str_pad((string) ($cobranca['id'] ?? 0), 10, '0', STR_PAD_LEFT),
                substr(md5((string) ($cobranca['id'] ?? '')), 0, 14)
            )
            : null;

        return [
            'modo' => 'simulacao',
            'codigo_pix' => $pix,
            'linha_digitavel' => $boleto,
            'gateway_id' => null,
            'gateway_provedor' => null,
        ];
    }
}
