<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\App;
use App\Helpers\Env;

Env::load(dirname(__DIR__) . '/.env');

if (($_ENV['APP_ENV'] ?? 'local') !== 'production') {
    try {
        App::bootstrap(dirname(__DIR__));
    } catch (Throwable) {
        // Testes unitários podem rodar sem DB
    }
}
