<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Core\App;

/** Validação de URLs para evitar SSRF em webhooks. */
final class UrlSafety
{
    /** @return array{ok: bool, motivo: ?string} */
    public static function webhookPermitida(string $url): array
    {
        $url = trim($url);
        if ($url === '') {
            return ['ok' => false, 'motivo' => 'URL vazia'];
        }

        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return ['ok' => false, 'motivo' => 'URL inválida'];
        }

        $scheme = strtolower($parts['scheme']);
        if (!in_array($scheme, ['https', 'http'], true)) {
            return ['ok' => false, 'motivo' => 'Apenas HTTP/HTTPS'];
        }

        if ($scheme === 'http' && (App::config('env') ?? '') === 'production') {
            return ['ok' => false, 'motivo' => 'Webhooks em produção exigem HTTPS'];
        }

        $host = strtolower($parts['host']);
        if (in_array($host, ['localhost', '127.0.0.1', '0.0.0.0', '::1'], true)) {
            return ['ok' => false, 'motivo' => 'Host local não permitido'];
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if (!self::ipPublico($host)) {
                return ['ok' => false, 'motivo' => 'IP privado não permitido'];
            }
        } else {
            $resolved = gethostbynamel($host);
            if (is_array($resolved)) {
                foreach ($resolved as $ip) {
                    if (!self::ipPublico($ip)) {
                        return ['ok' => false, 'motivo' => 'Domínio resolve para IP privado'];
                    }
                }
            }
        }

        return ['ok' => true, 'motivo' => null];
    }

    private static function ipPublico(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }
}
