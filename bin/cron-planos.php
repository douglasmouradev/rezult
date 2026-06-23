<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\App;
use App\Helpers\Env;
use App\Services\EmailJobService;

Env::load(dirname(__DIR__) . '/.env');
App::bootstrap(dirname(__DIR__));

$job = new EmailJobService();
echo 'Avisos de plano: ' . $job->enviarAvisosPlano() . "\n";
