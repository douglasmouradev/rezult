<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Core\App;

/** Criptografia simétrica para segredos (integrações, etc.). */
final class Crypto
{
    public static function encrypt(string $plain): string
    {
        if ($plain === '') {
            return '';
        }
        $key = self::key();
        $iv = random_bytes(16);
        $cipher = openssl_encrypt($plain, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        if ($cipher === false) {
            throw new \RuntimeException('Falha ao criptografar.');
        }

        return base64_encode($iv . $cipher);
    }

    public static function decrypt(string $encoded, bool $strict = false): string
    {
        if ($encoded === '') {
            return '';
        }
        $raw = base64_decode($encoded, true);
        if ($raw === false || strlen($raw) < 17) {
            if ($strict) {
                throw new \RuntimeException('Dado criptografado inválido.');
            }

            return $encoded;
        }
        $iv = substr($raw, 0, 16);
        $cipher = substr($raw, 16);
        $plain = openssl_decrypt($cipher, 'AES-256-CBC', self::key(), OPENSSL_RAW_DATA, $iv);

        if ($plain === false) {
            if ($strict) {
                throw new \RuntimeException('Falha ao descriptografar.');
            }

            return $encoded;
        }

        return $plain;
    }

    public static function mask(?string $secret, int $visible = 4): string
    {
        if ($secret === null || $secret === '') {
            return '';
        }
        $len = strlen($secret);
        if ($len <= $visible) {
            return str_repeat('•', $len);
        }

        return str_repeat('•', max(8, $len - $visible)) . substr($secret, -$visible);
    }

    private static function key(): string
    {
        $key = (string) App::config('app_key', '');
        if ($key === '') {
            $key = hash('sha256', App::basePath() . (App::config('env') ?? 'local'), true);
        } else {
            $key = hash('sha256', $key, true);
        }

        return $key;
    }
}
