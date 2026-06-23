<?php

declare(strict_types=1);

namespace App\Models;

final class Contato extends BaseModel
{
    protected string $table = 'contatos';

    /** @return array<int, array> */
    public function listar(int $empresaId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM contatos WHERE empresa_id = :e AND ativo = 1 ORDER BY nome'
        );
        $stmt->execute(['e' => $empresaId]);
        return $stmt->fetchAll();
    }

    public function salvar(array $data, int $empresaId): int
    {
        $data['empresa_id'] = $empresaId;
        $id = $data['id'] ?? null;
        return $this->save($data, $id ? $empresaId : null);
    }
}
