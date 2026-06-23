<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Policies\SuperAdminPolicy;

final class SuperAdminMiddleware
{
    public function __invoke(callable $next): void
    {
        SuperAdminPolicy::abortUnlessSuperadmin();
        $next();
    }
}
