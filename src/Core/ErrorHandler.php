<?php

declare(strict_types=1);

namespace App\Core;

use App\Helpers\Session;
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
}
