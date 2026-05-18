<?php

declare(strict_types=1);

namespace App\Helpers;

final class Sanitize
{
    public static function string(?string $value): string
    {
        return htmlspecialchars(trim((string) $value), ENT_QUOTES, 'UTF-8');
    }

    public static function raw(?string $value): string
    {
        return trim((string) $value);
    }

    public static function money(?string $value): float
    {
        $v = preg_replace('/[^\d,.-]/', '', (string) $value);
        $v = str_replace(['.', ','], ['', '.'], $v);
        return (float) $v;
    }
}
