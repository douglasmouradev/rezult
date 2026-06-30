<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Logger;
use App\Core\View;
use App\Helpers\GatewayWebhookAuth;
use App\Services\CobrancaService;
use App\Services\IntegracaoService;

/** Webhooks incoming de gateways de pagamento (sem sessão/CSRF). */
final class GatewayWebhookController
{
    public function asaas(): void
    {
        $raw = file_get_contents('php://input') ?: '';
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            $this->json(400, ['error' => 'Invalid JSON']);
        }

        $event = (string) ($payload['event'] ?? '');
        $payment = $payload['payment'] ?? null;
        if (!is_array($payment)) {
            $this->json(400, ['error' => 'Missing payment']);
        }

        $gatewayId = (string) ($payment['id'] ?? '');
        if ($gatewayId === '') {
            $this->json(400, ['error' => 'Missing payment id']);
        }

        $stmt = App::pdo()->prepare(
            'SELECT id, empresa_id FROM cobrancas WHERE gateway_id = :gid AND gateway_provedor = :p LIMIT 1'
        );
        $stmt->execute(['gid' => $gatewayId, 'p' => 'asaas']);
        $cobranca = $stmt->fetch();
        if (!$cobranca) {
            $ref = (string) ($payment['externalReference'] ?? '');
            if (preg_match('/^rezult:(\d+):(\d+)$/', $ref, $m)) {
                $cobranca = [
                    'id' => (int) $m[2],
                    'empresa_id' => (int) $m[1],
                ];
            }
        }

        if (!$cobranca) {
            Logger::info('Asaas webhook cobrança não encontrada', ['gateway_id' => $gatewayId, 'event' => $event]);
            $this->json(200, ['ok' => true, 'ignored' => true]);
        }

        $empresaId = (int) $cobranca['empresa_id'];
        $cobrancaId = (int) $cobranca['id'];

        $cfg = (new IntegracaoService())->getConfig($empresaId, IntegracaoService::PROVEDOR_GATEWAY);
        $expectedToken = (string) ($cfg['config']['webhook_token'] ?? '');
        $receivedToken = (string) ($_SERVER['HTTP_ASAAS_ACCESS_TOKEN'] ?? '');

        if (!GatewayWebhookAuth::aceita((string) App::config('env', 'local'), $expectedToken, $receivedToken)) {
            Logger::info('Asaas webhook rejeitado', ['empresa_id' => $empresaId]);
            $this->json(403, ['error' => 'Forbidden']);
        }

        $eventoId = $gatewayId . ':' . $event;
        if ($this->eventoJaProcessado('asaas', $eventoId)) {
            $this->json(200, ['ok' => true, 'duplicate' => true]);
        }

        $pagos = ['PAYMENT_RECEIVED', 'PAYMENT_CONFIRMED', 'PAYMENT_RECEIVED_IN_CASH'];
        if (in_array($event, $pagos, true)) {
            (new CobrancaService())->marcarPaga($cobrancaId, $empresaId);
            $this->registrarEvento('asaas', $eventoId);
            Logger::info('Cobrança paga via Asaas', ['cobranca_id' => $cobrancaId, 'empresa_id' => $empresaId]);
        }

        $this->json(200, ['ok' => true]);
    }

    private function eventoJaProcessado(string $provedor, string $eventoId): bool
    {
        try {
            $stmt = App::pdo()->prepare(
                'SELECT 1 FROM gateway_webhook_eventos WHERE provedor = :p AND evento_id = :e LIMIT 1'
            );
            $stmt->execute(['p' => $provedor, 'e' => $eventoId]);

            return (bool) $stmt->fetchColumn();
        } catch (\Throwable) {
            return false;
        }
    }

    private function registrarEvento(string $provedor, string $eventoId): void
    {
        try {
            App::pdo()->prepare(
                'INSERT IGNORE INTO gateway_webhook_eventos (provedor, evento_id) VALUES (:p, :e)'
            )->execute(['p' => $provedor, 'e' => $eventoId]);
        } catch (\Throwable $e) {
            Logger::error('Falha ao registrar evento gateway', ['error' => $e->getMessage()]);
        }
    }

    /** @param array<string, mixed> $data */
    private function json(int $code, array $data): never
    {
        View::json($data, $code);
    }
}
