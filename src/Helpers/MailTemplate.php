<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Core\App;

/** Templates HTML para e-mails transacionais */
final class MailTemplate
{
    public static function layout(string $titulo, string $conteudo): string
    {
        $app = htmlspecialchars(App::config('name', 'Rezult'), ENT_QUOTES, 'UTF-8');
        $ano = date('Y');

        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f6f8;font-family:Segoe UI,Roboto,Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;padding:24px 0">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06)">
<tr><td style="background:#1e3a5f;padding:20px 28px">
  <span style="color:#fff;font-size:20px;font-weight:700">{$app}</span>
</td></tr>
<tr><td style="padding:28px">
  <h1 style="margin:0 0 16px;font-size:22px;color:#1a1a2e">{$titulo}</h1>
  {$conteudo}
</td></tr>
<tr><td style="padding:16px 28px;background:#f8fafc;border-top:1px solid #e8ecf0">
  <p style="margin:0;font-size:12px;color:#8892a0">© {$ano} {$app}. Gestão financeira empresarial.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;
    }

    public static function botao(string $url, string $texto): string
    {
        $url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        $texto = htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');

        return '<p style="margin:24px 0"><a href="' . $url . '" style="display:inline-block;background:#2563eb;color:#fff;text-decoration:none;padding:12px 24px;border-radius:6px;font-weight:600">' . $texto . '</a></p>';
    }

    /** @return array{subject: string, html: string, text: string} */
    public static function confirmacao(string $nome, string $link): array
    {
        $n = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
        $html = self::layout(
            'Confirme seu e-mail',
            "<p>Olá <strong>{$n}</strong>,</p>
            <p>Obrigado por criar sua conta. Clique no botão abaixo para confirmar seu e-mail e começar a usar o Rezult.</p>"
            . self::botao($link, 'Confirmar e-mail')
            . '<p style="font-size:13px;color:#8892a0">Se você não criou esta conta, ignore este e-mail.</p>'
        );

        return [
            'subject' => 'Confirme seu e-mail — Rezult',
            'html' => $html,
            'text' => "Olá {$nome},\n\nConfirme seu e-mail: {$link}\n",
        ];
    }

    /** @return array{subject: string, html: string, text: string} */
    public static function recuperacao(string $nome, string $link): array
    {
        $n = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
        $html = self::layout(
            'Redefinir senha',
            "<p>Olá <strong>{$n}</strong>,</p>
            <p>Recebemos uma solicitação para redefinir sua senha. O link expira em 24 horas.</p>"
            . self::botao($link, 'Criar nova senha')
            . '<p style="font-size:13px;color:#8892a0">Se você não solicitou, ignore este e-mail.</p>'
        );

        return [
            'subject' => 'Redefinir senha — Rezult',
            'html' => $html,
            'text' => "Olá {$nome},\n\nRedefina sua senha: {$link}\n",
        ];
    }

    /** @return array{subject: string, html: string, text: string} */
    public static function convite(string $empresa, string $link): array
    {
        $e = htmlspecialchars($empresa, ENT_QUOTES, 'UTF-8');
        $html = self::layout(
            'Convite para equipe',
            "<p>Você foi convidado para participar da empresa <strong>{$e}</strong> no Rezult.</p>"
            . self::botao($link, 'Aceitar convite')
        );

        return [
            'subject' => "Convite — {$empresa}",
            'html' => $html,
            'text' => "Você foi convidado para {$empresa}. Acesse: {$link}\n",
        ];
    }

    /** @return array{subject: string, html: string, text: string} */
    public static function vencimento(string $nome, string $descricao, string $valor, string $empresa): array
    {
        $n = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
        $d = htmlspecialchars($descricao, ENT_QUOTES, 'UTF-8');
        $v = htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
        $e = htmlspecialchars($empresa, ENT_QUOTES, 'UTF-8');
        $html = self::layout(
            'Vencimento hoje',
            "<p>Olá <strong>{$n}</strong>,</p>
            <p>O lançamento abaixo vence <strong>hoje</strong>:</p>
            <table style='width:100%;border-collapse:collapse;margin:16px 0'>
            <tr><td style='padding:8px;border:1px solid #e8ecf0'>Descrição</td><td style='padding:8px;border:1px solid #e8ecf0'><strong>{$d}</strong></td></tr>
            <tr><td style='padding:8px;border:1px solid #e8ecf0'>Valor</td><td style='padding:8px;border:1px solid #e8ecf0'><strong>R$ {$v}</strong></td></tr>
            <tr><td style='padding:8px;border:1px solid #e8ecf0'>Empresa</td><td style='padding:8px;border:1px solid #e8ecf0'>{$e}</td></tr>
            </table>"
            . self::botao(App::config('url') . '/lancamentos', 'Ver lançamentos')
        );

        return [
            'subject' => 'Vencimento hoje — Rezult',
            'html' => $html,
            'text' => "Olá {$nome},\n\nVence hoje: {$descricao} — R$ {$valor} ({$empresa}).\n",
        ];
    }

    /** @return array{subject: string, html: string, text: string} */
    public static function resumoSemanal(string $nome, string $empresa, string $receitas, string $despesas): array
    {
        $n = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
        $e = htmlspecialchars($empresa, ENT_QUOTES, 'UTF-8');
        $html = self::layout(
            'Resumo semanal',
            "<p>Olá <strong>{$n}</strong>,</p>
            <p>Resumo dos últimos 7 dias em <strong>{$e}</strong>:</p>
            <table style='width:100%;border-collapse:collapse;margin:16px 0'>
            <tr><td style='padding:8px;border:1px solid #e8ecf0;color:#16a34a'>Receitas</td><td style='padding:8px;border:1px solid #e8ecf0'><strong>R$ {$receitas}</strong></td></tr>
            <tr><td style='padding:8px;border:1px solid #e8ecf0;color:#dc2626'>Despesas</td><td style='padding:8px;border:1px solid #e8ecf0'><strong>R$ {$despesas}</strong></td></tr>
            </table>"
            . self::botao(App::config('url') . '/dashboard', 'Abrir dashboard')
        );

        return [
            'subject' => 'Resumo semanal — Rezult',
            'html' => $html,
            'text' => "Resumo {$empresa}: Receitas R$ {$receitas} | Despesas R$ {$despesas}\n",
        ];
    }

    /** @return array{subject: string, html: string, text: string} */
    public static function planoExpirando(string $nome, string $empresa, string $plano, string $dataExpira, int $dias): array
    {
        $n = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
        $e = htmlspecialchars($empresa, ENT_QUOTES, 'UTF-8');
        $p = htmlspecialchars($plano, ENT_QUOTES, 'UTF-8');
        $d = htmlspecialchars($dataExpira, ENT_QUOTES, 'UTF-8');
        $html = self::layout(
            'Plano expirando',
            "<p>Olá <strong>{$n}</strong>,</p>
            <p>O plano <strong>{$p}</strong> da empresa <strong>{$e}</strong> expira em <strong>{$dias} dia(s)</strong> ({$d}).</p>
            <p>Renove para continuar usando todos os recursos sem interrupção.</p>"
            . self::botao(App::config('url') . '/configuracoes', 'Gerenciar plano')
        );

        return [
            'subject' => "Plano expira em {$dias} dia(s) — Rezult",
            'html' => $html,
            'text' => "Plano {$plano} de {$empresa} expira em {$dias} dia(s) ({$dataExpira}).\n",
        ];
    }

    /** @return array{subject: string, html: string, text: string} */
    public static function cobranca(string $descricao, string $valor, string $corpoExtra): array
    {
        $d = htmlspecialchars($descricao, ENT_QUOTES, 'UTF-8');
        $v = htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
        $extra = nl2br(htmlspecialchars($corpoExtra, ENT_QUOTES, 'UTF-8'));
        $html = self::layout(
            'Cobrança',
            "<p><strong>{$d}</strong></p>
            <p>Valor: <strong>R$ {$v}</strong></p>
            <p>{$extra}</p>"
        );

        return [
            'subject' => "Cobrança: {$descricao}",
            'html' => $html,
            'text' => "{$descricao}\nValor: R$ {$valor}\n\n{$corpoExtra}",
        ];
    }
}
