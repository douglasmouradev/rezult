<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\App;
use App\Helpers\Env;

Env::load(dirname(__DIR__) . '/.env');
App::bootstrap(dirname(__DIR__));
$pdo = App::pdo();

echo "Gerando seed de demonstração (6 meses)...\n";

$senha = password_hash('Senha@123', PASSWORD_BCRYPT, ['cost' => 12]);

$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
foreach (['lancamentos','metas','categorias','contas','convites','usuario_empresa','empresas','tokens_email','remember_tokens','login_tentativas','usuarios'] as $t) {
    $pdo->exec("TRUNCATE TABLE {$t}");
}
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');

$pdo->prepare('INSERT INTO usuarios (nome, email, senha_hash, email_verificado) VALUES (?,?,?,1)')
    ->execute(['Ana Silva', 'demo@rezult.app', $senha]);

$pdo->prepare('INSERT INTO empresas (nome, cnpj, moeda) VALUES (?,?,?)')
    ->execute(['Studio Criativo Ltda', '12.345.678/0001-90', 'BRL']);
$empresaId = (int) $pdo->lastInsertId();

$pdo->prepare('INSERT INTO usuario_empresa (usuario_id, empresa_id, papel) VALUES (1,?,?)')
    ->execute([$empresaId, 'dono']);

$cats = [
    ['Vendas', 'receita', '#10b981'],
    ['Serviços', 'receita', '#34d399'],
    ['Salários', 'despesa', '#ef4444'],
    ['Marketing', 'despesa', '#f59e0b'],
    ['Infraestrutura', 'despesa', '#6366f1'],
];
$catIds = [];
$insCat = $pdo->prepare('INSERT INTO categorias (empresa_id, nome, tipo, cor) VALUES (?,?,?,?)');
foreach ($cats as $c) {
    $insCat->execute([$empresaId, $c[0], $c[1], $c[2]]);
    $catIds[$c[0]] = (int) $pdo->lastInsertId();
}

$pdo->prepare('INSERT INTO contas (empresa_id, nome, tipo, saldo_inicial, cor) VALUES (?,?,?,?,?)')
    ->execute([$empresaId, 'Conta Corrente', 'corrente', 10000, '#10b981']);
$conta1 = (int) $pdo->lastInsertId();
$pdo->prepare('INSERT INTO contas (empresa_id, nome, tipo, saldo_inicial, cor) VALUES (?,?,?,?,?)')
    ->execute([$empresaId, 'Caixa', 'caixa', 2000, '#f59e0b']);
$conta2 = (int) $pdo->lastInsertId();

$pdo->prepare('INSERT INTO metas (empresa_id, descricao, valor_alvo, prazo) VALUES (?,?,?,?)')
    ->execute([$empresaId, 'Reserva de emergência', 50000, '2026-12-31']);
$metaId = (int) $pdo->lastInsertId();

$ins = $pdo->prepare(
    'INSERT INTO lancamentos (empresa_id, conta_id, categoria_id, meta_id, tipo, descricao, valor, data_lancamento, data_vencimento, status, tags)
     VALUES (?,?,?,?,?,?,?,?,?,?,?)'
);

$descricoes = [
    'receita' => ['Projeto cliente A', 'Mensalidade SaaS', 'Consultoria', 'Venda produto'],
    'despesa' => ['Folha pagamento', 'Google Ads', 'AWS hosting', 'Material escritório'],
];

for ($m = 5; $m >= 0; $m--) {
    $base = new DateTime("first day of -{$m} months");
    for ($d = 0; $d < 8; $d++) {
        $tipo = $d % 3 === 0 ? 'despesa' : 'receita';
        $catNome = $tipo === 'receita' ? ($d % 2 ? 'Serviços' : 'Vendas') : ($d % 2 ? 'Marketing' : 'Salários');
        $data = (clone $base)->modify('+' . ($d * 3 + rand(0, 2)) . ' days')->format('Y-m-d');
        $valor = $tipo === 'receita' ? rand(1500, 12000) : rand(400, 5500);
        $status = rand(0, 10) > 2 ? 'pago' : 'pendente';
        $tags = json_encode(['operacional', $tipo === 'despesa' ? 'fixo' : 'variável']);
        $ins->execute([
            $empresaId,
            $d % 2 ? $conta1 : $conta2,
            $catIds[$catNome],
            $tipo === 'receita' && $d === 0 ? $metaId : null,
            $tipo,
            $descricoes[$tipo][array_rand($descricoes[$tipo])] . ' #' . ($m * 10 + $d),
            $valor,
            $data,
            $status === 'pendente' ? (clone $base)->modify('+30 days')->format('Y-m-d') : null,
            $status,
            $tags,
        ]);
    }
}

$pdo->prepare('UPDATE metas SET valor_atual = (
    SELECT COALESCE(SUM(valor),0) FROM lancamentos WHERE meta_id = ? AND status = "pago"
) WHERE id = ?')->execute([$metaId, $metaId]);

echo "Seed OK.\n";
echo "Login: demo@rezult.app / Senha@123\n";
