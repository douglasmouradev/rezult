-- Seed de demonstração (execute após criar usuário via app ou ajuste senha_hash)
-- Senha padrão demo: Senha@123 (bcrypt cost 12)
SET NAMES utf8mb4;

INSERT INTO usuarios (id, nome, email, senha_hash, email_verificado) VALUES
(1, 'Ana Silva', 'demo@rezult.app', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

INSERT INTO empresas (id, nome, cnpj, moeda) VALUES
(1, 'Studio Criativo Ltda', '12.345.678/0001-90', 'BRL'),
(2, 'Consultoria Horizonte', '98.765.432/0001-10', 'BRL');

INSERT INTO usuario_empresa (usuario_id, empresa_id, papel) VALUES
(1, 1, 'dono'),
(1, 2, 'admin');

INSERT INTO categorias (empresa_id, nome, tipo, cor, icone) VALUES
(1, 'Vendas', 'receita', '#10b981', 'trending-up'),
(1, 'Serviços', 'receita', '#34d399', 'briefcase'),
(1, 'Salários', 'despesa', '#ef4444', 'users'),
(1, 'Marketing', 'despesa', '#f59e0b', 'megaphone'),
(1, 'Infraestrutura', 'despesa', '#6366f1', 'server'),
(1, 'Impostos', 'despesa', '#f97316', 'receipt');

INSERT INTO contas (empresa_id, nome, tipo, saldo_inicial, cor) VALUES
(1, 'Conta Corrente BB', 'corrente', 15000.00, '#10b981'),
(1, 'Caixa Físico', 'caixa', 2500.00, '#f59e0b'),
(1, 'Cartão Corporativo', 'cartao', 0.00, '#6366f1');

INSERT INTO metas (empresa_id, descricao, valor_alvo, valor_atual, prazo) VALUES
(1, 'Reserva de emergência', 50000.00, 18500.00, '2026-12-31'),
(1, 'Novo equipamento', 12000.00, 4200.00, '2026-08-15');
