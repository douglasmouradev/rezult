<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;

/** Cliente HTTP mínimo para API Asaas v3. */
final class AsaasClient
{
    public function __construct(
        private string $apiKey,
        private bool $sandbox = true,
        private int $maxRetries = 2,
    ) {}

    /** @param array<string, mixed> $body */
    public function post(string $path, array $body): array
    {
        return $this->request('POST', $path, $body);
    }

    public function get(string $path): array
    {
        return $this->request('GET', $path, null);
    }

    public function delete(string $path): array
    {
        return $this->request('DELETE', $path, null);
    }

    /** @param array<string, mixed>|null $body */
    private function request(string $method, string $path, ?array $body): array
    {
        $url = $this->baseUrl() . '/' . ltrim($path, '/');
        $headers = [
            'Content-Type: application/json',
            'access_token: ' . $this->apiKey,
            'User-Agent: Rezult/1.0',
        ];
        $payload = $body !== null ? json_encode($body, JSON_UNESCAPED_UNICODE) : null;

        $lastError = null;
        for ($attempt = 0; $attempt <= $this->maxRetries; $attempt++) {
            if ($attempt > 0) {
                usleep(200000 * $attempt);
            }

            $ctx = stream_context_create([
                'http' => [
                    'method' => $method,
                    'header' => implode("\r\n", $headers),
                    'content' => $payload,
                    'timeout' => 25,
                    'ignore_errors' => true,
                ],
            ]);

            $raw = @file_get_contents($url, false, $ctx);
            $status = 0;
            if (isset($http_response_header[0]) && preg_match('/\d{3}/', $http_response_header[0], $m)) {
                $status = (int) $m[0];
            }

            if ($status === 0 || $status >= 500) {
                $lastError = 'Gateway Asaas indisponível (HTTP ' . $status . ').';
                continue;
            }

            $data = is_string($raw) ? json_decode($raw, true) : null;
            if (!is_array($data)) {
                Logger::error('Asaas resposta inválida', ['status' => $status, 'path' => $path]);
                throw new \RuntimeException('Resposta inválida do gateway Asaas.');
            }

            if ($status < 200 || $status >= 300) {
                $msg = (string) ($data['errors'][0]['description'] ?? $data['message'] ?? 'Erro no gateway Asaas');
                Logger::error('Asaas erro HTTP', ['status' => $status, 'path' => $path, 'msg' => $msg]);
                throw new \RuntimeException($msg);
            }

            return $data;
        }

        throw new \RuntimeException($lastError ?? 'Falha ao comunicar com o gateway Asaas.');
    }

    private function baseUrl(): string
    {
        return $this->sandbox
            ? 'https://sandbox.asaas.com/api/v3'
            : 'https://api.asaas.com/api/v3';
    }
}
