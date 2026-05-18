<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testPapelEmpresaAdminPodeGerenciar(): void
    {
        $this->assertTrue(\App\Enums\PapelEmpresa::Admin->podeGerenciarEmpresa());
        $this->assertFalse(\App\Enums\PapelEmpresa::Operador->podeGerenciarEmpresa());
    }
}
