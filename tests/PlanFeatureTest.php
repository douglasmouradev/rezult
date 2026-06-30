<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PlanFeatureTest extends TestCase
{
    public function testStarterNaoTemApi(): void
    {
        $plan = new \App\Services\PlanService();
        $this->assertNotContains('api', $plan->featuresPlano('starter'));
        $this->assertContains('api', $plan->featuresPlano('pro'));
        $this->assertContains('assistente_ia', $plan->featuresPlano('business'));
    }

    public function testLimitesTokensApi(): void
    {
        $plan = new \App\Services\PlanService();
        $this->assertSame(0, $plan->limites()['starter']['api_tokens']);
        $this->assertSame(3, $plan->limites()['pro']['api_tokens']);
        $this->assertNull($plan->limites()['business']['api_tokens']);
    public function testBusinessTemNfseEOpenFinance(): void
    {
        $plan = new \App\Services\PlanService();
        $this->assertContains('nfse', $plan->featuresPlano('business'));
        $this->assertContains('open_finance', $plan->featuresPlano('business'));
        $this->assertNotContains('nfse', $plan->featuresPlano('pro'));
        $this->assertNotContains('open_finance', $plan->featuresPlano('pro'));
    }
}
