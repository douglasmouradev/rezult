-- Tokens, convites e rate limiting
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS tokens_email (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id  BIGINT UNSIGNED NOT NULL,
  token       VARCHAR(64) NOT NULL UNIQUE,
  tipo        ENUM('confirmacao','recuperacao') NOT NULL,
  expira_em   DATETIME NOT NULL,
  usado_em    DATETIME NULL,
  criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  INDEX idx_token (token),
  INDEX idx_usuario_tipo (usuario_id, tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS remember_tokens (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id  BIGINT UNSIGNED NOT NULL,
  selector    VARCHAR(32) NOT NULL UNIQUE,
  token_hash  VARCHAR(255) NOT NULL,
  expira_em   DATETIME NOT NULL,
  criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  INDEX idx_selector (selector)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_tentativas (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email       VARCHAR(180) NOT NULL,
  ip          VARCHAR(45) NOT NULL,
  sucesso     TINYINT(1) DEFAULT 0,
  criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email_ip (email, ip, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS convites (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  empresa_id  BIGINT UNSIGNED NOT NULL,
  email       VARCHAR(180) NOT NULL,
  papel       ENUM('admin','operador') DEFAULT 'operador',
  token       VARCHAR(64) NOT NULL UNIQUE,
  convidado_por BIGINT UNSIGNED NOT NULL,
  aceito_em   DATETIME NULL,
  expira_em   DATETIME NOT NULL,
  criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
  FOREIGN KEY (convidado_por) REFERENCES usuarios(id) ON DELETE CASCADE,
  INDEX idx_token (token),
  INDEX idx_empresa_email (empresa_id, email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
