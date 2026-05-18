-- LGPD, auditoria e conformidade
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS consentimentos (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id    BIGINT UNSIGNED NOT NULL,
  tipo          ENUM('termos','privacidade','marketing') NOT NULL,
  versao        VARCHAR(20) NOT NULL DEFAULT '1.0',
  aceito        TINYINT(1) NOT NULL DEFAULT 1,
  ip            VARCHAR(45),
  user_agent    VARCHAR(500),
  criado_em     DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  INDEX idx_usuario_tipo (usuario_id, tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auditoria (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id    BIGINT UNSIGNED NULL,
  empresa_id    BIGINT UNSIGNED NULL,
  acao          VARCHAR(80) NOT NULL,
  entidade      VARCHAR(60),
  entidade_id   BIGINT UNSIGNED NULL,
  ip            VARCHAR(45),
  detalhes      JSON,
  criado_em     DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_usuario (usuario_id, criado_em),
  INDEX idx_empresa (empresa_id, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lgpd_solicitacoes (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id    BIGINT UNSIGNED NOT NULL,
  tipo          ENUM('exportacao','exclusao','retificacao') NOT NULL,
  status        ENUM('pendente','processando','concluida','negada') DEFAULT 'pendente',
  resposta      TEXT,
  processado_em DATETIME NULL,
  criado_em     DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  INDEX idx_usuario_status (usuario_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
