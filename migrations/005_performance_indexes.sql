SET NAMES utf8mb4;
CREATE INDEX idx_lanc_emp_status_data ON lancamentos (empresa_id, status, data_lancamento);
