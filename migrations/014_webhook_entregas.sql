CREATE TABLE IF NOT EXISTS webhook_entregas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  webhook_id INT UNSIGNED NOT NULL,
  empresa_id INT UNSIGNED NOT NULL,
  evento VARCHAR(80) NOT NULL,
  url VARCHAR(500) NOT NULL,
  payload JSON NOT NULL,
  http_status INT UNSIGNED NULL,
  resposta TEXT NULL,
  sucesso TINYINT(1) NOT NULL DEFAULT 0,
  tentativas TINYINT UNSIGNED NOT NULL DEFAULT 1,
  criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_webhook (webhook_id),
  INDEX idx_empresa (empresa_id),
  INDEX idx_retry (sucesso, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
