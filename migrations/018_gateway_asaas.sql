ALTER TABLE cobrancas
    ADD COLUMN gateway_id VARCHAR(80) NULL,
    ADD COLUMN gateway_provedor VARCHAR(30) NULL;

CREATE INDEX idx_cobrancas_gateway ON cobrancas (gateway_id);

CREATE TABLE IF NOT EXISTS gateway_webhook_eventos (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provedor     VARCHAR(30) NOT NULL,
    evento_id    VARCHAR(120) NOT NULL,
    processado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_gateway_evento (provedor, evento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
