<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Logger;

/** Emissão de cobranças via gateway configurado ou modo demonstração. */
final class GatewayService
{
    public function __construct(private IntegracaoService $integracoes = new IntegracaoService()) {}

    /**
     * @param array<string, mixed> $cobranca
     * @return array{modo: string, codigo_pix: ?string, linha_digitavel: ?string, gateway_id: ?string}
     */
    public function emitir(int $empresaId, array $cobranca): array
    {
        if ($this->integracoes->gatewayAtivo($empresaId)) {
            return $this->emitirViaGateway($empresaId, $cobranca);
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
        $apiKey = (string) ($cfg['config']['api_key'] ?? '');

        // Ponto de extensão: Asaas / Stripe / Mercado Pago
        Logger::info('Gateway cobrança (stub)', [
            'empresa_id' => $empresaId,
            'cobranca_id' => $cobranca['id'] ?? null,
            'api_key_prefix' => substr($apiKey, 0, 6),
        ]);

        $sim = $this->emitirSimulado($cobranca);
        $sim['modo'] = 'gateway';
        $sim['gateway_id'] = 'gw_' . ($cobranca['id'] ?? 0) . '_' . bin2hex(random_bytes(4));

        return $sim;
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
        ];
    }
}
