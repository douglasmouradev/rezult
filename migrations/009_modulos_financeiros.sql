SET NAMES utf8mb4;

ALTER TABLE lancamentos ADD COLUMN parceiro VARCHAR(200) NULL AFTER descricao;
ALTER TABLE lancamentos ADD COLUMN conciliado_em DATETIME NULL AFTER observacoes;

CREATE TABLE IF NOT EXISTS cobrancas (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  empresa_id      BIGINT UNSIGNED NOT NULL,
  lancamento_id   BIGINT UNSIGNED NULL,
  cliente_nome    VARCHAR(200) NOT NULL,
  cliente_email   VARCHAR(200) NULL,
  descricao       VARCHAR(300) NOT NULL,
  valor           DECIMAL(15,2) NOT NULL,
  vencimento      DATE NOT NULL,
  tipo            ENUM('pix','boleto') NOT NULL DEFAULT 'pix',
  status          ENUM('rascunho','emitida','paga','cancelada','vencida') NOT NULL DEFAULT 'rascunho',
  codigo_pix      VARCHAR(500) NULL,
  linha_digitavel VARCHAR(100) NULL,
  criado_em       DATETIME DEFAULT CURRENT_TIMESTAMP,
  atualizado_em   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_cobrancas_empresa (empresa_id, status, vencimento),
  FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
  FOREIGN KEY (lancamento_id) REFERENCES lancamentos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notas_fiscais (
  id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  empresa_id        BIGINT UNSIGNED NOT NULL,
  lancamento_id     BIGINT UNSIGNED NULL,
  tomador_nome      VARCHAR(200) NOT NULL,
  tomador_documento VARCHAR(20) NOT NULL,
  descricao_servico TEXT NOT NULL,
  valor             DECIMAL(15,2) NOT NULL,
  numero            VARCHAR(30) NULL,
  codigo_verificacao VARCHAR(50) NULL,
  status            ENUM('rascunho','emitida','cancelada','erro') NOT NULL DEFAULT 'rascunho',
  emitida_em        DATETIME NULL,
  criado_em         DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_nf_empresa (empresa_id, status),
  FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
  FOREIGN KEY (lancamento_id) REFERENCES lancamentos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS regras_automacao (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  empresa_id  BIGINT UNSIGNED NOT NULL,
  nome        VARCHAR(120) NOT NULL,
  ativo       TINYINT(1) NOT NULL DEFAULT 1,
  gatilho     ENUM('import_csv','vencimento','recorrente','descricao_contem') NOT NULL,
  condicao    JSON NULL,
  acao        ENUM('categorizar','notificar','criar_lancamento','marcar_pago') NOT NULL,
  parametros  JSON NOT NULL,
  criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_regras_empresa (empresa_id, ativo),
  FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS conciliacoes (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  empresa_id  BIGINT UNSIGNED NOT NULL,
  conta_id    BIGINT UNSIGNED NOT NULL,
  arquivo     VARCHAR(255) NULL,
  status      ENUM('processando','pendente','concluida') NOT NULL DEFAULT 'pendente',
  total_itens INT UNSIGNED DEFAULT 0,
  conciliados INT UNSIGNED DEFAULT 0,
  criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_conc_empresa (empresa_id, status),
  FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
  FOREIGN KEY (conta_id) REFERENCES contas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS conciliacao_itens (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  conciliacao_id  BIGINT UNSIGNED NOT NULL,
  data_movimento  DATE NOT NULL,
  descricao       VARCHAR(300) NOT NULL,
  valor           DECIMAL(15,2) NOT NULL,
  tipo_movimento  ENUM('credito','debito') NOT NULL,
  lancamento_id   BIGINT UNSIGNED NULL,
  status          ENUM('pendente','conciliado','ignorado') NOT NULL DEFAULT 'pendente',
  INDEX idx_conc_itens (conciliacao_id, status),
  FOREIGN KEY (conciliacao_id) REFERENCES conciliacoes(id) ON DELETE CASCADE,
  FOREIGN KEY (lancamento_id) REFERENCES lancamentos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
