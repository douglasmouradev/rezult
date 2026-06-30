-- Garante tabela de orçamentos em ambientes onde 008 não criou corretamente
CREATE TABLE IF NOT EXISTS orcamentos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  empresa_id BIGINT UNSIGNED NOT NULL,
  categoria_id BIGINT UNSIGNED NULL,
  mes CHAR(7) NOT NULL,
  valor_planejado DECIMAL(15,2) NOT NULL,
  FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
  FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL,
  UNIQUE KEY uk_orcamento (empresa_id, categoria_id, mes)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
