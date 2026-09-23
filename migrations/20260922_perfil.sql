ALTER TABLE usuarios
    ADD COLUMN nome VARCHAR(100) NULL DEFAULT NULL,
    ADD COLUMN email_normalizado VARCHAR(100) GENERATED ALWAYS AS (LOWER(TRIM(email))) PERSISTENT,
    ADD UNIQUE KEY uq_usuarios_email_normalizado (email_normalizado);
