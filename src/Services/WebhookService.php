<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;

final class WebhookService
{
    public function dispatch(string $event, int $empresaId, array $payload): void
    {
        $stmt = App::pdo()->prepare(
            'SELECT id, url, secret, eventos FROM webhooks WHERE empresa_id = :e AND ativo = 1'
        );
        $stmt->execute(['e' => $empresaId]);

        $body = json_encode([
            'event' => $event,
            'empresa_id' => $empresaId,
            'timestamp' => date('c'),
            'data' => $payload,
        ], JSON_UNESCAPED_UNICODE);

        if ($body === false) {
            return;
        }

        foreach ($stmt->fetchAll() as $hook) {
            $eventos = json_decode($hook['eventos'] ?? '[]', true);
            if (!is_array($eventos) || (!in_array($event, $eventos, true) && !in_array('*', $eventos, true))) {
                continue;
            }

            $this->post($hook['url'], $body, $hook['secret']);
        }
    }

    private function post(string $url, string $body, string $secret): void
    {
        $signature = hash_hmac('sha256', $body, $secret);

        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", [
                    'Content-Type: application/json',
                    'X-Rezult-Signature: ' . $signature,
                    'User-Agent: Rezult-Webhook/1.0',
                ]),
                'content' => $body,
                'timeout' => 5,
                'ignore_errors' => true,
            ],
        ]);

        @file_get_contents($url, false, $ctx);
    }
}
