<?php

declare(strict_types=1);

namespace App\Core;

final class Logger
{
    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    /** @return list<string> */
    public static function tail(int $lines = 200): array
    {
        $file = App::basePath() . '/storage/logs/app.log';
        if (!is_file($file)) {
            return [];
        }

        $lines = max(10, min(1000, $lines));
        $fp = fopen($file, 'rb');
        if (!$fp) {
            return [];
        }

        fseek($fp, 0, SEEK_END);
        $pos = ftell($fp);
        $buffer = '';
        $collected = [];

        while ($pos > 0 && count($collected) < $lines) {
            $chunk = min(4096, $pos);
            $pos -= $chunk;
            fseek($fp, $pos);
            $buffer = fread($fp, $chunk) . $buffer;
            $parts = explode("\n", $buffer);
            $buffer = array_shift($parts) ?? '';
            foreach (array_reverse($parts) as $line) {
                if ($line !== '') {
                    array_unshift($collected, $line);
                    if (count($collected) >= $lines) {
                        break;
                    }
                }
            }
        }

        if ($buffer !== '' && count($collected) < $lines) {
            array_unshift($collected, $buffer);
        }

        fclose($fp);

        return array_slice($collected, -$lines);
    }

    private static function write(string $level, string $message, array $context): void
    {
        $dir = App::basePath() . '/storage/logs';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $line = sprintf(
            "[%s] %s %s\n",
            date('c'),
            $level,
            $message . ($context ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '')
        );
        $file = $dir . '/app.log';
        if (is_file($file) && filesize($file) > 5 * 1024 * 1024) {
            rename($file, $dir . '/app-' . date('Y-m-d-His') . '.log');
        }
        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }
}
