<?php

declare(strict_types=1);

namespace App\Models;

final class Conciliacao extends BaseModel
{
    protected string $table = 'conciliacoes';

    public function listar(int $empresaId): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.*, ct.nome AS conta_nome FROM conciliacoes c
             LEFT JOIN contas ct ON ct.id = c.conta_id
             WHERE c.empresa_id = :e ORDER BY c.criado_em DESC LIMIT 50'
        );
        $stmt->execute(['e' => $empresaId]);
        return $stmt->fetchAll();
    }

    public function itens(int $conciliacaoId): array
    {
        $stmt = $this->db->prepare(
            'SELECT ci.*, l.descricao AS lancamento_descricao FROM conciliacao_itens ci
             LEFT JOIN lancamentos l ON l.id = ci.lancamento_id
             WHERE ci.conciliacao_id = :id ORDER BY ci.data_movimento DESC, ci.id DESC'
        );
        $stmt->execute(['id' => $conciliacaoId]);
        return $stmt->fetchAll();
    }
}
