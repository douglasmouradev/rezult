<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;

final class IntegracaoService
{
    public const PROVEDOR_OPEN_FINANCE = 'open_finance';
    public const PROVEDOR_GATEWAY = 'gateway';
    public const PROVEDOR_NFSE = 'nfse';

    /** @return string[] */
    public static function provedoresValidos(): array
    {
        return [
            self::PROVEDOR_OPEN_FINANCE,
            self::PROVEDOR_GATEWAY,
            self::PROVEDOR_NFSE,
        ];
    }

    /** @return array{ativo: bool, config: array<string, mixed>} */
    public function getConfig(int $empresaId, string $provedor): array
    {
        $stmt = App::pdo()->prepare(
            'SELECT ativo, config_json FROM integracoes WHERE empresa_id = :e AND provedor = :p LIMIT 1'
        );
        $stmt->execute(['e' => $empresaId, 'p' => $provedor]);
        $row = $stmt->fetch();

        if (!$row) {
            return ['ativo' => false, 'config' => []];
        }

        $config = $row['config_json'] ? json_decode((string) $row['config_json'], true) : [];
        return [
            'ativo' => (bool) $row['ativo'],
            'config' => is_array($config) ? $config : [],
        ];
    }

    /** @param array<string, mixed> $config */
    public function saveConfig(int $empresaId, string $provedor, array $config, bool $ativo): void
    {
        if (!in_array($provedor, self::provedoresValidos(), true)) {
            throw new \InvalidArgumentException('Provedor inválido.');
        }

        $json = json_encode($config, JSON_UNESCAPED_UNICODE);
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
}
