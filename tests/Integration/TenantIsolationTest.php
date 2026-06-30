<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class TenantIsolationTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!extension_loaded('pdo_mysql')) {
            self::markTestSkipped('pdo_mysql não disponível');
        }
        try {
            $pdo = \App\Core\App::pdo();
            $pdo->query('SELECT 1');
        } catch (Throwable $e) {
            self::markTestSkipped('Banco indisponível: ' . $e->getMessage());
        }
    }

    public function testLancamentosRespeitamEmpresaId(): void
    {
        $pdo = \App\Core\App::pdo();
        $stmt = $pdo->query(
            'SELECT l.empresa_id, COUNT(*) AS c FROM lancamentos l
             GROUP BY l.empresa_id HAVING c > 0 LIMIT 5'
        );
        $rows = $stmt->fetchAll();
        $this->assertIsArray($rows);
        foreach ($rows as $row) {
            $eid = (int) $row['empresa_id'];
            $check = $pdo->prepare('SELECT COUNT(*) FROM lancamentos WHERE empresa_id = :e');
            $check->execute(['e' => $eid]);
            $this->assertSame((int) $row['c'], (int) $check->fetchColumn());
        }
    }

    public function testCobrancasTemIndiceGateway(): void
    {
        $pdo = \App\Core\App::pdo();
        $stmt = $pdo->query("SHOW INDEX FROM cobrancas WHERE Key_name = 'idx_cobrancas_gateway'");
        $this->assertNotFalse($stmt->fetch());
    }
}
