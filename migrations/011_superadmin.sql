SET NAMES utf8mb4;

ALTER TABLE usuarios ADD COLUMN is_superadmin TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE usuarios ADD COLUMN ultimo_login_em DATETIME NULL;

CREATE INDEX idx_usuarios_superadmin ON usuarios (is_superadmin);
CREATE INDEX idx_usuarios_ultimo_login ON usuarios (ultimo_login_em);
