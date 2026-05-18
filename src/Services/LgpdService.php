<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Helpers\Session;

final class LgpdService
{
    public const VERSAO_TERMOS = '1.0';
    public const VERSAO_PRIVACIDADE = '1.0';

    public function registrarConsentimentos(int $usuarioId, bool $marketing): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
        $stmt = App::pdo()->prepare(
            'INSERT INTO consentimentos (usuario_id, tipo, versao, aceito, ip, user_agent) VALUES (:u, :t, :v, 1, :ip, :ua)'
        );
        foreach (['termos', 'privacidade'] as $tipo) {
            $stmt->execute([
                'u' => $usuarioId,
                't' => $tipo,
                'v' => $tipo === 'termos' ? self::VERSAO_TERMOS : self::VERSAO_PRIVACIDADE,
                'ip' => $ip,
                'ua' => $ua,
            ]);
        }
        $pdo = App::pdo();
        $pdo->prepare(
            'INSERT INTO consentimentos (usuario_id, tipo, versao, aceito, ip, user_agent) VALUES (:u, :t, :v, :a, :ip, :ua)'
        )->execute([
            'u' => $usuarioId,
            't' => 'marketing',
            'v' => '1.0',
            'a' => $marketing ? 1 : 0,
            'ip' => $ip,
            'ua' => $ua,
        ]);
        if ($this->colunaExiste('usuarios', 'marketing_optin')) {
            App::pdo()->prepare('UPDATE usuarios SET marketing_optin = :m WHERE id = :id')
                ->execute(['m' => $marketing ? 1 : 0, 'id' => $usuarioId]);
        }
    }

    private function colunaExiste(string $tabela, string $coluna): bool
    {
        $stmt = App::pdo()->prepare(
            'SELECT 1 FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c LIMIT 1'
        );
        $stmt->execute(['t' => $tabela, 'c' => $coluna]);
        return (bool) $stmt->fetch();
    }

    public function solicitar(int $usuarioId, string $tipo): int
    {
        $stmt = App::pdo()->prepare(
            'INSERT INTO lgpd_solicitacoes (usuario_id, tipo) VALUES (:u, :t)'
        );
        $stmt->execute(['u' => $usuarioId, 't' => $tipo]);
        AuditoriaService::registrar('lgpd_solicitacao', 'lgpd', (int) App::pdo()->lastInsertId(), ['tipo' => $tipo]);
        return (int) App::pdo()->lastInsertId();
    }

    /** Exporta dados pessoais do titular (portabilidade Art. 18) */
    public function exportarDadosUsuario(int $usuarioId): array
    {
        $pdo = App::pdo();
        $cols = 'id, nome, email, criado_em';
        if ($this->colunaExiste('usuarios', 'marketing_optin')) {
            $cols .= ', marketing_optin';
        }
        $usuario = $pdo->prepare("SELECT {$cols} FROM usuarios WHERE id = :id");
        $usuario->execute(['id' => $usuarioId]);
        $u = $usuario->fetch();

        $empresas = $pdo->prepare(
            'SELECT e.nome, e.cnpj, ue.papel FROM empresas e
             JOIN usuario_empresa ue ON ue.empresa_id = e.id WHERE ue.usuario_id = :id'
        );
        $empresas->execute(['id' => $usuarioId]);

        $consent = $pdo->prepare('SELECT tipo, versao, aceito, criado_em FROM consentimentos WHERE usuario_id = :id');
        $consent->execute(['id' => $usuarioId]);

        return [
            'exportado_em' => date('c'),
            'titular' => $u,
            'empresas_vinculadas' => $empresas->fetchAll(),
            'consentimentos' => $consent->fetchAll(),
            'nota' => 'Dados financeiros das empresas permanecem vinculados ao controlador (sua organização).',
        ];
    }

    public function registrarConsentimentoVisitante(): void
    {
        $hash = hash('sha256', session_id() . ($_SERVER['REMOTE_ADDR'] ?? ''));
        App::pdo()->prepare(
            'INSERT INTO consentimentos_visitante (session_hash, ip, user_agent) VALUES (:s,:ip,:ua)'
        )->execute([
            's' => $hash,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
        ]);
    }

    public function processarExclusoesAgendadas(): int
    {
        $stmt = App::pdo()->query(
            "SELECT usuario_id FROM lgpd_solicitacoes
             WHERE tipo = 'exclusao' AND status = 'pendente'
             AND criado_em <= DATE_SUB(NOW(), INTERVAL 15 DAY)"
        );
        $n = 0;
        foreach ($stmt->fetchAll() as $row) {
            $this->processarExclusaoConta((int) $row['usuario_id']);
            $n++;
        }
        return $n;
    }

    public function registrarConsentimentoCookies(?int $usuarioId = null): void
    {
        $uid = $usuarioId ?? (int) Session::get('usuario_id');
        if ($uid <= 0) {
            return;
        }
        App::pdo()->prepare(
            'INSERT INTO consentimentos (usuario_id, tipo, versao, aceito, ip, user_agent)
             VALUES (:u, :t, :v, 1, :ip, :ua)'
        )->execute([
            'u' => $uid,
            't' => 'cookies',
            'v' => '1.0',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
        ]);
    }

    public function exportarDadosEmpresa(int $empresaId): array
    {
        $pdo = App::pdo();
        $empresa = $pdo->prepare('SELECT id, nome, cnpj, moeda, criado_em FROM empresas WHERE id = :id');
        $empresa->execute(['id' => $empresaId]);
        $contas = $pdo->prepare('SELECT id, nome, tipo, saldo_inicial, ativo FROM contas WHERE empresa_id = :e');
        $contas->execute(['e' => $empresaId]);
        $cats = $pdo->prepare('SELECT id, nome, tipo, cor FROM categorias WHERE empresa_id = :e');
        $cats->execute(['e' => $empresaId]);
        $lanc = $pdo->prepare(
            'SELECT id, tipo, descricao, valor, data_lancamento, status, criado_em
             FROM lancamentos WHERE empresa_id = :e ORDER BY data_lancamento DESC LIMIT 5000'
        );
        $lanc->execute(['e' => $empresaId]);

        return [
            'exportado_em' => date('c'),
            'empresa' => $empresa->fetch(),
            'contas' => $contas->fetchAll(),
            'categorias' => $cats->fetchAll(),
            'lancamentos' => $lanc->fetchAll(),
        ];
    }

    public function retificarDados(int $usuarioId, array $dados): void
    {
        $nome = trim($dados['nome'] ?? '');
        if ($nome === '') {
            throw new \InvalidArgumentException('Nome é obrigatório.');
        }
        App::pdo()->prepare('UPDATE usuarios SET nome = :n WHERE id = :id AND anonimizado = 0')
            ->execute(['n' => $nome, 'id' => $usuarioId]);
        $this->solicitar($usuarioId, 'retificacao');
        AuditoriaService::registrar('lgpd_retificacao', 'usuario', $usuarioId);
    }

    public function processarExclusaoConta(int $usuarioId): void
    {
        $pdo = App::pdo();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'UPDATE usuarios SET nome = :n, email = :e, anonimizado = 1, excluido_em = NOW(), senha_hash = :h, avatar_url = NULL WHERE id = :id'
            )->execute([
                'n' => 'Usuário removido',
                'e' => 'excluido_' . $usuarioId . '@anonimo.local',
                'h' => password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT),
                'id' => $usuarioId,
            ]);
            $pdo->prepare('DELETE FROM remember_tokens WHERE usuario_id = :id')->execute(['id' => $usuarioId]);
            $pdo->prepare('DELETE FROM tokens_email WHERE usuario_id = :id')->execute(['id' => $usuarioId]);
            $pdo->prepare(
                'UPDATE lgpd_solicitacoes SET status = :s, processado_em = NOW() WHERE usuario_id = :id AND tipo = :t AND status = :p'
            )->execute(['s' => 'concluida', 'id' => $usuarioId, 't' => 'exclusao', 'p' => 'pendente']);
            $pdo->commit();
            AuditoriaService::registrar('lgpd_exclusao_conta', 'usuario', $usuarioId);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
