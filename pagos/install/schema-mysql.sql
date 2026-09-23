-- ============================================================
--  Núcleo de Pagos — esquema para MySQL / MariaDB (HostGator)
--  Importa este archivo en phpMyAdmin sobre tu base de datos.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
--  Usuarios del sistema (login)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario    VARCHAR(60)  NOT NULL,
    pass_hash  VARCHAR(255) NOT NULL,
    rol        ENUM('dios','normal') NOT NULL DEFAULT 'normal',
    creado_en  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuario (usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuario administrador (rol "Dios"). Contraseña: Luc1@2488  (hash bcrypt)
INSERT INTO usuarios (usuario, pass_hash, rol)
VALUES ('Lucio', '$2y$12$fTbvJzjYC6Qgf6MTVTW2JOxHXws6DGPY/17jnulhjE9Ty4OKjtlzW', 'dios')
ON DUPLICATE KEY UPDATE usuario = usuario;

-- ------------------------------------------------------------
--  Préstamos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS prestamos (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id     BIGINT UNSIGNED NOT NULL,
    concepto       VARCHAR(200)    NOT NULL,
    deudor         VARCHAR(120)    NULL,
    monto_prestado DECIMAL(14,2)   NOT NULL DEFAULT 0.00,
    frecuencia     ENUM('diario','semanal','quincenal','mensual','anual') NOT NULL,
    cuota          DECIMAL(14,2)   NOT NULL DEFAULT 0.00,
    num_periodos   INT             NOT NULL DEFAULT 1,
    total_pagar    DECIMAL(14,2)   NOT NULL DEFAULT 0.00,
    creado_en      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_prestamos_usuario (usuario_id),
    CONSTRAINT fk_prestamos_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  Pagos / abonos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pagos (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    prestamo_id BIGINT UNSIGNED NOT NULL,
    monto       DECIMAL(14,2)   NOT NULL,
    nota        VARCHAR(200)    NULL,
    pagado_en   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pagos_prestamo (prestamo_id),
    KEY idx_pagos_fecha (pagado_en),
    CONSTRAINT fk_pagos_prestamo FOREIGN KEY (prestamo_id)
        REFERENCES prestamos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
--  Datos de ejemplo del usuario Lucio (opcional: puedes borrarlos
--  o eliminar el préstamo desde la app cuando quieras)
-- ------------------------------------------------------------
INSERT INTO prestamos (usuario_id, concepto, deudor, monto_prestado, frecuencia, cuota, num_periodos, total_pagar)
SELECT id, 'Préstamo de ejemplo — moto', 'Carlos R.', 20000.00, 'quincenal', 1250.00, 20, 25000.00
FROM usuarios WHERE usuario = 'Lucio' LIMIT 1;

INSERT INTO pagos (prestamo_id, monto, nota)
VALUES (LAST_INSERT_ID(), 1250.00, 'Primer abono');
