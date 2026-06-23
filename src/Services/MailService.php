<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Logger;

/** Envio de e-mail — SMTP, mail() ou gravação em storage/mail (dev) */
final class MailService
{
    /** @param array{subject: string, html: string, text: string} $template */
    public function enviarTemplate(string $para, array $template): bool
    {
        return $this->enviarHtml($para, $template['subject'], $template['html'], $template['text']);
    }

    public function enviar(string $para, string $assunto, string $corpo): bool
    {
        return $this->enviarHtml($para, $assunto, '<pre style="font-family:inherit">' . htmlspecialchars($corpo, ENT_QUOTES, 'UTF-8') . '</pre>', $corpo);
    }

    public function enviarHtml(string $para, string $assunto, string $html, string $texto = ''): bool
    {
        $texto = $texto !== '' ? $texto : strip_tags($html);
        $smtpHost = App::config('mail_host');

        if ($smtpHost && App::config('mail_user')) {
            $ok = $this->enviarSmtp($para, $assunto, $html, $texto);
            Logger::info('E-mail SMTP', ['para' => $para, 'ok' => $ok]);
            return $ok;
        }

        if ($smtpHost) {
            $from = $this->formatarFrom();
            $headers = "From: {$from}\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";
            $ok = @mail($para, $this->encodeSubject($assunto), $html, $headers);
            Logger::info('E-mail mail()', ['para' => $para, 'ok' => $ok]);
            return $ok;
        }

        return $this->gravarDev($para, $assunto, $html, $texto);
    }

    private function gravarDev(string $para, string $assunto, string $html, string $texto): bool
    {
        $from = $this->formatarFrom();
        $dir = App::basePath() . '/storage/mail';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $msg = "To: {$para}\nFrom: {$from}\nSubject: {$assunto}\nContent-Type: text/html\n\n{$html}\n\n--- plain ---\n{$texto}";
        file_put_contents($dir . '/' . date('Ymd_His') . '_' . md5($para . $assunto) . '.eml', $msg);
        Logger::info('E-mail gravado (dev)', ['para' => $para, 'assunto' => $assunto]);
        return true;
    }

    private function formatarFrom(): string
    {
        $from = App::config('mail_from');
        $name = App::config('mail_from_name', 'Rezult');

        return "{$name} <{$from}>";
    }

    private function encodeSubject(string $subject): string
    {
        return '=?UTF-8?B?' . base64_encode($subject) . '?=';
    }

    private function enviarSmtp(string $para, string $assunto, string $html, string $plain): bool
    {
        $host = (string) App::config('mail_host');
        $port = (int) App::config('mail_port', 587);
        $user = (string) App::config('mail_user');
        $pass = (string) App::config('mail_password');
        $from = (string) App::config('mail_from');
        $fromName = (string) App::config('mail_from_name', 'Rezult');
        $encryption = (string) App::config('mail_encryption', 'tls');

        $remote = ($encryption === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
        $fp = @stream_socket_client($remote, $errno, $errstr, 15);
        if (!$fp) {
            Logger::error('SMTP conexão falhou', ['errno' => $errno, 'errstr' => $errstr]);
            return false;
        }

        stream_set_timeout($fp, 15);
        $read = static function () use ($fp): string {
            $data = '';
            while ($line = fgets($fp, 512)) {
                $data .= $line;
                if (isset($line[3]) && $line[3] === ' ') {
                    break;
                }
            }
            return $data;
        };
        $write = static function (string $cmd) use ($fp): void {
            fwrite($fp, $cmd . "\r\n");
        };

        $read();
        $ehloHost = parse_url((string) App::config('url'), PHP_URL_HOST) ?: 'localhost';
        $write("EHLO {$ehloHost}");
        $read();

        if ($encryption === 'tls' && $port !== 465) {
            $write('STARTTLS');
            $resp = $read();
            if (!str_starts_with($resp, '220')) {
                fclose($fp);
                return false;
            }
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($fp);
                return false;
            }
            $write("EHLO {$ehloHost}");
            $read();
        }

        $write('AUTH LOGIN');
        $read();
        $write(base64_encode($user));
        $read();
        $write(base64_encode($pass));
        if (!str_starts_with($read(), '235')) {
            fclose($fp);
            return false;
        }

        $write("MAIL FROM:<{$from}>");
        $read();
        $write("RCPT TO:<{$para}>");
        $read();
        $write('DATA');
        $read();

        $boundary = 'rezult_' . bin2hex(random_bytes(8));
        $message = implode("\r\n", [
            "From: {$fromName} <{$from}>",
            "To: {$para}",
            'Subject: ' . $this->encodeSubject($assunto),
            'MIME-Version: 1.0',
            "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
            '',
            "--{$boundary}",
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            '',
            $plain,
            "--{$boundary}",
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            '',
            $html,
            "--{$boundary}--",
            '',
        ]);

        fwrite($fp, $message . "\r\n.\r\n");
        $ok = str_starts_with($read(), '250');
        $write('QUIT');
        fclose($fp);

        return $ok;
    }
}
