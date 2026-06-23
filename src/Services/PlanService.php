<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;

final class PlanService
{
    private const LIMITES = [
        'starter' => ['empresas' => 1, 'usuarios' => 1],
        'pro' => ['empresas' => 5, 'usuarios' => 10],
        'business' => ['empresas' => null, 'usuarios' => null],
    ];

    /** @return array<string, array{empresas: ?int, usuarios: ?int}> */
    public function limites(): array
    {
        return self::LIMITES;
    }

    public function planoEmpresa(int $empresaId): string
    {
        $stmt = App::pdo()->prepare('SELECT plano FROM empresas WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $empresaId]);
        $plano = $stmt->fetchColumn();
        return is_string($plano) && isset(self::LIMITES[$plano]) ? $plano : 'starter';
    }

    public function podeCriarEmpresa(int $userId): bool
    {
        $limite = self::LIMITES[$this->planoUsuario($userId)]['empresas'];
        if ($limite === null) {
            return true;
        }

        $stmt = App::pdo()->prepare(
            "SELECT COUNT(*) FROM usuario_empresa WHERE usuario_id = :u AND papel = 'dono'"
        );
        $stmt->execute(['u' => $userId]);

        return (int) $stmt->fetchColumn() < $limite;
    }

    public function podeConvidar(int $empresaId): bool
    {
        $limite = self::LIMITES[$this->planoEmpresa($empresaId)]['usuarios'];
        if ($limite === null) {
            return true;
        }

        $stmt = App::pdo()->prepare('SELECT COUNT(*) FROM usuario_empresa WHERE empresa_id = :e');
        $stmt->execute(['e' => $empresaId]);
        $membros = (int) $stmt->fetchColumn();

        $stmt = App::pdo()->prepare(
            'SELECT COUNT(*) FROM convites WHERE empresa_id = :e AND aceito_em IS NULL AND expira_em > NOW()'
        );
        $stmt->execute(['e' => $empresaId]);
        $pendentes = (int) $stmt->fetchColumn();

        return ($membros + $pendentes) < $limite;
    }

    private function planoUsuario(int $userId): string
    {
        $stmt = App::pdo()->prepare(
            "SELECT e.plano FROM empresas e
             INNER JOIN usuario_empresa ue ON ue.empresa_id = e.id
             WHERE ue.usuario_id = :u AND ue.papel = 'dono'
             AND e.ativo = 1 AND e.plano_ativo = 1
             AND (e.plano_expira_em IS NULL OR e.plano_expira_em > NOW())
             ORDER BY FIELD(e.plano, 'business', 'pro', 'starter')
             LIMIT 1"
        );
        $stmt->execute(['u' => $userId]);
        $plano = $stmt->fetchColumn();

        return is_string($plano) && isset(self::LIMITES[$plano]) ? $plano : 'starter';
    }

    /** @param array<string, mixed> $empresa */
    public function empresaOperacional(array $empresa): bool
    {
        return $this->motivoBloqueio($empresa) === null;
    }

    /** @param array<string, mixed> $empresa */
    public function motivoBloqueio(array $empresa): ?string
    {
        if (isset($empresa['ativo']) && !(int) $empresa['ativo']) {
            return 'Esta loja foi desabilitada pelo administrador da plataforma.';
        }
        if (isset($empresa['plano_ativo']) && !(int) $empresa['plano_ativo']) {
            return 'O plano desta loja está inativo. Entre em contato com o suporte.';
        }
        if (!empty($empresa['plano_expira_em']) && strtotime((string) $empresa['plano_expira_em']) < time()) {
            return 'O plano desta loja expirou em ' . date('d/m/Y', strtotime((string) $empresa['plano_expira_em'])) . '.';
        }

        return null;
    }

    public function buscarEmpresa(int $empresaId): ?array
    {
        $stmt = App::pdo()->prepare('SELECT * FROM empresas WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $empresaId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function planoLabel(string $plano): string
    {
        return match ($plano) {
            'pro' => 'Pro',
            'business' => 'Business',
            default => 'Starter',
        };
    }
}
