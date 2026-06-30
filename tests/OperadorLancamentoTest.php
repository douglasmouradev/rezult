<?php

declare(strict_types=1);

use App\Enums\PapelEmpresa;
use App\Services\LancamentoService;
use PHPUnit\Framework\TestCase;

final class OperadorLancamentoTest extends TestCase
{
    public function testOperadorNaoMarcaPagoNaCriacao(): void
    {
        $status = LancamentoService::resolverStatusParaPapel('pago', null, PapelEmpresa::Operador);
        $this->assertSame('aguardando_aprovacao', $status);
    }

    public function testOperadorNaoMarcaPagoNaEdicao(): void
    {
        $status = LancamentoService::resolverStatusParaPapel('pago', 'pendente', PapelEmpresa::Operador);
        $this->assertSame('aguardando_aprovacao', $status);
    }

    public function testOperadorMantemLancamentoJaPago(): void
    {
        $status = LancamentoService::resolverStatusParaPapel('pago', 'pago', PapelEmpresa::Operador);
        $this->assertSame('pago', $status);
    }

    public function testAdminPodeMarcarPago(): void
    {
        $status = LancamentoService::resolverStatusParaPapel('pago', 'pendente', PapelEmpresa::Admin);
        $this->assertSame('pago', $status);
    }
}
