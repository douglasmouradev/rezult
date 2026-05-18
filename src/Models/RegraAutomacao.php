<?php

declare(strict_types=1);

namespace App\Models;

final class RegraAutomacao extends BaseModel
{
    protected string $table = 'regras_automacao';

    /** @return array<int, array> */
    public function listarAtivas(int $empresaId, ?string $gatilho = null): array
    {
        $sql = 'SELECT * FROM regras_automacao WHERE empresa_id = :e AND ativo = 1';
        $params = ['e' => $empresaId];
        if ($gatilho) {
            $sql .= ' AND gatilho = :g';
            $params['g'] = $gatilho;
        }
        $sql .= ' ORDER BY id ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
