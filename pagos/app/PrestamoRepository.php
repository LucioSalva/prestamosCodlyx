<?php

declare(strict_types=1);

namespace App;

use PDO;

/**
 * Acceso a datos de préstamos y pagos (MySQL).
 * TODO está aislado por usuario dueño (usuario_id): cada quien solo
 * ve y modifica sus propios préstamos.
 */
final class PrestamoRepository
{
    /** Columnas de resumen reutilizadas en varias consultas. */
    private const RESUMEN = '
        p.*,
        COALESCE(pg.abonado, 0)                    AS abonado,
        (p.total_pagar - COALESCE(pg.abonado, 0))  AS saldo,
        COALESCE(pg.num_pagos, 0)                  AS num_pagos
    ';

    private const JOIN = '
        LEFT JOIN (
            SELECT prestamo_id, SUM(monto) AS abonado, COUNT(*) AS num_pagos
            FROM pagos GROUP BY prestamo_id
        ) pg ON pg.prestamo_id = p.id
    ';

    public function __construct(private PDO $pdo)
    {
    }

    /** Préstamos del usuario dado. */
    public function todos(int $usuarioId): array
    {
        $sql = 'SELECT ' . self::RESUMEN . ' FROM prestamos p ' . self::JOIN
             . ' WHERE p.usuario_id = :uid'
             . ' ORDER BY p.creado_en DESC, p.id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $usuarioId]);

        return $stmt->fetchAll();
    }

    /** Métricas globales del usuario dado. */
    public function totalesGlobales(int $usuarioId): array
    {
        $sql = 'SELECT
                    COUNT(*)                                                 AS num_prestamos,
                    COALESCE(SUM(p.total_pagar), 0)                          AS total,
                    COALESCE(SUM(COALESCE(pg.abonado, 0)), 0)                AS abonado,
                    COALESCE(SUM(p.total_pagar - COALESCE(pg.abonado, 0)), 0) AS saldo
                FROM prestamos p ' . self::JOIN . ' WHERE p.usuario_id = :uid';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $usuarioId]);

        return $stmt->fetch() ?: [
            'num_prestamos' => 0, 'total' => 0, 'abonado' => 0, 'saldo' => 0,
        ];
    }

    /** Un préstamo del usuario dado (null si no existe o no es suyo). */
    public function encontrar(int $id, int $usuarioId): ?array
    {
        $sql  = 'SELECT ' . self::RESUMEN . ' FROM prestamos p ' . self::JOIN
              . ' WHERE p.id = :id AND p.usuario_id = :uid';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'uid' => $usuarioId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function pagosDe(int $prestamoId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM pagos WHERE prestamo_id = :id ORDER BY pagado_en DESC, id DESC'
        );
        $stmt->execute(['id' => $prestamoId]);

        return $stmt->fetchAll();
    }

    /** Crea un préstamo para el usuario dado y devuelve su id. */
    public function crear(array $d, int $usuarioId): int
    {
        // Usa el total exacto si viene; si no, lo calcula como cuota × periodos.
        $total = isset($d['total_pagar']) && (float) $d['total_pagar'] > 0
            ? round((float) $d['total_pagar'], 2)
            : round((float) $d['cuota'] * (int) $d['num_periodos'], 2);

        $sql = 'INSERT INTO prestamos (usuario_id, concepto, deudor, monto_prestado, frecuencia, cuota, num_periodos, total_pagar)
                VALUES (:uid, :concepto, :deudor, :monto, :frecuencia, :cuota, :periodos, :total)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'uid'        => $usuarioId,
            'concepto'   => $d['concepto'],
            'deudor'     => $d['deudor'] !== '' ? $d['deudor'] : null,
            'monto'      => $d['monto_prestado'],
            'frecuencia' => $d['frecuencia'],
            'cuota'      => $d['cuota'],
            'periodos'   => $d['num_periodos'],
            'total'      => $total,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** Actualiza un préstamo del usuario (respeta el total exacto si viene). */
    public function actualizar(int $id, array $d, int $usuarioId): void
    {
        $total = isset($d['total_pagar']) && (float) $d['total_pagar'] > 0
            ? round((float) $d['total_pagar'], 2)
            : round((float) $d['cuota'] * (int) $d['num_periodos'], 2);

        $sql = 'UPDATE prestamos
                SET concepto = :concepto, deudor = :deudor, monto_prestado = :monto,
                    frecuencia = :frecuencia, cuota = :cuota, num_periodos = :periodos,
                    total_pagar = :total
                WHERE id = :id AND usuario_id = :uid';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'concepto'   => $d['concepto'],
            'deudor'     => $d['deudor'] !== '' ? $d['deudor'] : null,
            'monto'      => $d['monto_prestado'],
            'frecuencia' => $d['frecuencia'],
            'cuota'      => $d['cuota'],
            'periodos'   => $d['num_periodos'],
            'total'      => $total,
            'id'         => $id,
            'uid'        => $usuarioId,
        ]);
    }

    /**
     * Registra un abono en un préstamo del usuario. Devuelve el saldo
     * restante, o null si el préstamo no existe o no es del usuario.
     */
    public function agregarPago(int $prestamoId, float $monto, string $nota, int $usuarioId): ?float
    {
        // Verifica propiedad antes de insertar.
        $prestamo = $this->encontrar($prestamoId, $usuarioId);
        if ($prestamo === null) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO pagos (prestamo_id, monto, nota) VALUES (:id, :monto, :nota)'
        );
        $stmt->execute([
            'id'    => $prestamoId,
            'monto' => $monto,
            'nota'  => $nota !== '' ? $nota : null,
        ]);

        $actualizado = $this->encontrar($prestamoId, $usuarioId);

        return (float) ($actualizado['saldo'] ?? 0);
    }

    /** Elimina un abono solo si su préstamo pertenece al usuario. */
    public function eliminarPago(int $pagoId, int $usuarioId): void
    {
        $sql = 'DELETE pg FROM pagos pg
                JOIN prestamos p ON p.id = pg.prestamo_id
                WHERE pg.id = :id AND p.usuario_id = :uid';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $pagoId, 'uid' => $usuarioId]);
    }

    /** Elimina un préstamo solo si pertenece al usuario. */
    public function eliminarPrestamo(int $id, int $usuarioId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM prestamos WHERE id = :id AND usuario_id = :uid');
        $stmt->execute(['id' => $id, 'uid' => $usuarioId]);
    }
}
