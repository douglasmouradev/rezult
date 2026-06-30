<?php

declare(strict_types=1);

namespace App\Core;

use App\Helpers\Session;
use App\Services\MailService;
use Throwable;

final class ErrorHandler
{
    public static function register(): void
    {
        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
    }

    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        throw new \ErrorException($message, 0, $severity, $file, $line);
    }

    public static function handleException(Throwable $e): void
    {
        Logger::error($e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        self::alertarEquipe($e);

        if (!headers_sent()) {
            http_response_code(500);
        }

        if (App::config('debug')) {
            echo '<pre>' . htmlspecialchars($e->getMessage() . "\n" . $e->getTraceAsString()) . '</pre>';
            return;
        }

        $layout = Session::get('usuario_id') ? 'app' : 'guest';
        try {
            View::render('errors/500', ['title' => 'Erro interno'], layout: $layout);
        } catch (Throwable) {
            echo 'Erro interno. Tente novamente mais tarde.';
        }
    }

    private static function alertarEquipe(Throwable $e): void
    {
        $email = trim((string) ($_ENV['ERROR_ALERT_EMAIL'] ?? ''));
        if ($email === '' || App::config('env') !== 'production') {
            return;
        }

        static $enviado = false;
        if ($enviado) {
            return;
        }
        $enviado = true;

        try {
            $corpo = "Erro em " . App::config('url') . "\n\n"
                . $e->getMessage() . "\n"
                . $e->getFile() . ':' . $e->getLine();
            (new MailService())->enviar($email, '[Rezult] Erro crítico', $corpo);
        } catch (Throwable) {
            /* evita loop */
        }
    }
}
