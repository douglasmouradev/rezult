<?php

declare(strict_types=1);

namespace App\Helpers;

/** Datas no fuso da aplicação (padrão: America/Sao_Paulo). */
final class DateTimeBr
{
    private static string $timezone = 'America/Sao_Paulo';

    public static function init(string $timezone): void
    {
        $timezone = trim($timezone) !== '' ? trim($timezone) : 'America/Sao_Paulo';
        if (!in_array($timezone, timezone_identifiers_list(), true)) {
            $timezone = 'America/Sao_Paulo';
        }
        self::$timezone = $timezone;
        date_default_timezone_set($timezone);
    }

    public static function timezone(): string
    {
        return self::$timezone;
    }

    /** Offset MySQL, ex: -03:00 */
    public static function mysqlOffset(): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone(self::$timezone)))->format('P');
    }

    public static function now(string $format = 'Y-m-d H:i:s'): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone(self::$timezone)))->format($format);
    }

    public static function format(?string $datetime, string $format = 'd/m/Y H:i'): string
    {
        if ($datetime === null || trim($datetime) === '') {
            return '—';
        }
        $ts = strtotime($datetime);
        if ($ts === false) {
            return '—';
        }

        return date($format, $ts);
    }

    /** @return array{php: string, timezone: string, offset: string, mysql: ?string} */
    public static function diagnostico(?\PDO $pdo = null): array
    {
        $info = [
            'php' => self::now('d/m/Y H:i:s'),
            'timezone' => self::$timezone,
            'offset' => self::mysqlOffset(),
            'mysql' => null,
        ];
        if ($pdo !== null) {
            $row = $pdo->query('SELECT NOW() AS agora, @@session.time_zone AS tz')->fetch();
            $info['mysql'] = $row
                ? self::format((string) $row['agora'], 'd/m/Y H:i:s') . ' (' . ($row['tz'] ?? '?') . ')'
                : null;
        }

        return $info;
    }
}
