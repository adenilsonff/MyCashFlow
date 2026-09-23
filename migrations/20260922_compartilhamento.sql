CREATE TABLE compartilhamentos (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    proprietario_id INT NOT NULL,
    leitor_id INT NOT NULL,
    estado ENUM('pendente','ativo','revogado','recusado') NOT NULL DEFAULT 'pendente',
    versao INT UNSIGNED NOT NULL DEFAULT 1,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_compartilhamento_pessoas (proprietario_id, leitor_id),
    KEY ix_compartilhamento_leitor (leitor_id, estado),
    CONSTRAINT fk_compartilhamento_dono FOREIGN KEY (proprietario_id) REFERENCES usuarios(id),
    CONSTRAINT fk_compartilhamento_leitor FOREIGN KEY (leitor_id) REFERENCES usuarios(id),
    CONSTRAINT ck_compartilhamento_pessoas CHECK (proprietario_id <> leitor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE compartilhamento_modulos (
    compartilhamento_id INT NOT NULL,
    modulo ENUM('despesas','receitas','cartao','saldos','investimentos','daytrade','analise') NOT NULL,
    nivel ENUM('leitura','edicao') NOT NULL DEFAULT 'leitura',
    PRIMARY KEY (compartilhamento_id, modulo),
    CONSTRAINT fk_compartilhamento_modulos FOREIGN KEY (compartilhamento_id) REFERENCES compartilhamentos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
