<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Core\App;

/** Verifica existência de tabelas/colunas (produção com migrations incompletas). */
final class Schema
{
    public static function tabelaExiste(string $tabela): bool
    {
        try {
            $pdo = App::pdo();
            $stmt = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($tabela));

            return (bool) $stmt->fetch();
        } catch (\Throwable) {
            return false;
        }
    }

    public static function colunaExiste(string $tabela, string $coluna): bool
    {
        try {
            $stmt = App::pdo()->prepare(
                'SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c'
            );
            $stmt->execute(['t' => $tabela, 'c' => $coluna]);

            return (int) $stmt->fetchColumn() > 0;
        } catch (\Throwable) {
            return false;
        }
    }
}
