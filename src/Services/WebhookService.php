<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Logger;

final class WebhookService
{
    private const MAX_TENTATIVAS = 3;

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

            $this->entregar(
                (int) $hook['id'],
                $empresaId,
                $event,
                $hook['url'],
                $hook['secret'],
                $body,
            );
        }
    }

    public function reprocessarFalhas(int $limite = 50): int
    {
        if (!$this->tabelaEntregasExiste()) {
            return 0;
        }

        $lim = max(1, min(200, $limite));
        $stmt = App::pdo()->query(
            'SELECT we.*, w.secret FROM webhook_entregas we
             INNER JOIN webhooks w ON w.id = we.webhook_id AND w.ativo = 1
             WHERE we.sucesso = 0 AND we.tentativas < ' . self::MAX_TENTATIVAS . '
             AND we.criado_em > DATE_SUB(NOW(), INTERVAL 48 HOUR)
             ORDER BY we.criado_em ASC
             LIMIT ' . $lim
        );

        $reprocessados = 0;
        foreach ($stmt->fetchAll() as $row) {
            $resultado = $this->post($row['url'], (string) $row['payload'], (string) $row['secret']);
            $this->atualizarEntrega((int) $row['id'], $resultado, (int) $row['tentativas'] + 1);
            if ($resultado['sucesso']) {
                $reprocessados++;
            }
        }

        return $reprocessados;
    }

    /** @return list<array<string, mixed>> */
    public function listarEntregas(int $empresaId, int $limit = 50): array
    {
        if (!$this->tabelaEntregasExiste()) {
            return [];
        }

        $lim = max(1, min(200, $limit));
        $stmt = App::pdo()->prepare(
            'SELECT id, webhook_id, evento, url, http_status, sucesso, tentativas, criado_em
             FROM webhook_entregas WHERE empresa_id = :e ORDER BY id DESC LIMIT ' . $lim
        );
        $stmt->execute(['e' => $empresaId]);

        return $stmt->fetchAll() ?: [];
    }

    private function entregar(
        int $webhookId,
        int $empresaId,
        string $event,
        string $url,
        string $secret,
        string $body,
    ): void {
        $resultado = $this->post($url, $body, $secret);

        if (!$this->tabelaEntregasExiste()) {
            return;
        }

        App::pdo()->prepare(
            'INSERT INTO webhook_entregas (webhook_id, empresa_id, evento, url, payload, http_status, resposta, sucesso, tentativas)
             VALUES (:wid, :eid, :ev, :url, :payload, :status, :resp, :ok, 1)'
        )->execute([
            'wid' => $webhookId,
            'eid' => $empresaId,
            'ev' => $event,
            'url' => mb_substr($url, 0, 500),
            'payload' => $body,
            'status' => $resultado['http_status'],
            'resp' => mb_substr($resultado['resposta'] ?? '', 0, 2000),
            'ok' => $resultado['sucesso'] ? 1 : 0,
        ]);

        if (!$resultado['sucesso']) {
            Logger::info('Webhook falhou', ['url' => $url, 'status' => $resultado['http_status']]);
        }
    }

    /** @param array{http_status: ?int, resposta: ?string, sucesso: bool} $resultado */
    private function atualizarEntrega(int $id, array $resultado, int $tentativas): void
    {
        App::pdo()->prepare(
            'UPDATE webhook_entregas SET http_status = :s, resposta = :r, sucesso = :ok, tentativas = :t WHERE id = :id'
        )->execute([
            's' => $resultado['http_status'],
            'r' => mb_substr($resultado['resposta'] ?? '', 0, 2000),
            'ok' => $resultado['sucesso'] ? 1 : 0,
            't' => $tentativas,
            'id' => $id,
        ]);
    }

    /** @return array{http_status: ?int, resposta: ?string, sucesso: bool} */
    private function post(string $url, string $body, string $secret): array
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
                'timeout' => 8,
                'ignore_errors' => true,
            ],
        ]);

        $resposta = @file_get_contents($url, false, $ctx);
        $status = null;
        if (isset($http_response_header[0]) && preg_match('/\d{3}/', $http_response_header[0], $m)) {
            $status = (int) $m[0];
        }

        $sucesso = $status !== null && $status >= 200 && $status < 300;

        return [
            'http_status' => $status,
            'resposta' => is_string($resposta) ? $resposta : null,
            'sucesso' => $sucesso,
        ];
    }

    private function tabelaEntregasExiste(): bool
    {
        static $existe = null;
        if ($existe !== null) {
            return $existe;
        }
        try {
            $stmt = App::pdo()->query("SHOW TABLES LIKE 'webhook_entregas'");
            $existe = (bool) $stmt->fetch();
        } catch (\Throwable) {
            $existe = false;
        }

        return $existe;
    }
}
