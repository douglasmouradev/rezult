-- Onboarding persistido por empresa
ALTER TABLE empresas
    ADD COLUMN onboarding_concluido TINYINT(1) NOT NULL DEFAULT 0;
