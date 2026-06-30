<?php

declare(strict_types=1);

use App\Helpers\FinancialMode;
use PHPUnit\Framework\TestCase;

final class FinancialModeTest extends TestCase
{
    public function testModoDemoPermiteSimulacao(): void
    {
        $this->assertTrue(FinancialMode::permiteSimulacao('demo', 'production', false));
    }

    public function testModoLiveProducaoBloqueiaSemGateway(): void
    {
        $this->assertFalse(FinancialMode::permiteSimulacao('live', 'production', false));
    }

    public function testModoLiveLocalPermiteSimulacao(): void
    {
        $this->assertTrue(FinancialMode::permiteSimulacao('live', 'local', false));
    }

    public function testGatewayAtivoNaoSimula(): void
    {
        $this->assertFalse(FinancialMode::permiteSimulacao('demo', 'local', true));
    }
}
