SET NAMES utf8mb4;

ALTER TABLE usuarios ADD COLUMN bloqueado TINYINT(1) NOT NULL DEFAULT 0;

CREATE INDEX idx_usuarios_bloqueado ON usuarios (bloqueado);
