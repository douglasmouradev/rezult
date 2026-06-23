<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Helpers\MailTemplate;

/** Lembretes de vencimento, resumo semanal e avisos de plano */
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
            $tpl = MailTemplate::vencimento(
                $row['nome'],
                $row['descricao'],
                number_format((float) $row['valor'], 2, ',', '.'),
                $row['empresa_nome'],
            );
            if ($this->mail->enviarTemplate($row['email'], $tpl)) {
                $enviados++;
            }
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
            $tpl = MailTemplate::resumoSemanal(
                $row['nome'],
                $row['empresa_nome'],
                number_format((float) $t['r'], 2, ',', '.'),
                number_format((float) $t['d'], 2, ',', '.'),
            );
            if ($this->mail->enviarTemplate($row['email'], $tpl)) {
                $enviados++;
            }
        }
        return $enviados;
    }

    public function enviarAvisosPlano(): int
    {
        if (!$this->temColunaAviso()) {
            return 0;
        }

        $enviados = 0;
        $enviados += $this->avisarPlano(7, 'plano_aviso_7d_em');
        $enviados += $this->avisarPlano(1, 'plano_aviso_1d_em');

        return $enviados;
    }

    private function avisarPlano(int $dias, string $colunaAviso): int
    {
        $stmt = App::pdo()->prepare(
            "SELECT e.id, e.nome, e.plano, e.plano_expira_em, u.email, u.nome AS usuario_nome
             FROM empresas e
             JOIN usuario_empresa ue ON ue.empresa_id = e.id AND ue.papel = 'dono'
             JOIN usuarios u ON u.id = ue.usuario_id AND u.anonimizado = 0
             WHERE e.ativo = 1 AND e.plano_ativo = 1
             AND e.plano_expira_em IS NOT NULL
             AND DATE(e.plano_expira_em) = DATE_ADD(CURDATE(), INTERVAL :d DAY)
             AND e.{$colunaAviso} IS NULL"
        );
        $stmt->execute(['d' => $dias]);

        $plan = new PlanService();
        $enviados = 0;

        foreach ($stmt->fetchAll() as $row) {
            $dataExpira = date('d/m/Y', strtotime((string) $row['plano_expira_em']));
            $tpl = MailTemplate::planoExpirando(
                $row['usuario_nome'],
                $row['nome'],
                $plan->planoLabel($row['plano']),
                $dataExpira,
                $dias,
            );
            if ($this->mail->enviarTemplate($row['email'], $tpl)) {
                App::pdo()->prepare("UPDATE empresas SET {$colunaAviso} = NOW() WHERE id = :id")
                    ->execute(['id' => $row['id']]);
                $enviados++;
            }
        }

        return $enviados;
    }

    private function temColunaAviso(): bool
    {
        try {
            $stmt = App::pdo()->query("SHOW COLUMNS FROM empresas LIKE 'plano_aviso_7d_em'");
            return (bool) $stmt->fetch();
        } catch (\Throwable) {
            return false;
        }
    }
}
