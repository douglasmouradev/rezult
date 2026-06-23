<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;

final class NotificationService
{
    public function criar(int $usuarioId, string $titulo, string $mensagem, ?int $empresaId = null): void
    {
        App::pdo()->prepare(
            'INSERT INTO notificacoes (usuario_id, empresa_id, titulo, mensagem) VALUES (:u,:e,:t,:m)'
        )->execute(['u' => $usuarioId, 'e' => $empresaId, 't' => $titulo, 'm' => $mensagem]);
    }

    public function listarNaoLidas(int $usuarioId, int $limit = 10): array
    {
        $stmt = App::pdo()->prepare(
            'SELECT * FROM notificacoes WHERE usuario_id = :u AND lida = 0 ORDER BY criado_em DESC LIMIT :l'
        );
        $stmt->bindValue(':u', $usuarioId, \PDO::PARAM_INT);
        $stmt->bindValue(':l', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function listarTodas(int $usuarioId, int $limit = 50): array
    {
        $stmt = App::pdo()->prepare(
            'SELECT * FROM notificacoes WHERE usuario_id = :u ORDER BY criado_em DESC LIMIT :l'
        );
        $stmt->bindValue(':u', $usuarioId, \PDO::PARAM_INT);
        $stmt->bindValue(':l', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function contarNaoLidas(int $usuarioId): int
    {
        $stmt = App::pdo()->prepare('SELECT COUNT(*) FROM notificacoes WHERE usuario_id = :u AND lida = 0');
        $stmt->execute(['u' => $usuarioId]);
        return (int) $stmt->fetchColumn();
    }

    public function marcarLida(int $id, int $usuarioId): void
    {
        App::pdo()->prepare('UPDATE notificacoes SET lida = 1 WHERE id = :id AND usuario_id = :u')
            ->execute(['id' => $id, 'u' => $usuarioId]);
    }

    public function marcarTodasLidas(int $usuarioId): void
    {
        App::pdo()->prepare('UPDATE notificacoes SET lida = 1 WHERE usuario_id = :u')->execute(['u' => $usuarioId]);
    }
}
