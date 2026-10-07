CREATE TABLE notificacao_cartoes (
 id INT NOT NULL AUTO_INCREMENT,
 usuario_id INT NOT NULL,
 nome VARCHAR(80) NOT NULL,
 dia_fechamento TINYINT UNSIGNED NOT NULL,
 PRIMARY KEY(id),
 UNIQUE KEY uq_notificacao_cartao(usuario_id,nome),
 CONSTRAINT ck_notificacao_dia CHECK (dia_fechamento BETWEEN 1 AND 31),
 CONSTRAINT fk_notificacao_cartao_usuario FOREIGN KEY(usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
CREATE TABLE notificacao_lidos (
 usuario_id INT NOT NULL,
 chave CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 lido_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(usuario_id,chave),
 CONSTRAINT fk_notificacao_lido_usuario FOREIGN KEY(usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
