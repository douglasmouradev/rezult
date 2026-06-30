<?php

declare(strict_types=1);

namespace App\Helpers;

/** Emissão NFS-e real vs demonstração. */
final class NfseMode
{
    public static function permiteDemonstracao(string $env, bool $allowDemo): bool
    {
        if ($allowDemo) {
            return true;
        }

        return $env !== 'production';
    }
}
