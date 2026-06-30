<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PlanServiceTest extends TestCase
{
    public function testLimitesDefinidos(): void
    {
        $plan = new \App\Services\PlanService();
        $limites = $plan->limites();

        $this->assertSame(1, $limites['starter']['empresas']);
        $this->assertSame(10, $limites['pro']['usuarios']);
        $this->assertNull($limites['business']['empresas']);
    }

    public function testPlanoLabel(): void
    {
        $plan = new \App\Services\PlanService();
        $this->assertSame('Pro', $plan->planoLabel('pro'));
        $this->assertSame('Starter', $plan->planoLabel('starter'));
    }

    public function testMotivoBloqueioLojaDesabilitada(): void
    {
        $plan = new \App\Services\PlanService();
        $motivo = $plan->motivoBloqueio(['ativo' => 0, 'plano_ativo' => 1]);
        $this->assertStringContainsString('desabilitada', $motivo ?? '');
    }

    public function testMotivoBloqueioPlanoExpirado(): void
    {
        $plan = new \App\Services\PlanService();
        $motivo = $plan->motivoBloqueio([
            'ativo' => 1,
            'plano_ativo' => 1,
            'plano_expira_em' => '2020-01-01 00:00:00',
        ]);
        $this->assertStringContainsString('expirou', $motivo ?? '');
    }

    public function testMotivoBloqueioTrialExpirado(): void
    {
        $plan = new \App\Services\PlanService();
        $motivo = $plan->motivoBloqueio([
            'ativo' => 1,
            'plano_ativo' => 1,
            'trial_ate' => '2020-01-01 00:00:00',
        ]);
        $this->assertStringContainsString('trial', strtolower($motivo ?? ''));
    }

    public function testTrialExpiradoComPlanoPagoAtivoNaoBloqueia(): void
    {
        $plan = new \App\Services\PlanService();
        $motivo = $plan->motivoBloqueio([
            'ativo' => 1,
            'plano_ativo' => 1,
            'trial_ate' => '2020-01-01 00:00:00',
            'plano_expira_em' => date('Y-m-d H:i:s', strtotime('+30 days')),
        ]);
        $this->assertNull($motivo);
    }
}
