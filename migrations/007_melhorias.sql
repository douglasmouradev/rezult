SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS rate_limits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  acao VARCHAR(40) NOT NULL,
  chave VARCHAR(255) NOT NULL,
  ip VARCHAR(45),
  criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_acao_chave (acao, chave, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE consentimentos MODIFY tipo ENUM('termos','privacidade','marketing','cookies') NOT NULL;

ALTER TABLE lancamentos ADD COLUMN recorrente_proximo DATE NULL;
ALTER TABLE lancamentos ADD COLUMN recorrente_origem_id BIGINT UNSIGNED NULL;
