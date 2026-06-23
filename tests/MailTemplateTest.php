<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class MailTemplateTest extends TestCase
{
    protected function setUp(): void
    {
        \App\Helpers\Env::load(dirname(__DIR__) . '/.env');
        \App\Core\App::bootstrap(dirname(__DIR__));
    }

    public function testConfirmacaoContemLink(): void
    {
        $tpl = \App\Helpers\MailTemplate::confirmacao('João', 'https://rezult.app/auth/confirmacao?token=abc');
        $this->assertStringContainsString('Confirme seu e-mail', $tpl['subject']);
        $this->assertStringContainsString('https://rezult.app/auth/confirmacao?token=abc', $tpl['html']);
        $this->assertStringContainsString('João', $tpl['text']);
    }

    public function testConviteContemEmpresa(): void
    {
        $tpl = \App\Helpers\MailTemplate::convite('Minha Loja', 'https://rezult.app/convite/x');
        $this->assertStringContainsString('Minha Loja', $tpl['subject']);
        $this->assertStringContainsString('Aceitar convite', $tpl['html']);
    }

    public function testLayoutHtmlValido(): void
    {
        $html = \App\Helpers\MailTemplate::layout('Título', '<p>Corpo</p>');
        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('Título', $html);
    }
}
