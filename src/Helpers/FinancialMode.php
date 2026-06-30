<?php

declare(strict_types=1);

namespace App\Helpers;

/** Regras de emissão financeira demo vs produção. */
final class FinancialMode
{
    public static function permiteSimulacao(string $mode, string $env, bool $gatewayAtivo): bool
    {
        if ($gatewayAtivo) {
            return false;
        }
        if ($mode === 'demo') {
            return true;
        }

        return $env !== 'production';
    }
}
