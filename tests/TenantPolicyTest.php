<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class TenantPolicyTest extends TestCase
{
    public function testPapeisDefinidos(): void
    {
        $ref = new ReflectionClass(\App\Policies\TenantPolicy::class);
        $this->assertTrue($ref->hasMethod('abortUnlessCanManageEmpresa'));
        $this->assertTrue($ref->hasMethod('abortUnlessEmpresaAccess'));
    }
}
