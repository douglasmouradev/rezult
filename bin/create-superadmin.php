#!/usr/bin/env php
<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\App;
use App\Models\Usuario;

App::bootstrap(dirname(__DIR__));

$nome = $argv[1] ?? '';
$email = strtolower(trim($argv[2] ?? ''));
$senha = $argv[3] ?? '';

if ($nome === '' || $email === '' || $senha === '') {
    fwrite(STDERR, "Uso: php bin/create-superadmin.php \"Nome Completo\" email@exemplo.com \"SenhaForte123\"\n");
    exit(1);
}

if (mb_strlen($senha) < 8) {
    fwrite(STDERR, "Senha deve ter no mínimo 8 caracteres.\n");
    exit(1);
}

$pdo = App::pdo();
$usuarioModel = new Usuario();
$existente = $usuarioModel->findByEmail($email);

if ($existente) {
    $id = (int) $existente['id'];
    $pdo->prepare(
        'UPDATE usuarios SET nome = :n, senha_hash = :h, email_verificado = 1, is_superadmin = 1, excluido_em = NULL, anonimizado = 0 WHERE id = :id'
    )->execute([
        'n' => $nome,
        'h' => password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]),
        'id' => $id,
    ]);
    echo "Superadmin atualizado (usuário já existia): {$email} (id {$id})\n";
} else {
    $id = $usuarioModel->criar($nome, $email, $senha);
    $pdo->prepare('UPDATE usuarios SET email_verificado = 1, is_superadmin = 1 WHERE id = :id')
        ->execute(['id' => $id]);
    echo "Superadmin criado: {$email} (id {$id})\n";
}

echo "Acesse /superadmin após fazer login.\n";
