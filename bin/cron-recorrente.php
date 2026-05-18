<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\App;
use App\Helpers\Env;
use App\Services\RecorrenciaService;

Env::load(dirname(__DIR__) . '/.env');
App::bootstrap(dirname(__DIR__));

$n = (new RecorrenciaService())->processar();
echo "Recorrências geradas: {$n}\n";
