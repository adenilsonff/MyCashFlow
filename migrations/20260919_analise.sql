CREATE TABLE IF NOT EXISTS analise_acompanhamento (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    ticker VARCHAR(10) NOT NULL,
    tipo_ativo ENUM('acao','fii','etf','bdr') NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_analise_usuario_ativo (usuario_id,ticker,tipo_ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS analise_marcacoes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    ticker VARCHAR(10) NOT NULL,
    tipo_ativo ENUM('acao','fii','etf','bdr') NOT NULL,
    tipo ENUM('linha','nota') NOT NULL,
    titulo VARCHAR(80) NOT NULL,
    preco DECIMAL(16,4) NULL,
    cor CHAR(7) NOT NULL DEFAULT '#6366f1',
    texto VARCHAR(2000) NOT NULL DEFAULT '',
    versao INT UNSIGNED NOT NULL DEFAULT 1,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_analise_marcacoes (usuario_id,ticker,tipo_ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
