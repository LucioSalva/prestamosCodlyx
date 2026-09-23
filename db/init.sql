-- ============================================================
--  Esquema de la aplicación de pagos de préstamos
--  PostgreSQL 17
-- ============================================================

SET client_encoding = 'UTF8';

-- ------------------------------------------------------------
--  Frecuencias de pago admitidas
-- ------------------------------------------------------------
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'frecuencia_pago') THEN
        CREATE TYPE frecuencia_pago AS ENUM (
            'diario',
            'semanal',
            'quincenal',
            'mensual',
            'anual'
        );
    END IF;
END$$;

-- ------------------------------------------------------------
--  Préstamos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS prestamos (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    concepto        TEXT             NOT NULL CHECK (length(trim(concepto)) > 0),
    deudor          TEXT,
    monto_prestado  NUMERIC(14, 2)   NOT NULL CHECK (monto_prestado >= 0),
    frecuencia      frecuencia_pago  NOT NULL,
    cuota           NUMERIC(14, 2)   NOT NULL CHECK (cuota >= 0),
    num_periodos    INTEGER          NOT NULL CHECK (num_periodos > 0),
    -- El total a pagar lo calcula la propia base de datos.
    total_pagar     NUMERIC(14, 2)   GENERATED ALWAYS AS (cuota * num_periodos) STORED,
    creado_en       TIMESTAMPTZ      NOT NULL DEFAULT now()
);

-- ------------------------------------------------------------
--  Pagos / abonos aplicados a cada préstamo
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pagos (
    id           BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    prestamo_id  BIGINT         NOT NULL REFERENCES prestamos(id) ON DELETE CASCADE,
    monto        NUMERIC(14, 2) NOT NULL CHECK (monto > 0),
    nota         TEXT,
    pagado_en    TIMESTAMPTZ    NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_pagos_prestamo ON pagos(prestamo_id);
CREATE INDEX IF NOT EXISTS idx_pagos_fecha    ON pagos(pagado_en DESC);

-- ------------------------------------------------------------
--  Vista de resumen: total, abonado y saldo restante
-- ------------------------------------------------------------
CREATE OR REPLACE VIEW prestamos_resumen AS
SELECT
    p.id,
    p.concepto,
    p.deudor,
    p.monto_prestado,
    p.frecuencia,
    p.cuota,
    p.num_periodos,
    p.total_pagar,
    p.creado_en,
    COALESCE(pg.abonado, 0)::NUMERIC(14, 2)            AS abonado,
    (p.total_pagar - COALESCE(pg.abonado, 0))::NUMERIC(14, 2) AS saldo,
    COALESCE(pg.num_pagos, 0)                           AS num_pagos
FROM prestamos p
LEFT JOIN (
    SELECT prestamo_id,
           SUM(monto)  AS abonado,
           COUNT(*)    AS num_pagos
    FROM pagos
    GROUP BY prestamo_id
) pg ON pg.prestamo_id = p.id;

-- ------------------------------------------------------------
--  Datos de ejemplo (solo en primera inicialización)
-- ------------------------------------------------------------
INSERT INTO prestamos (concepto, deudor, monto_prestado, frecuencia, cuota, num_periodos)
VALUES ('Préstamo de ejemplo — moto', 'Carlos R.', 20000.00, 'quincenal', 1250.00, 20)
ON CONFLICT DO NOTHING;

INSERT INTO pagos (prestamo_id, monto, nota)
SELECT id, 1250.00, 'Primer abono'
FROM prestamos WHERE concepto = 'Préstamo de ejemplo — moto'
LIMIT 1;
