<?php

declare(strict_types=1);

use App\Helpers\GatewayWebhookAuth;
use App\Helpers\NfseMode;
use App\Services\GatewayCustomerService;
use PHPUnit\Framework\TestCase;

final class GatewayWebhookAuthTest extends TestCase
{
    public function testProducaoSemTokenRejeita(): void
    {
        $this->assertFalse(GatewayWebhookAuth::aceita('production', '', ''));
    }

    public function testProducaoComTokenValidoAceita(): void
    {
        $this->assertTrue(GatewayWebhookAuth::aceita('production', 'segredo', 'segredo'));
    }

    public function testProducaoComTokenInvalidoRejeita(): void
    {
        $this->assertFalse(GatewayWebhookAuth::aceita('production', 'segredo', 'errado'));
    }

    public function testLocalSemTokenAceita(): void
    {
        $this->assertTrue(GatewayWebhookAuth::aceita('local', '', ''));
    }
}

final class NfseModeTest extends TestCase
{
    public function testProducaoBloqueiaDemonstracao(): void
    {
        $this->assertFalse(NfseMode::permiteDemonstracao('production', false));
    }

    public function testProducaoComFlagPermite(): void
    {
        $this->assertTrue(NfseMode::permiteDemonstracao('production', true));
    }

    public function testLocalPermiteDemonstracao(): void
    {
        $this->assertTrue(NfseMode::permiteDemonstracao('local', false));
    }
}

final class GatewayCustomerKeyTest extends TestCase
{
    public function testChavePorEmail(): void
    {
        $chave = GatewayCustomerService::chaveCliente([
            'cliente_nome' => 'João',
            'cliente_email' => 'Joao@Empresa.com',
        ]);
        $this->assertSame('email:joao@empresa.com', $chave);
    }

    public function testChavePorNomeSemEmail(): void
    {
        $chave = GatewayCustomerService::chaveCliente([
            'cliente_nome' => 'Cliente X',
            'cliente_email' => '',
        ]);
        $this->assertStringStartsWith('nome:', $chave);
    }
}
