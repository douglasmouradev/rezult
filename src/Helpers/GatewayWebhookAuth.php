<?php

declare(strict_types=1);

namespace App\Helpers;

/** Validação de token para webhooks de gateway. */
final class GatewayWebhookAuth
{
    public static function aceita(string $env, string $expectedToken, string $receivedToken): bool
    {
        $expected = trim($expectedToken);
        $received = trim($receivedToken);

        if ($env === 'production' && $expected === '') {
            return false;
        }

        if ($expected === '') {
            return true;
        }

        return hash_equals($expected, $received);
    }
}
