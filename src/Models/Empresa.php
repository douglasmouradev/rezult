<?php

declare(strict_types=1);

namespace App\Models;

final class Empresa extends BaseModel
{
    protected string $table = 'empresas';

    public function listarPorUsuario(int $usuarioId): array
    {
        $sql = 'SELECT e.*, ue.papel
                FROM empresas e
                INNER JOIN usuario_empresa ue ON ue.empresa_id = e.id
                WHERE ue.usuario_id = :uid
                ORDER BY e.nome';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid' => $usuarioId]);
        return $stmt->fetchAll();
    }

    public function usuarioTemAcesso(int $usuarioId, int $empresaId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM usuario_empresa WHERE usuario_id = :u AND empresa_id = :e LIMIT 1'
        );
        $stmt->execute(['u' => $usuarioId, 'e' => $empresaId]);
        return (bool) $stmt->fetch();
    }

    public function vincularUsuario(int $usuarioId, int $empresaId, string $papel): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO usuario_empresa (usuario_id, empresa_id, papel) VALUES (:u, :e, :p)
             ON DUPLICATE KEY UPDATE papel = VALUES(papel)'
        );
        $stmt->execute(['u' => $usuarioId, 'e' => $empresaId, 'p' => $papel]);
    }

    public function papelUsuario(int $usuarioId, int $empresaId): ?string
    {
        $stmt = $this->db->prepare(
            'SELECT papel FROM usuario_empresa WHERE usuario_id = :u AND empresa_id = :e'
        );
        $stmt->execute(['u' => $usuarioId, 'e' => $empresaId]);
        $row = $stmt->fetch();
        return $row['papel'] ?? null;
    }
}
