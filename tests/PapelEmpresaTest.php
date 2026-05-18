<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PapelEmpresaTest extends TestCase
{
    public function testOperadorNaoExcluiLancamento(): void
    {
        $this->assertFalse(\App\Enums\PapelEmpresa::Operador->podeExcluirLancamento());
        $this->assertTrue(\App\Enums\PapelEmpresa::Admin->podeExcluirLancamento());
    }
}
