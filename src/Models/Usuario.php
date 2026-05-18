<?php

declare(strict_types=1);

namespace App\Models;

final class Usuario extends BaseModel
{
    protected string $table = 'usuarios';

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM usuarios WHERE email = :email AND (excluido_em IS NULL) AND (anonimizado = 0 OR anonimizado IS NULL) LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function criar(string $nome, string $email, string $senha): int
    {
        return $this->save([
            'nome' => $nome,
            'email' => $email,
            'senha_hash' => password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]),
            'email_verificado' => 0,
        ]);
    }

    public function verificarSenha(array $usuario, string $senha): bool
    {
        return password_verify($senha, $usuario['senha_hash']);
    }
}
