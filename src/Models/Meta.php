<?php

declare(strict_types=1);

namespace App\Models;

final class Meta extends BaseModel
{
    protected string $table = 'metas';

    public function atualizarProgresso(int $metaId, int $empresaId): void
    {
        $sql = "UPDATE metas m SET valor_atual = (
            SELECT COALESCE(SUM(l.valor), 0) FROM lancamentos l
            WHERE l.meta_id = m.id AND l.empresa_id = :empresa_sub AND l.status = 'pago' AND l.tipo = 'receita'
        ) WHERE m.id = :id AND m.empresa_id = :empresa_main";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $metaId,
            'empresa_sub' => $empresaId,
            'empresa_main' => $empresaId,
        ]);
    }
}
