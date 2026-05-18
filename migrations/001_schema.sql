-- Rezult — Schema principal
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS usuarios (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome          VARCHAR(120) NOT NULL,
  email         VARCHAR(180) NOT NULL UNIQUE,
  senha_hash    VARCHAR(255) NOT NULL,
  avatar_url    VARCHAR(500),
  email_verificado TINYINT(1) DEFAULT 0,
  criado_em     DATETIME DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS empresas (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome        VARCHAR(200) NOT NULL,
  cnpj        VARCHAR(18),
  logo_url    VARCHAR(500),
  moeda       CHAR(3) DEFAULT 'BRL',
  criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS usuario_empresa (
  usuario_id  BIGINT UNSIGNED NOT NULL,
  empresa_id  BIGINT UNSIGNED NOT NULL,
  papel       ENUM('dono','admin','operador') DEFAULT 'operador',
  PRIMARY KEY (usuario_id, empresa_id),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
  INDEX idx_empresa (empresa_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categorias (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  empresa_id  BIGINT UNSIGNED NOT NULL,
  nome        VARCHAR(100) NOT NULL,
  tipo        ENUM('receita','despesa') NOT NULL,
  cor         CHAR(7) DEFAULT '#6366f1',
  icone       VARCHAR(50),
  FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
  INDEX idx_empresa_tipo (empresa_id, tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contas (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  empresa_id    BIGINT UNSIGNED NOT NULL,
  nome          VARCHAR(100) NOT NULL,
  tipo          ENUM('corrente','poupanca','caixa','cartao','investimento') NOT NULL,
  saldo_inicial DECIMAL(15,2) DEFAULT 0.00,
  cor           CHAR(7) DEFAULT '#10b981',
  ativo         TINYINT(1) DEFAULT 1,
  FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
  INDEX idx_empresa_ativo (empresa_id, ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lancamentos (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  empresa_id      BIGINT UNSIGNED NOT NULL,
  conta_id        BIGINT UNSIGNED NOT NULL,
  categoria_id    BIGINT UNSIGNED,
  meta_id         BIGINT UNSIGNED NULL,
  transferencia_par_id BIGINT UNSIGNED NULL,
  tipo            ENUM('receita','despesa','transferencia') NOT NULL,
  descricao       VARCHAR(300) NOT NULL,
  valor           DECIMAL(15,2) NOT NULL,
  data_lancamento DATE NOT NULL,
  data_vencimento DATE,
  status          ENUM('pago','pendente','cancelado') DEFAULT 'pendente',
  recorrente      TINYINT(1) DEFAULT 0,
  frequencia      ENUM('mensal','semanal','anual') NULL,
  anexo_url       VARCHAR(500),
  observacoes     TEXT,
  tags            JSON,
  criado_em       DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_empresa_data (empresa_id, data_lancamento),
  INDEX idx_empresa_tipo (empresa_id, tipo),
  INDEX idx_empresa_status (empresa_id, status),
  INDEX idx_conta (conta_id),
  INDEX idx_categoria (categoria_id),
  INDEX idx_vencimento (empresa_id, data_vencimento, status),
  FOREIGN KEY (empresa_id)   REFERENCES empresas(id)   ON DELETE CASCADE,
  FOREIGN KEY (conta_id)     REFERENCES contas(id)      ON DELETE RESTRICT,
  FOREIGN KEY (categoria_id) REFERENCES categorias(id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS metas (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  empresa_id  BIGINT UNSIGNED NOT NULL,
  descricao   VARCHAR(200) NOT NULL,
  valor_alvo  DECIMAL(15,2) NOT NULL,
  valor_atual DECIMAL(15,2) DEFAULT 0.00,
  prazo       DATE,
  FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
  INDEX idx_empresa (empresa_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE lancamentos
  ADD CONSTRAINT fk_lanc_meta FOREIGN KEY (meta_id) REFERENCES metas(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_lanc_transfer FOREIGN KEY (transferencia_par_id) REFERENCES lancamentos(id) ON DELETE SET NULL;

SET FOREIGN_KEY_CHECKS = 1;
