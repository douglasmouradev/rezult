#!/usr/bin/env php
<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\App;
use App\Services\SuperAdminService;

App::bootstrap(dirname(__DIR__));

$email = $argv[1] ?? App::config('superadmin_email') ?? '';

if ($email === '') {
    fwrite(STDERR, "Uso: php bin/promote-superadmin.php email@exemplo.com\n");
    fwrite(STDERR, "Ou defina SUPERADMIN_EMAIL no .env\n");
    exit(1);
}

if (!SuperAdminService::promoverPorEmail($email)) {
    fwrite(STDERR, "Usuário não encontrado: {$email}\n");
    exit(1);
}

echo "Superadmin promovido: {$email}\n";
