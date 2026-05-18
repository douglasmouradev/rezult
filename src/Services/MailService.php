<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Logger;

/** Envio de e-mail — em dev grava em storage/mail */
final class MailService
{
    public function enviar(string $para, string $assunto, string $corpo): bool
    {
        $from = App::config('mail_from');
        $smtpHost = App::config('mail_host');

        if ($smtpHost) {
            $headers = "From: {$from}\r\nContent-Type: text/plain; charset=UTF-8\r\n";
            $ok = @mail($para, $assunto, $corpo, $headers);
            Logger::info('E-mail SMTP', ['para' => $para, 'ok' => $ok]);
            return $ok;
        }

        $dir = App::basePath() . '/storage/mail';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $msg = "To: {$para}\nFrom: {$from}\nSubject: {$assunto}\n\n{$corpo}";
        file_put_contents($dir . '/' . date('Ymd_His') . '_' . md5($para) . '.eml', $msg);
        Logger::info('E-mail gravado (dev)', ['para' => $para, 'assunto' => $assunto]);
        return true;
    }
}
