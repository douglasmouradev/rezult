<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;

final class SuperAdminService
{
    private const ATIVO_DIAS = 7;

    public function dashboard(): array
    {
        $pdo = App::pdo();

        return [
            'total_usuarios' => (int) $pdo->query(
                "SELECT COUNT(*) FROM usuarios WHERE excluido_em IS NULL AND (anonimizado = 0 OR anonimizado IS NULL)"
            )->fetchColumn(),
            'usuarios_ativos' => (int) $pdo->query(
                'SELECT COUNT(*) FROM usuarios
                 WHERE excluido_em IS NULL AND (anonimizado = 0 OR anonimizado IS NULL)
                 AND ultimo_login_em >= DATE_SUB(NOW(), INTERVAL ' . self::ATIVO_DIAS . ' DAY)'
            )->fetchColumn(),
            'total_empresas' => (int) $pdo->query('SELECT COUNT(*) FROM empresas')->fetchColumn(),
            'empresas_plano_ativo' => (int) $pdo->query(
                'SELECT COUNT(*) FROM empresas
                 WHERE ativo = 1 AND plano_ativo = 1
                 AND (plano_expira_em IS NULL OR plano_expira_em > NOW())'
            )->fetchColumn(),
            'empresas_desabilitadas' => (int) $pdo->query(
                'SELECT COUNT(*) FROM empresas WHERE ativo = 0 OR plano_ativo = 0
                 OR (plano_expira_em IS NOT NULL AND plano_expira_em <= NOW())'
            )->fetchColumn(),
            'logins_hoje' => (int) $pdo->query(
                'SELECT COUNT(*) FROM login_tentativas WHERE sucesso = 1 AND DATE(criado_em) = CURDATE()'
            )->fetchColumn(),
            'logins_7d' => (int) $pdo->query(
                'SELECT COUNT(*) FROM login_tentativas WHERE sucesso = 1 AND criado_em >= DATE_SUB(NOW(), INTERVAL 7 DAY)'
            )->fetchColumn(),
            'logins_30d' => (int) $pdo->query(
                'SELECT COUNT(*) FROM login_tentativas WHERE sucesso = 1 AND criado_em >= DATE_SUB(NOW(), INTERVAL 30 DAY)'
            )->fetchColumn(),
            'falhas_hoje' => (int) $pdo->query(
                'SELECT COUNT(*) FROM login_tentativas WHERE sucesso = 0 AND DATE(criado_em) = CURDATE()'
            )->fetchColumn(),
            'cadastros_30d' => (int) $pdo->query(
                'SELECT COUNT(*) FROM usuarios WHERE criado_em >= DATE_SUB(NOW(), INTERVAL 30 DAY)'
            )->fetchColumn(),
            'ativos_dias' => self::ATIVO_DIAS,
        ];
    }

    /** @return list<array<string, mixed>> */
    public function usuariosRecentes(int $limit = 10): array
    {
        $stmt = App::pdo()->prepare(
            'SELECT u.id, u.nome, u.email, u.ultimo_login_em, u.email_verificado, u.is_superadmin,
                    (SELECT COUNT(*) FROM usuario_empresa ue WHERE ue.usuario_id = u.id) AS empresas_qtd
             FROM usuarios u
             WHERE u.excluido_em IS NULL AND (u.anonimizado = 0 OR u.anonimizado IS NULL)
             ORDER BY u.ultimo_login_em IS NULL, u.ultimo_login_em DESC, u.criado_em DESC
             LIMIT :lim'
        );
        $stmt->bindValue('lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }

    /** @return list<array<string, mixed>> */
    public function listarUsuarios(): array
    {
        $stmt = App::pdo()->query(
            'SELECT u.id, u.nome, u.email, u.email_verificado, u.is_superadmin, u.criado_em, u.ultimo_login_em,
                    (SELECT COUNT(*) FROM usuario_empresa ue WHERE ue.usuario_id = u.id) AS empresas_qtd,
                    (SELECT COUNT(*) FROM remember_tokens rt WHERE rt.usuario_id = u.id AND rt.expira_em > NOW()) AS sessoes_lembrar
             FROM usuarios u
             WHERE u.excluido_em IS NULL AND (u.anonimizado = 0 OR u.anonimizado IS NULL)
             ORDER BY u.ultimo_login_em DESC, u.nome ASC'
        );

        return $stmt->fetchAll() ?: [];
    }

    /** @return list<array<string, mixed>> */
    public function listarEmpresas(?string $filtro = null): array
    {
        $where = '';
        if ($filtro === 'ativo') {
            $where = 'WHERE e.ativo = 1 AND e.plano_ativo = 1 AND (e.plano_expira_em IS NULL OR e.plano_expira_em > NOW())';
        } elseif ($filtro === 'inativo') {
            $where = 'WHERE e.ativo = 0 OR e.plano_ativo = 0 OR (e.plano_expira_em IS NOT NULL AND e.plano_expira_em <= NOW())';
        }

        $stmt = App::pdo()->query(
            "SELECT e.id, e.nome, e.cnpj, e.plano, e.ativo, e.plano_ativo, e.plano_expira_em, e.criado_em,
                    (SELECT COUNT(*) FROM usuario_empresa ue WHERE ue.empresa_id = e.id) AS membros_qtd,
                    (SELECT COUNT(*) FROM lancamentos l WHERE l.empresa_id = e.id) AS lancamentos_qtd,
                    (SELECT u.nome FROM usuario_empresa ue
                     INNER JOIN usuarios u ON u.id = ue.usuario_id
                     WHERE ue.empresa_id = e.id AND ue.papel = 'dono' LIMIT 1) AS dono_nome,
                    (SELECT u.email FROM usuario_empresa ue
                     INNER JOIN usuarios u ON u.id = ue.usuario_id
                     WHERE ue.empresa_id = e.id AND ue.papel = 'dono' LIMIT 1) AS dono_email
             FROM empresas e
             {$where}
             ORDER BY e.criado_em DESC"
        );

        return $stmt->fetchAll() ?: [];
    }

    public function statusPlano(array $empresa): string
    {
        $plan = new PlanService();
        if (!(int) ($empresa['ativo'] ?? 1)) {
            return 'desabilitada';
        }
        if ($plan->motivoBloqueio($empresa) !== null) {
            return 'plano_inativo';
        }

        return 'ativa';
    }

    public function atualizarEmpresa(int $id, array $dados): bool
    {
        $empresa = (new PlanService())->buscarEmpresa($id);
        if (!$empresa) {
            return false;
        }

        $plano = $dados['plano'] ?? $empresa['plano'] ?? 'starter';
        if (!in_array($plano, ['starter', 'pro', 'business'], true)) {
            $plano = 'starter';
        }

        $expira = trim((string) ($dados['plano_expira_em'] ?? ''));
        $expiraSql = $expira !== '' ? date('Y-m-d H:i:s', strtotime($expira)) : null;

        $stmt = App::pdo()->prepare(
            'UPDATE empresas SET plano = :p, plano_ativo = :pa, ativo = :a, plano_expira_em = :exp WHERE id = :id'
        );
        $stmt->execute([
            'p' => $plano,
            'pa' => !empty($dados['plano_ativo']) ? 1 : 0,
            'a' => !empty($dados['ativo']) ? 1 : 0,
            'exp' => $expiraSql,
            'id' => $id,
        ]);

        return true;
    }

    public function alternarAtivo(int $id): bool
    {
        $empresa = (new PlanService())->buscarEmpresa($id);
        if (!$empresa) {
            return false;
        }

        $novo = (int) ($empresa['ativo'] ?? 1) === 1 ? 0 : 1;
        App::pdo()->prepare('UPDATE empresas SET ativo = :a WHERE id = :id')->execute(['a' => $novo, 'id' => $id]);

        return $novo === 1;
    }

    public function alternarPlanoAtivo(int $id): bool
    {
        $empresa = (new PlanService())->buscarEmpresa($id);
        if (!$empresa) {
            return false;
        }

        $novo = (int) ($empresa['plano_ativo'] ?? 1) === 1 ? 0 : 1;
        App::pdo()->prepare('UPDATE empresas SET plano_ativo = :p WHERE id = :id')->execute(['p' => $novo, 'id' => $id]);

        return $novo === 1;
    }

    /** @return list<array<string, mixed>> */
    public function listarLogins(int $limit = 300): array
    {
        $stmt = App::pdo()->prepare(
            'SELECT lt.id, lt.email, lt.ip, lt.sucesso, lt.criado_em, u.nome AS usuario_nome, u.id AS usuario_id
             FROM login_tentativas lt
             LEFT JOIN usuarios u ON u.email = lt.email
             ORDER BY lt.criado_em DESC
             LIMIT :lim'
        );
        $stmt->bindValue('lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }

    public function estaAtivo(?string $ultimoLogin): bool
    {
        if ($ultimoLogin === null || $ultimoLogin === '') {
            return false;
        }

        return strtotime($ultimoLogin) >= strtotime('-' . self::ATIVO_DIAS . ' days');
    }

    public static function promoverPorEmail(string $email): bool
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return false;
        }

        $stmt = App::pdo()->prepare('UPDATE usuarios SET is_superadmin = 1 WHERE LOWER(email) = :e');
        $stmt->execute(['e' => $email]);

        return $stmt->rowCount() > 0;
    }

    public static function sincronizarSuperadminConfig(int $usuarioId, string $email): void
    {
        $configEmail = App::config('superadmin_email');
        if (!$configEmail || strtolower($email) !== strtolower($configEmail)) {
            return;
        }

        App::pdo()->prepare('UPDATE usuarios SET is_superadmin = 1 WHERE id = :id')->execute(['id' => $usuarioId]);
    }
}
