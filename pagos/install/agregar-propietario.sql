-- ============================================================
--  Núcleo de Pagos — AISLAR PRÉSTAMOS POR USUARIO
--  Importa este archivo en phpMyAdmin (sobre tu base) SI YA
--  tenías préstamos creados sin dueño.
--
--  Requisito: la tabla "usuarios" ya debe existir
--  (si no, importa antes install/agregar-login.sql).
-- ============================================================

SET NAMES utf8mb4;

-- 1) Agregar la columna dueño (si no existe todavía).
--    Nota: si sale "Duplicate column name", ya estaba puesta; ignóralo.
ALTER TABLE prestamos ADD COLUMN usuario_id BIGINT UNSIGNED NULL AFTER id;

-- 2) Asignar TODOS los préstamos actuales al usuario Lucio.
--    (Cámbialo por otro usuario si lo prefieres.)
UPDATE prestamos
SET usuario_id = (SELECT id FROM usuarios WHERE usuario = 'Lucio' LIMIT 1)
WHERE usuario_id IS NULL;

-- 3) Dejar la columna obligatoria y enlazarla con usuarios.
ALTER TABLE prestamos
    MODIFY usuario_id BIGINT UNSIGNED NOT NULL,
    ADD KEY idx_prestamos_usuario (usuario_id),
    ADD CONSTRAINT fk_prestamos_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE CASCADE;
