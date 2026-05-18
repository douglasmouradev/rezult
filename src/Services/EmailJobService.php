<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;

/** Lembretes de vencimento e resumo semanal */
final class EmailJobService
{
    public function __construct(private MailService $mail = new MailService()) {}

    public function enviarVencimentos(): int
    {
        $stmt = App::pdo()->query(
            "SELECT l.*, u.email, u.nome, e.nome AS empresa_nome
             FROM lancamentos l
             JOIN empresas e ON e.id = l.empresa_id
             JOIN usuario_empresa ue ON ue.empresa_id = l.empresa_id AND ue.papel IN ('dono','admin')
             JOIN usuarios u ON u.id = ue.usuario_id
             WHERE l.status = 'pendente' AND l.data_vencimento = CURDATE()
             AND u.anonimizado = 0"
        );
        $enviados = 0;
        foreach ($stmt->fetchAll() as $row) {
            $this->mail->enviar(
                $row['email'],
                'Rezult — Vencimento hoje',
                "Olá {$row['nome']},\n\nVence hoje: {$row['descricao']} — R$ {$row['valor']} ({$row['empresa_nome']})."
            );
            $enviados++;
        }
        return $enviados;
    }

    public function enviarResumoSemanal(): int
    {
        $stmt = App::pdo()->query(
            "SELECT u.id, u.email, u.nome, ue.empresa_id, e.nome AS empresa_nome
             FROM usuarios u
             JOIN usuario_empresa ue ON ue.usuario_id = u.id
             JOIN empresas e ON e.id = ue.empresa_id
             WHERE u.anonimizado = 0 AND ue.papel IN ('dono','admin')"
        );
        $enviados = 0;
        foreach ($stmt->fetchAll() as $row) {
            $tot = App::pdo()->prepare(
                "SELECT
                  COALESCE(SUM(CASE WHEN tipo='receita' AND status='pago' THEN valor END),0) AS r,
                  COALESCE(SUM(CASE WHEN tipo='despesa' AND status='pago' THEN valor END),0) AS d
                 FROM lancamentos WHERE empresa_id = :e AND data_lancamento >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
            );
            $tot->execute(['e' => $row['empresa_id']]);
            $t = $tot->fetch();
            $this->mail->enviar(
                $row['email'],
                'Rezult — Resumo semanal',
                "Resumo {$row['empresa_nome']}: Receitas R$ {$t['r']} | Despesas R$ {$t['d']}"
            );
            $enviados++;
        }
        return $enviados;
    }
}
