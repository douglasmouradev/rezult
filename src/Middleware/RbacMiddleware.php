<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Policies\TenantPolicy;
use Closure;

/** RBAC: config = admin/dono; finance = todos com acesso à empresa */
final class RbacMiddleware
{
    public function __construct(private string $nivel = 'config') {}

    public function __invoke(Closure $next): void
    {
        if ($this->nivel === 'config') {
            TenantPolicy::abortUnlessCanManageConfig();
        }
        $next();
    }
}
