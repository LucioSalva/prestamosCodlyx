-- ============================================================
--  Núcleo de Pagos — AGREGAR LOGIN a una instalación existente
--  Importa este archivo en phpMyAdmin (sobre tu base) SI YA
--  habías importado antes schema-mysql.sql.
-- ============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS usuarios (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario    VARCHAR(60)  NOT NULL,
    pass_hash  VARCHAR(255) NOT NULL,
    rol        ENUM('dios','normal') NOT NULL DEFAULT 'normal',
    creado_en  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuario (usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuario administrador (rol "Dios"). Contraseña: Luc1@2488
-- El hash es bcrypt; la contraseña NUNCA se guarda en texto plano.
INSERT INTO usuarios (usuario, pass_hash, rol)
VALUES ('Lucio', '$2y$12$fTbvJzjYC6Qgf6MTVTW2JOxHXws6DGPY/17jnulhjE9Ty4OKjtlzW', 'dios')
ON DUPLICATE KEY UPDATE usuario = usuario;
