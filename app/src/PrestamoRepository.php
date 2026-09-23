<?php

declare(strict_types=1);

namespace App;

use PDO;

/**
 * Acceso a datos de préstamos y pagos.
 * Todas las consultas usan sentencias preparadas (sin inyección SQL).
 */
final class PrestamoRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** Todos los préstamos con su resumen (total, abonado, saldo). */
    public function todos(): array
    {
        $sql = 'SELECT * FROM prestamos_resumen ORDER BY creado_en DESC';

        return $this->pdo->query($sql)->fetchAll();
    }

    /** Métricas globales para el encabezado. */
    public function totalesGlobales(): array
    {
        $sql = 'SELECT
                    COUNT(*)                       AS num_prestamos,
                    COALESCE(SUM(total_pagar), 0)  AS total,
                    COALESCE(SUM(abonado), 0)      AS abonado,
                    COALESCE(SUM(saldo), 0)        AS saldo
                FROM prestamos_resumen';

        return $this->pdo->query($sql)->fetch() ?: [
            'num_prestamos' => 0, 'total' => 0, 'abonado' => 0, 'saldo' => 0,
        ];
    }

    /** Un préstamo por id (con resumen) o null. */
    public function encontrar(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM prestamos_resumen WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** Pagos de un préstamo, del más reciente al más antiguo. */
    public function pagosDe(int $prestamoId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM pagos WHERE prestamo_id = :id ORDER BY pagado_en DESC, id DESC'
        );
        $stmt->execute(['id' => $prestamoId]);

        return $stmt->fetchAll();
    }

    /** Crea un préstamo y devuelve su id. */
    public function crear(array $d): int
    {
        $sql = 'INSERT INTO prestamos (concepto, deudor, monto_prestado, frecuencia, cuota, num_periodos)
                VALUES (:concepto, :deudor, :monto, :frecuencia, :cuota, :periodos)
                RETURNING id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'concepto'   => $d['concepto'],
            'deudor'     => $d['deudor'] !== '' ? $d['deudor'] : null,
            'monto'      => $d['monto_prestado'],
            'frecuencia' => $d['frecuencia'],
            'cuota'      => $d['cuota'],
            'periodos'   => $d['num_periodos'],
        ]);

        return (int) $stmt->fetchColumn();
    }

    /** Registra un abono. Devuelve el saldo restante tras aplicarlo. */
    public function agregarPago(int $prestamoId, float $monto, string $nota): float
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO pagos (prestamo_id, monto, nota) VALUES (:id, :monto, :nota)'
        );
        $stmt->execute([
            'id'    => $prestamoId,
            'monto' => $monto,
            'nota'  => $nota !== '' ? $nota : null,
        ]);

        $prestamo = $this->encontrar($prestamoId);

        return (float) ($prestamo['saldo'] ?? 0);
    }

    public function eliminarPago(int $pagoId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM pagos WHERE id = :id');
        $stmt->execute(['id' => $pagoId]);
    }

    public function eliminarPrestamo(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM prestamos WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
