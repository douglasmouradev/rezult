<?php

declare(strict_types=1);

namespace App\Core;

use App\Helpers\DateTimeBr;
use App\Helpers\Env;
use App\Helpers\Session;
use PDO;

final class App
{
    private static ?PDO $pdo = null;
    private static array $config = [];

    public static function bootstrap(string $basePath): void
    {
        Env::load($basePath . '/.env');
        self::$config['app'] = require $basePath . '/config/app.php';
        self::$config['database'] = require $basePath . '/config/database.php';

        DateTimeBr::init((string) self::config('timezone', 'America/Sao_Paulo'));

        self::validateEnvironment();

        Session::start(self::config('app.session_lifetime'));

        self::securityHeaders();
    }

    private static function validateEnvironment(): void
    {
        if (self::config('env') !== 'production') {
            return;
        }

        $key = (string) self::config('app_key', '');
        if (strlen($key) < 32) {
            throw new \RuntimeException(
                'APP_KEY obrigatório em produção (mínimo 32 caracteres). Gere com: openssl rand -hex 32'
            );
        }

        $healthToken = trim((string) ($_ENV['HEALTH_TOKEN'] ?? ''));
        if ($healthToken === '') {
            throw new \RuntimeException(
                'HEALTH_TOKEN obrigatório em produção para proteger /health.'
            );
        }
    }

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $db = self::config('database');
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $db['host'],
                $db['port'],
                $db['database'],
                $db['charset']
            );
            self::$pdo = new PDO($dsn, $db['username'], $db['password'], $db['options']);
            $offset = DateTimeBr::mysqlOffset();
            self::$pdo->exec("SET time_zone = " . self::$pdo->quote($offset));
        }
        return self::$pdo;
    }

    public static function config(string $key, mixed $default = null): mixed
    {
        $parts = explode('.', $key);
        $value = self::$config;
        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                // Atalho: chaves de app.php sem prefixo "app."
                if (count($parts) === 1 && isset(self::$config['app'][$key])) {
                    return self::$config['app'][$key];
                }
                return $default;
            }
            $value = $value[$part];
        }
        return $value;
    }

    public static function basePath(): string
    {
        return dirname(__DIR__, 2);
    }

    private static function securityHeaders(): void
    {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://unpkg.com; font-src 'self' https://fonts.gstatic.com https://unpkg.com; img-src 'self' data: blob:; connect-src 'self'");
        if (self::config('env') === 'production') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}
