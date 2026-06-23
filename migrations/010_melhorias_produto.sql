SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS contatos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id BIGINT UNSIGNED NOT NULL,
    nome VARCHAR(255) NOT NULL,
    documento VARCHAR(20) NULL,
    email VARCHAR(255) NULL,
    telefone VARCHAR(30) NULL,
    tipo ENUM('cliente', 'fornecedor', 'ambos') NOT NULL DEFAULT 'cliente',
    observacoes TEXT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_contatos_empresa (empresa_id),
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE empresas ADD COLUMN plano VARCHAR(20) NOT NULL DEFAULT 'starter';

ALTER TABLE lancamentos ADD COLUMN contato_id BIGINT UNSIGNED NULL;
ALTER TABLE lancamentos ADD INDEX idx_lanc_contato (contato_id);

CREATE TABLE IF NOT EXISTS webhooks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id BIGINT UNSIGNED NOT NULL,
    url VARCHAR(500) NOT NULL,
    eventos JSON NOT NULL,
    secret VARCHAR(64) NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_webhooks_empresa (empresa_id),
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS integracoes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id BIGINT UNSIGNED NOT NULL,
    provedor VARCHAR(50) NOT NULL,
    config_json JSON NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 0,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_integracao (empresa_id, provedor),
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE usuarios ADD COLUMN ia_consentimento TINYINT(1) NOT NULL DEFAULT 0;
