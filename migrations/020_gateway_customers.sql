CREATE TABLE IF NOT EXISTS gateway_customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id BIGINT UNSIGNED NOT NULL,
    gateway_provedor VARCHAR(30) NOT NULL,
    chave VARCHAR(255) NOT NULL,
    customer_id VARCHAR(80) NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_gateway_customer (empresa_id, gateway_provedor, chave),
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
