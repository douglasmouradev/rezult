SET NAMES utf8mb4;

ALTER TABLE empresas ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE empresas ADD COLUMN plano_ativo TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE empresas ADD COLUMN plano_expira_em DATETIME NULL;

CREATE INDEX idx_empresas_status ON empresas (ativo, plano_ativo);
