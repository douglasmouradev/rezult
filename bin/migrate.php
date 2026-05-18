<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\App;
use App\Helpers\Env;

Env::load(dirname(__DIR__) . '/.env');
App::bootstrap(dirname(__DIR__));

$pdo = App::pdo();
$pdo->exec('CREATE TABLE IF NOT EXISTS migrations_log (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  arquivo VARCHAR(120) NOT NULL UNIQUE,
  aplicado_em DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

$dir = dirname(__DIR__) . '/migrations';
$aplicados = $pdo->query('SELECT arquivo FROM migrations_log')->fetchAll(PDO::FETCH_COLUMN);
$pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

foreach (glob($dir . '/*.sql') as $file) {
    $nome = basename($file);
    if (in_array($nome, $aplicados, true)) {
        echo "Pulando {$nome} (já aplicado)\n";
        continue;
    }
    echo "Executando {$nome}...\n";
    try {
        $sql = file_get_contents($file);
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
            if ($stmt !== '' && !str_starts_with($stmt, '--')) {
                $pdo->exec($stmt);
            }
        }
        $pdo->prepare('INSERT INTO migrations_log (arquivo) VALUES (?)')->execute([$nome]);
        echo "OK\n";
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), 'already exists')) {
            $pdo->prepare('INSERT IGNORE INTO migrations_log (arquivo) VALUES (?)')->execute([$nome]);
            echo "OK (já existia)\n";
        } else {
            throw $e;
        }
    }
}

echo "Migrations concluídas.\n";
