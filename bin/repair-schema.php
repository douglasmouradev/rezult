#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Repara colunas/tabelas ausentes sem depender só do migrations_log.
 * Uso: php bin/repair-schema.php
 */
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\App;
use App\Helpers\Env;

Env::load(dirname(__DIR__) . '/.env');
App::bootstrap(dirname(__DIR__));

$pdo = App::pdo();

$addColumn = static function (string $table, string $column, string $definition) use ($pdo): void {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c'
    );
    $stmt->execute(['t' => $table, 'c' => $column]);
    if ((int) $stmt->fetchColumn() > 0) {
        echo "  skip {$table}.{$column}\n";
        return;
    }
    $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
    echo "  + {$table}.{$column}\n";
};

$execSql = static function (string $label, string $sql) use ($pdo): void {
    try {
        $pdo->exec($sql);
        echo "OK {$label}\n";
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), 'already exists')) {
            echo "skip {$label}\n";
            return;
        }
        echo "ERRO {$label}: {$e->getMessage()}\n";
    }
};

echo "==> Tabelas\n";
$execSql('orcamentos', 'CREATE TABLE IF NOT EXISTS orcamentos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  empresa_id BIGINT UNSIGNED NOT NULL,
  categoria_id BIGINT UNSIGNED NULL,
  mes CHAR(7) NOT NULL,
  valor_planejado DECIMAL(15,2) NOT NULL,
  FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
  FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL,
  UNIQUE KEY uk_orcamento (empresa_id, categoria_id, mes)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
$execSql('gateway_webhook_eventos', "CREATE TABLE IF NOT EXISTS gateway_webhook_eventos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provedor VARCHAR(30) NOT NULL,
    evento_id VARCHAR(120) NOT NULL,
    processado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_gateway_evento (provedor, evento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

echo "==> Colunas empresas\n";
$addColumn('empresas', 'plano', "VARCHAR(20) NOT NULL DEFAULT 'starter'");
$addColumn('empresas', 'ativo', 'TINYINT(1) NOT NULL DEFAULT 1');
$addColumn('empresas', 'plano_ativo', 'TINYINT(1) NOT NULL DEFAULT 1');
$addColumn('empresas', 'plano_expira_em', 'DATETIME NULL');
$addColumn('empresas', 'trial_ate', 'DATETIME NULL');
$addColumn('empresas', 'onboarding_concluido', 'TINYINT(1) NOT NULL DEFAULT 0');
$addColumn('empresas', 'plano_aviso_7d_em', 'DATETIME NULL');
$addColumn('empresas', 'plano_aviso_1d_em', 'DATETIME NULL');

echo "==> Colunas cobrancas\n";
$addColumn('cobrancas', 'gateway_id', 'VARCHAR(80) NULL');
$addColumn('cobrancas', 'gateway_provedor', 'VARCHAR(30) NULL');

echo "==> Colunas api_tokens\n";
$addColumn('api_tokens', 'escopos', "ENUM('read','read_write') NOT NULL DEFAULT 'read_write'");

echo "==> Colunas lancamentos\n";
$addColumn('lancamentos', 'parceiro', 'VARCHAR(200) NULL');
$addColumn('lancamentos', 'conciliado_em', 'DATETIME NULL');
$addColumn('lancamentos', 'centro_custo_id', 'BIGINT UNSIGNED NULL');
$addColumn('lancamentos', 'aprovado_por', 'BIGINT UNSIGNED NULL');
$addColumn('lancamentos', 'aprovado_em', 'DATETIME NULL');
$addColumn('lancamentos', 'contato_id', 'BIGINT UNSIGNED NULL');

echo "==> Colunas usuarios\n";
$addColumn('usuarios', 'is_superadmin', 'TINYINT(1) NOT NULL DEFAULT 0');
$addColumn('usuarios', 'bloqueado', 'TINYINT(1) NOT NULL DEFAULT 0');

echo "Reparo concluído.\n";
