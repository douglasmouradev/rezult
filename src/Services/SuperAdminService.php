<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;

final class SuperAdminService
{
    private const ATIVO_DIAS = 7;

    /** @var array<string, bool> */
    private static array $colunasCache = [];

    private function temColuna(string $tabela, string $coluna): bool
    {
        $key = $tabela . '.' . $coluna;
        if (!array_key_exists($key, self::$colunasCache)) {
            try {
                $stmt = App::pdo()->query("SHOW COLUMNS FROM {$tabela} LIKE " . App::pdo()->quote($coluna));
                self::$colunasCache[$key] = (bool) $stmt->fetch();
            } catch (\Throwable) {
                self::$colunasCache[$key] = false;
            }
        }

        return self::$colunasCache[$key];
    }

    private function limiteSql(int $limit, int $max = 500): int
    {
        return max(1, min($max, $limit));
    }

    public function dashboard(): array
    {
        $pdo = App::pdo();

        return [
            'total_usuarios' => (int) $pdo->query(
                "SELECT COUNT(*) FROM usuarios WHERE excluido_em IS NULL AND (anonimizado = 0 OR anonimizado IS NULL)"
            )->fetchColumn(),
            'usuarios_ativos' => $this->temColuna('usuarios', 'ultimo_login_em')
                ? (int) $pdo->query(
                    'SELECT COUNT(*) FROM usuarios
                     WHERE excluido_em IS NULL AND (anonimizado = 0 OR anonimizado IS NULL)
                     AND ultimo_login_em >= DATE_SUB(NOW(), INTERVAL ' . self::ATIVO_DIAS . ' DAY)'
                )->fetchColumn()
                : 0,
            'total_empresas' => (int) $pdo->query('SELECT COUNT(*) FROM empresas')->fetchColumn(),
            'empresas_plano_ativo' => $this->contarEmpresasPlanoAtivo(),
            'empresas_desabilitadas' => $this->contarEmpresasInativas(),
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
        $lim = $this->limiteSql($limit, 50);
        $stmt = App::pdo()->query(
            'SELECT u.id, u.nome, u.email, u.ultimo_login_em, u.email_verificado, u.is_superadmin,
                    (SELECT COUNT(*) FROM usuario_empresa ue WHERE ue.usuario_id = u.id) AS empresas_qtd
             FROM usuarios u
             WHERE u.excluido_em IS NULL AND (u.anonimizado = 0 OR u.anonimizado IS NULL)
             ORDER BY u.ultimo_login_em IS NULL, u.ultimo_login_em DESC, u.criado_em DESC
             LIMIT ' . $lim
        );

        return $stmt->fetchAll() ?: [];
    }

    /** @return list<array<string, mixed>> */
    public function listarUsuarios(?string $filtro = null): array
    {
        $temBloqueado = $this->temColuna('usuarios', 'bloqueado');
        $cols = 'u.id, u.nome, u.email, u.email_verificado, u.is_superadmin, u.criado_em, u.ultimo_login_em, u.excluido_em';
        if ($temBloqueado) {
            $cols .= ', u.bloqueado';
        }

        if ($filtro === 'bloqueados' && $temBloqueado) {
            $where = 'WHERE u.bloqueado = 1 AND u.excluido_em IS NULL AND (u.anonimizado = 0 OR u.anonimizado IS NULL)';
        } elseif ($filtro === 'excluidos') {
            $where = 'WHERE u.excluido_em IS NOT NULL OR u.anonimizado = 1';
        } else {
            $where = 'WHERE u.excluido_em IS NULL AND (u.anonimizado = 0 OR u.anonimizado IS NULL)';
            if ($filtro === 'ativos') {
                $where .= ' AND u.ultimo_login_em >= DATE_SUB(NOW(), INTERVAL ' . self::ATIVO_DIAS . ' DAY)';
                if ($temBloqueado) {
                    $where .= ' AND u.bloqueado = 0';
                }
            }
        }

        $stmt = App::pdo()->query(
            "SELECT {$cols},
                    (SELECT COUNT(*) FROM usuario_empresa ue WHERE ue.usuario_id = u.id) AS empresas_qtd,
                    (SELECT COUNT(*) FROM remember_tokens rt WHERE rt.usuario_id = u.id AND rt.expira_em > NOW()) AS sessoes_lembrar
             FROM usuarios u
             {$where}
             ORDER BY u.ultimo_login_em DESC, u.nome ASC"
        );

        return $stmt->fetchAll() ?: [];
    }

    public function buscarUsuario(int $id, bool $incluirExcluido = true): ?array
    {
        $sql = 'SELECT u.*,
                    (SELECT COUNT(*) FROM usuario_empresa ue WHERE ue.usuario_id = u.id) AS empresas_qtd,
                    (SELECT COUNT(*) FROM remember_tokens rt WHERE rt.usuario_id = u.id AND rt.expira_em > NOW()) AS sessoes_lembrar
                FROM usuarios u WHERE u.id = :id';
        if (!$incluirExcluido) {
            $sql .= ' AND u.excluido_em IS NULL AND (u.anonimizado = 0 OR u.anonimizado IS NULL)';
        }
        $stmt = App::pdo()->prepare($sql . ' LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @return list<array<string, mixed>> */
    public function empresasDoUsuario(int $usuarioId): array
    {
        $stmt = App::pdo()->prepare(
            'SELECT e.id, e.nome, ue.papel
             FROM usuario_empresa ue
             INNER JOIN empresas e ON e.id = ue.empresa_id
             WHERE ue.usuario_id = :u
             ORDER BY e.nome'
        );
        $stmt->execute(['u' => $usuarioId]);
        $rows = $stmt->fetchAll() ?: [];
        $plan = new PlanService();

        foreach ($rows as &$row) {
            $full = $plan->buscarEmpresa((int) $row['id']);
            if (is_array($full)) {
                $row = array_merge($row, $full);
            } else {
                $row['plano'] = 'starter';
                $row['ativo'] = 1;
                $row['plano_ativo'] = 1;
            }
        }
        unset($row);

        return $rows;
    }

    /** @return list<array<string, mixed>> */
    public function loginsDoUsuario(string $email, int $limit = 50): array
    {
        $lim = $this->limiteSql($limit, 100);
        $stmt = App::pdo()->prepare(
            'SELECT id, email, ip, sucesso, criado_em FROM login_tentativas
             WHERE email = :e ORDER BY criado_em DESC LIMIT ' . $lim
        );
        $stmt->execute(['e' => $email]);

        return $stmt->fetchAll() ?: [];
    }

    public function criarUsuario(string $nome, string $email, string $senha, bool $verificado = true, bool $superadmin = false): int
    {
        $model = new \App\Models\Usuario();
        if ($model->findByEmail($email)) {
            throw new \InvalidArgumentException('E-mail já cadastrado.');
        }

        $id = $model->criar($nome, $email, $senha);
        $params = ['v' => $verificado ? 1 : 0, 's' => $superadmin ? 1 : 0, 'id' => $id];
        if ($this->temColuna('usuarios', 'bloqueado')) {
            App::pdo()->prepare(
                'UPDATE usuarios SET email_verificado = :v, is_superadmin = :s, bloqueado = 0 WHERE id = :id'
            )->execute($params);
        } else {
            App::pdo()->prepare(
                'UPDATE usuarios SET email_verificado = :v, is_superadmin = :s WHERE id = :id'
            )->execute($params);
        }

        return $id;
    }

    public function atualizarUsuario(int $id, array $dados): bool
    {
        $usuario = $this->buscarUsuario($id);
        if (!$usuario || !empty($usuario['excluido_em']) || (int) ($usuario['anonimizado'] ?? 0) === 1) {
            return false;
        }

        $nome = trim((string) ($dados['nome'] ?? ''));
        $email = strtolower(trim((string) ($dados['email'] ?? '')));
        if ($nome === '' || $email === '') {
            throw new \InvalidArgumentException('Nome e e-mail são obrigatórios.');
        }

        $dup = App::pdo()->prepare('SELECT id FROM usuarios WHERE LOWER(email) = :e AND id != :id LIMIT 1');
        $dup->execute(['e' => $email, 'id' => $id]);
        if ($dup->fetch()) {
            throw new \InvalidArgumentException('E-mail já em uso por outro usuário.');
        }

        $sql = 'UPDATE usuarios SET nome = :n, email = :e, email_verificado = :v, is_superadmin = :s';
        $params = [
            'n' => $nome,
            'e' => $email,
            'v' => !empty($dados['email_verificado']) ? 1 : 0,
            's' => !empty($dados['is_superadmin']) ? 1 : 0,
            'id' => $id,
        ];
        if ($this->temColuna('usuarios', 'bloqueado')) {
            $sql .= ', bloqueado = :b';
            $params['b'] = !empty($dados['bloqueado']) ? 1 : 0;
        }
        $sql .= ' WHERE id = :id';
        App::pdo()->prepare($sql)->execute($params);

        return true;
    }

    public function redefinirSenha(int $id, string $senha): bool
    {
        $usuario = $this->buscarUsuario($id, false);
        if (!$usuario || !empty($usuario['excluido_em'])) {
            return false;
        }

        App::pdo()->prepare('UPDATE usuarios SET senha_hash = :h WHERE id = :id')->execute([
            'h' => password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]),
            'id' => $id,
        ]);

        return true;
    }

    public function alternarBloqueio(int $id): bool
    {
        if (!$this->temColuna('usuarios', 'bloqueado')) {
            return false;
        }

        $usuario = $this->buscarUsuario($id);
        if (!$usuario) {
            return false;
        }

        $novo = (int) ($usuario['bloqueado'] ?? 0) === 1 ? 0 : 1;
        App::pdo()->prepare('UPDATE usuarios SET bloqueado = :b WHERE id = :id')->execute(['b' => $novo, 'id' => $id]);
        if ($novo === 1) {
            $this->encerrarSessoes($id);
        }

        return $novo === 1;
    }

    public function encerrarSessoes(int $id): void
    {
        App::pdo()->prepare('DELETE FROM remember_tokens WHERE usuario_id = :id')->execute(['id' => $id]);
        App::pdo()->prepare('DELETE FROM tokens_email WHERE usuario_id = :id')->execute(['id' => $id]);
    }

    public function excluirUsuario(int $id): bool
    {
        $usuario = $this->buscarUsuario($id);
        if (!$usuario || !empty($usuario['excluido_em'])) {
            return false;
        }

        (new \App\Services\LgpdService())->processarExclusaoConta($id);

        return true;
    }

    public function statusUsuario(array $usuario): string
    {
        if (!empty($usuario['excluido_em']) || (int) ($usuario['anonimizado'] ?? 0) === 1) {
            return 'excluido';
        }
        if ((int) ($usuario['bloqueado'] ?? 0) === 1) {
            return 'bloqueado';
        }
        if ($this->estaAtivo($usuario['ultimo_login_em'] ?? null)) {
            return 'ativo';
        }

        return 'inativo';
    }

    /** @return list<array<string, mixed>> */
    public function listarEmpresas(?string $filtro = null): array
    {
        $temStatus = $this->temColuna('empresas', 'ativo') && $this->temColuna('empresas', 'plano_ativo');
        $cols = 'e.id, e.nome, e.cnpj, e.criado_em';
        if ($this->temColuna('empresas', 'plano')) {
            $cols .= ', e.plano';
        }
        if ($temStatus) {
            $cols .= ', e.ativo, e.plano_ativo, e.plano_expira_em';
        }

        $where = '';
        if ($temStatus) {
            if ($filtro === 'ativo') {
                $where = 'WHERE e.ativo = 1 AND e.plano_ativo = 1 AND (e.plano_expira_em IS NULL OR e.plano_expira_em > NOW())';
            } elseif ($filtro === 'inativo') {
                $where = 'WHERE e.ativo = 0 OR e.plano_ativo = 0 OR (e.plano_expira_em IS NOT NULL AND e.plano_expira_em <= NOW())';
            }
        }

        $stmt = App::pdo()->query(
            "SELECT {$cols},
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

    private function contarEmpresasPlanoAtivo(): int
    {
        if (!$this->temColuna('empresas', 'ativo')) {
            return (int) App::pdo()->query('SELECT COUNT(*) FROM empresas')->fetchColumn();
        }

        return (int) App::pdo()->query(
            'SELECT COUNT(*) FROM empresas
             WHERE ativo = 1 AND plano_ativo = 1
             AND (plano_expira_em IS NULL OR plano_expira_em > NOW())'
        )->fetchColumn();
    }

    private function contarEmpresasInativas(): int
    {
        if (!$this->temColuna('empresas', 'ativo')) {
            return 0;
        }

        return (int) App::pdo()->query(
            'SELECT COUNT(*) FROM empresas WHERE ativo = 0 OR plano_ativo = 0
             OR (plano_expira_em IS NOT NULL AND plano_expira_em <= NOW())'
        )->fetchColumn();
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
        $lim = $this->limiteSql($limit, 500);
        $stmt = App::pdo()->query(
            'SELECT lt.id, lt.email, lt.ip, lt.sucesso, lt.criado_em, u.nome AS usuario_nome, u.id AS usuario_id
             FROM login_tentativas lt
             LEFT JOIN usuarios u ON u.email = lt.email
             ORDER BY lt.criado_em DESC
             LIMIT ' . $lim
        );

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
