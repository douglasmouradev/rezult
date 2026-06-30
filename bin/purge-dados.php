<?php

declare(strict_types=1);

/**
 * Remove todos os registros de dados da aplicação (usuários, empresas, lançamentos, etc.).
 * Mantém apenas migrations_log e o schema.
 *
 * Uso: php bin/purge-dados.php --confirm
 */
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Helpers\Env;

if (!in_array('--confirm', $argv ?? [], true)) {
    fwrite(STDERR, "ATENÇÃO: isto apaga TODOS os dados da aplicação.\n");
    fwrite(STDERR, "Para confirmar: php bin/purge-dados.php --confirm\n");
    exit(1);
}

$root = dirname(__DIR__);
Env::load($root . '/.env');
$db = require $root . '/config/database.php';

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $db['host'],
    $db['port'],
    $db['database'],
    $db['charset']
);
$pdo = new PDO($dsn, $db['username'], $db['password'], $db['options'] ?? []);

$preservar = ['migrations_log'];
$stmt = $pdo->query('SHOW TABLES');
$tabelas = [];
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    $nome = (string) $row[0];
    if (!in_array($nome, $preservar, true)) {
        $tabelas[] = $nome;
    }
}

echo "Banco: {$db['database']}\n";
echo 'Apagando ' . count($tabelas) . " tabelas de dados...\n";

$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
foreach ($tabelas as $t) {
    $pdo->exec("TRUNCATE TABLE `{$t}`");
    echo "  TRUNCATE {$t}\n";
}
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');

$limparDir = static function (string $dir, array $extensoes = []): int {
    if (!is_dir($dir)) {
        return 0;
    }
    $n = 0;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $item) {
        if ($item->isDir()) {
            continue;
        }
        if ($extensoes !== [] && !in_array(strtolower($item->getExtension()), $extensoes, true)) {
            continue;
        }
        if (@unlink($item->getPathname())) {
            $n++;
        }
    }

    return $n;
};

$uploads = $limparDir($root . '/storage/uploads');
$mail = $limparDir($root . '/storage/mail', ['eml']);
$sessoes = $limparDir($root . '/storage/sessions');

echo "Arquivos removidos: uploads={$uploads}, mail={$mail}, sessions={$sessoes}\n";
echo "Concluído. Banco zerado (schema e migrations preservados).\n";
echo "Para demo local: php bin/seed.php\n";
echo "Para superadmin: php bin/create-superadmin.php \"Nome\" email@exemplo.com \"SenhaForte123\"\n";
