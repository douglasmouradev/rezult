<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Helpers\Session;

/** Gestão de planos, features e solicitações de upgrade. */
final class BillingService
{
    public function __construct(private PlanService $plan = new PlanService()) {}

    /** @return array<string, mixed> */
    public function resumoEmpresa(int $empresaId): array
    {
        $empresa = $this->plan->buscarEmpresa($empresaId);
        if (!$empresa) {
            return [];
        }

        $plano = $this->plan->planoEmpresa($empresaId);

        return [
            'plano' => $plano,
            'plano_label' => $this->plan->planoLabel($plano),
            'limites' => $this->plan->limites()[$plano],
            'features' => $this->plan->featuresPlano($plano),
            'bloqueio' => $this->plan->motivoBloqueio($empresa),
            'plano_expira_em' => $empresa['plano_expira_em'] ?? null,
            'trial_ate' => $empresa['trial_ate'] ?? null,
            'planos_disponiveis' => $this->plan->catalogoPlanos(),
        ];
    }

    public function solicitarUpgrade(int $empresaId, int $usuarioId, string $planoDesejado): void
    {
        if (!in_array($planoDesejado, ['pro', 'business'], true)) {
            throw new \InvalidArgumentException('Plano inválido.');
        }

        AuditoriaService::registrar('upgrade_solicitado', 'empresa', $empresaId, [
            'plano' => $planoDesejado,
            'usuario_id' => $usuarioId,
        ]);

        Session::flash(
            'success',
            'Solicitação de upgrade para ' . $this->plan->planoLabel($planoDesejado)
            . ' registrada. Nossa equipe entrará em contato em breve.'
        );
    }

    public function processarExpirados(): int
    {
        $stmt = App::pdo()->query(
            'SELECT id FROM empresas
             WHERE plano_ativo = 1 AND plano_expira_em IS NOT NULL AND plano_expira_em < NOW()'
        );
        $ids = $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
        if ($ids === []) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        App::pdo()->prepare("UPDATE empresas SET plano_ativo = 0 WHERE id IN ({$placeholders})")->execute($ids);

        return count($ids);
    }
}
