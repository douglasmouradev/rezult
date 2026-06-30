<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Helpers\Crypto;

final class IntegracaoService
{
    public const PROVEDOR_OPEN_FINANCE = 'open_finance';
    public const PROVEDOR_GATEWAY = 'gateway';
    public const PROVEDOR_NFSE = 'nfse';

    /** @var array<string, list<string>> */
    private const SECRET_FIELDS = [
        self::PROVEDOR_OPEN_FINANCE => ['client_secret'],
        self::PROVEDOR_GATEWAY => ['api_key', 'webhook_token'],
        self::PROVEDOR_NFSE => ['token'],
    ];

    /** @return string[] */
    public static function provedoresValidos(): array
    {
        return [
            self::PROVEDOR_OPEN_FINANCE,
            self::PROVEDOR_GATEWAY,
            self::PROVEDOR_NFSE,
        ];
    }

    /** Config descriptografada para uso interno. */
    /** @return array{ativo: bool, config: array<string, mixed>} */
    public function getConfig(int $empresaId, string $provedor): array
    {
        $row = $this->fetchRow($empresaId, $provedor);
        if (!$row) {
            return ['ativo' => false, 'config' => []];
        }

        return [
            'ativo' => (bool) $row['ativo'],
            'config' => $this->decryptConfig($provedor, $this->decodeJson($row['config_json'])),
        ];
    }

    /** Config mascarada para exibição em formulários. */
    /** @return array{ativo: bool, config: array<string, mixed>} */
    public function getConfigForDisplay(int $empresaId, string $provedor): array
    {
        $data = $this->getConfig($empresaId, $provedor);
        foreach (self::SECRET_FIELDS[$provedor] ?? [] as $field) {
            if (!empty($data['config'][$field])) {
                $data['config'][$field] = Crypto::mask((string) $data['config'][$field]);
                $data['config'][$field . '_preenchido'] = true;
            }
        }

        return $data;
    }

    /** @param array<string, mixed> $config */
    public function saveConfig(int $empresaId, string $provedor, array $config, bool $ativo): void
    {
        if (!in_array($provedor, self::provedoresValidos(), true)) {
            throw new \InvalidArgumentException('Provedor inválido.');
        }

        $atual = $this->getConfig($empresaId, $provedor)['config'];
        foreach (self::SECRET_FIELDS[$provedor] ?? [] as $field) {
            $novo = trim((string) ($config[$field] ?? ''));
            if ($novo === '' || str_contains($novo, '•')) {
                $config[$field] = $atual[$field] ?? '';
            }
        }

        $json = json_encode($this->encryptConfig($provedor, $config), JSON_UNESCAPED_UNICODE);
        App::pdo()->prepare(
            'INSERT INTO integracoes (empresa_id, provedor, config_json, ativo)
             VALUES (:e, :p, :c, :a)
             ON DUPLICATE KEY UPDATE config_json = VALUES(config_json), ativo = VALUES(ativo)'
        )->execute([
            'e' => $empresaId,
            'p' => $provedor,
            'c' => $json,
            'a' => (int) $ativo,
        ]);
    }

    public function gatewayAtivo(int $empresaId): bool
    {
        $cfg = $this->getConfig($empresaId, self::PROVEDOR_GATEWAY);

        return $cfg['ativo'] && !empty($cfg['config']['api_key']);
    }

    /** @return array<string, mixed>|null */
    private function fetchRow(int $empresaId, string $provedor): ?array
    {
        $stmt = App::pdo()->prepare(
            'SELECT ativo, config_json FROM integracoes WHERE empresa_id = :e AND provedor = :p LIMIT 1'
        );
        $stmt->execute(['e' => $empresaId, 'p' => $provedor]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @return array<string, mixed> */
    private function decodeJson(mixed $json): array
    {
        if (!$json) {
            return [];
        }
        $data = json_decode((string) $json, true);

        return is_array($data) ? $data : [];
    }

    /** @param array<string, mixed> $config */
    private function encryptConfig(string $provedor, array $config): array
    {
        foreach (self::SECRET_FIELDS[$provedor] ?? [] as $field) {
            if (!empty($config[$field]) && !str_contains((string) $config[$field], '•')) {
                $config[$field] = Crypto::encrypt((string) $config[$field]);
            }
        }

        return $config;
    }

    /** @param array<string, mixed> $config */
    private function decryptConfig(string $provedor, array $config): array
    {
        foreach (self::SECRET_FIELDS[$provedor] ?? [] as $field) {
            if (!empty($config[$field])) {
                $val = (string) $config[$field];
                $config[$field] = self::looksEncrypted($val)
                    ? Crypto::decrypt($val, strict: true)
                    : $val;
            }
        }

        return $config;
    }

    private static function looksEncrypted(string $value): bool
    {
        if (strlen($value) < 24) {
            return false;
        }
        $raw = base64_decode($value, true);

        return $raw !== false && strlen($raw) > 16;
    }
}
