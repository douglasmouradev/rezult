<?php

declare(strict_types=1);

namespace App\Helpers;

final class Money
{
    public static function format(float $value): string
    {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }

    /** @return array{symbol: string, amount: string} */
    public static function parts(float $value): array
    {
        return [
            'symbol' => 'R$',
            'amount' => number_format($value, 2, ',', '.'),
        ];
    }
}
