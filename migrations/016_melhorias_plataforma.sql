ALTER TABLE api_tokens
  ADD COLUMN escopos ENUM('read', 'read_write') NOT NULL DEFAULT 'read_write';

ALTER TABLE empresas
  ADD COLUMN trial_ate DATETIME NULL;
