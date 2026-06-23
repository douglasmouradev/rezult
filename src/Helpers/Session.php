<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Core\App;

final class Session
{
    public static function start(int $lifetime = 7200): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $savePath = App::basePath() . '/storage/sessions';
        if (!is_dir($savePath)) {
            mkdir($savePath, 0755, true);
        }
        if (is_writable($savePath)) {
            session_save_path($savePath);
        }

        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.gc_maxlifetime', (string) $lifetime);
        ini_set('session.cookie_path', '/');

        if (self::requestIsHttps()) {
            ini_set('session.cookie_secure', '1');
        }

        session_start();
    }

    public static function requestIsHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        $proto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        if ($proto === 'https') {
            return true;
        }

        $port = (int) ($_SERVER['SERVER_PORT'] ?? 0);

        return $port === 443;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function pull(string $key, mixed $default = null): mixed
    {
        $v = $_SESSION[$key] ?? $default;
        unset($_SESSION[$key]);
        return $v;
    }

    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][$type][] = $message;
    }

    /** @return array<string, array<string>> */
    public static function pullFlash(): array
    {
        $flash = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $flash;
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }
}
