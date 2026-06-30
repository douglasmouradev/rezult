#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Backup diário do MySQL.
 * Cron: 0 2 * * * php /caminho/bin/cron-backup.php
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\App;
use App\Core\Logger;
use App\Helpers\Env;

Env::load(dirname(__DIR__) . '/.env');

$db = require dirname(__DIR__) . '/config/database.php';
$dir = dirname(__DIR__) . '/storage/backups';
if (!is_dir($dir)) {
    mkdir($dir, 0750, true);
}

$filename = 'rezult_' . date('Y-m-d_His') . '.sql.gz';
$path = $dir . '/' . $filename;

$host = escapeshellarg((string) $db['host']);
$user = escapeshellarg((string) $db['username']);
$pass = escapeshellarg((string) $db['password']);
$name = escapeshellarg((string) $db['database']);
$port = (int) $db['port'];

$cmd = sprintf(
    'mysqldump -h %s -P %d -u %s -p%s %s 2>&1 | gzip > %s',
    $host,
    $port,
    $user,
    $pass,
    $name,
    escapeshellarg($path)
);

exec($cmd, $output, $code);
if ($code !== 0 || !is_file($path) || filesize($path) < 100) {
    Logger::error('cron-backup failed', ['output' => implode("\n", $output), 'code' => $code]);
    fwrite(STDERR, "ERRO no backup\n");
    exit(1);
}

// Retenção: 14 dias
foreach (glob($dir . '/rezult_*.sql.gz') ?: [] as $file) {
    if (filemtime($file) < time() - 14 * 86400) {
        @unlink($file);
    }
}

Logger::info('cron-backup ok', ['file' => $filename, 'bytes' => filesize($path)]);
echo "OK: {$filename}\n";
