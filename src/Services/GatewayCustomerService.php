<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;

/** Cache de customer_id por cliente no gateway (evita duplicatas no Asaas). */
final class GatewayCustomerService
{
    public function buscar(int $empresaId, string $provedor, string $chave): ?string
    {
        $stmt = App::pdo()->prepare(
            'SELECT customer_id FROM gateway_customers
             WHERE empresa_id = :e AND gateway_provedor = :p AND chave = :c LIMIT 1'
        );
        $stmt->execute(['e' => $empresaId, 'p' => $provedor, 'c' => $chave]);
        $id = $stmt->fetchColumn();

        return is_string($id) && $id !== '' ? $id : null;
    }

    public function salvar(int $empresaId, string $provedor, string $chave, string $customerId): void
    {
        App::pdo()->prepare(
            'INSERT INTO gateway_customers (empresa_id, gateway_provedor, chave, customer_id)
             VALUES (:e, :p, :c, :cid)
             ON DUPLICATE KEY UPDATE customer_id = VALUES(customer_id)'
        )->execute([
            'e' => $empresaId,
            'p' => $provedor,
            'c' => $chave,
            'cid' => $customerId,
        ]);
    }

    /** @param array<string, mixed> $cobranca */
    public static function chaveCliente(array $cobranca): string
    {
        $email = strtolower(trim((string) ($cobranca['cliente_email'] ?? '')));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'email:' . $email;
        }

        $nome = mb_strtolower(trim((string) ($cobranca['cliente_nome'] ?? '')));

        return 'nome:' . hash('sha256', $nome);
    }
}
