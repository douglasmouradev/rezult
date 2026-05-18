<?php

declare(strict_types=1);

namespace App\Models;

final class Categoria extends BaseModel
{
    protected string $table = 'categorias';

    public function porTipo(int $empresaId, string $tipo): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM categorias WHERE empresa_id = :e AND tipo = :t ORDER BY nome'
        );
        $stmt->execute(['e' => $empresaId, 't' => $tipo]);
        return $stmt->fetchAll();
    }
}
