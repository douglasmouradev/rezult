#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Verifica ambiente, banco e schema. Uso: php bin/doctor.php
 */
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\App;
use App\Helpers\Env;

Env::load(dirname(__DIR__) . '/.env');

$ok = true;
$print = static function (string $status, string $msg) use (&$ok): void {
    if ($status === 'FAIL') {
        $ok = false;
    }
    echo str_pad($status, 5) . ' ' . $msg . PHP_EOL;
};

try {
    App::bootstrap(dirname(__DIR__));
    $print('OK', 'Bootstrap');
} catch (Throwable $e) {
    $print('FAIL', 'Bootstrap: ' . $e->getMessage());
    exit(1);
}

$env = (string) App::config('env');
$key = (string) App::config('app_key', '');
$health = trim((string) ($_ENV['HEALTH_TOKEN'] ?? ''));

if ($env === 'production') {
    $print(strlen($key) >= 32 ? 'OK' : 'FAIL', 'APP_KEY (mín. 32 chars)');
    $print($health !== '' ? 'OK' : 'FAIL', 'HEALTH_TOKEN');

    $financialMode = (string) App::config('financial_mode', 'demo');
    $print($financialMode === 'live' ? 'OK' : 'WARN', 'FINANCIAL_MODE=' . $financialMode . ' (recomendado: live)');

    $mailHost = trim((string) App::config('mail_host', ''));
    $mailUser = trim((string) App::config('mail_user', ''));
    $print($mailHost !== '' && $mailUser !== '' ? 'OK' : 'FAIL', 'SMTP (MAIL_HOST + MAIL_USER)');
} else {
    $print('OK', 'APP_ENV=' . $env);
}

try {
    $pdo = App::pdo();
    $pdo->query('SELECT 1');
    $print('OK', 'MySQL conectado');
} catch (Throwable $e) {
    $print('FAIL', 'MySQL: ' . $e->getMessage());
    exit(1);
}

$tabelasCriticas = [
    'empresas', 'lancamentos', 'categorias', 'contas', 'orcamentos',
    'cobrancas', 'conciliacoes', 'integracoes', 'webhooks', 'notificacoes',
    'centros_custo', 'metas', 'contatos', 'api_tokens', 'gateway_customers',
];

foreach ($tabelasCriticas as $t) {
    $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($t));
    $print($stmt->fetch() ? 'OK' : 'FAIL', "Tabela {$t}");
}

$colunas = [
    ['empresas', 'onboarding_concluido'],
    ['empresas', 'plano_ativo'],
    ['empresas', 'plano'],
    ['empresas', 'trial_ate'],
    ['cobrancas', 'gateway_id'],
    ['api_tokens', 'escopos'],
];

foreach ($colunas as [$tabela, $coluna]) {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c'
    );
    $stmt->execute(['t' => $tabela, 'c' => $coluna]);
    $print(((int) $stmt->fetchColumn()) > 0 ? 'OK' : 'FAIL', "Coluna {$tabela}.{$coluna}");
}

$log = App::basePath() . '/storage/logs/app.log';
if (is_file($log)) {
    echo PHP_EOL . '--- Últimos erros (app.log) ---' . PHP_EOL;
    $lines = array_slice(file($log) ?: [], -8);
    echo $lines ? implode('', $lines) : "(vazio)\n";
} else {
    $print('WARN', 'storage/logs/app.log não existe');
}

$sessionPath = App::basePath() . '/storage/sessions';
$print(is_writable($sessionPath) ? 'OK' : 'FAIL', 'storage/sessions gravável');

echo PHP_EOL . ($ok ? "Diagnóstico: tudo OK\n" : "Diagnóstico: há problemas — rode php bin/repair-schema.php\n");
exit($ok ? 0 : 1);
