#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Manutenção periódica: purge de rate_limits antigos.
 * Cron sugerido: 0 4 * * * php /caminho/bin/cron-maintenance.php
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\App;
use App\Core\Logger;

App::bootstrap(dirname(__DIR__));

try {
    $stmt = App::pdo()->exec('DELETE FROM rate_limits WHERE criado_em < DATE_SUB(NOW(), INTERVAL 7 DAY)');
    $n = $stmt === false ? 0 : $stmt;
    Logger::info('cron-maintenance: rate_limits purged', ['rows' => $n]);
    echo "OK: {$n} registros removidos de rate_limits\n";
} catch (Throwable $e) {
    Logger::error('cron-maintenance failed', ['error' => $e->getMessage()]);
    fwrite(STDERR, 'ERRO: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
