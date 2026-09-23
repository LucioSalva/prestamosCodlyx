<?php

declare(strict_types=1);

use App\Database;
use App\PrestamoRepository;

session_start();

require __DIR__ . '/../src/helpers.php';
require __DIR__ . '/../src/Database.php';
require __DIR__ . '/../src/PrestamoRepository.php';

/* -------------------------------------------------------------------------
 *  Arranque
 * ---------------------------------------------------------------------- */
try {
    $repo = new PrestamoRepository(Database::conn());
} catch (Throwable $e) {
    http_response_code(503);
    render('error', [
        'mensaje' => 'La base de datos no está disponible todavía. Espera unos segundos y recarga.',
    ], 'Sin conexión');
    exit;
}

/* -------------------------------------------------------------------------
 *  Enrutado
 * ---------------------------------------------------------------------- */
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path   = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/');
$path   = $path === '' ? '/' : $path;

/** Extrae un id entero positivo de un patrón de ruta, o null. */
$idFrom = static function (string $pattern) use ($path): ?int {
    if (preg_match($pattern, $path, $m) === 1) {
        return (int) $m[1];
    }
    return null;
};

try {
    // ---------- POST: crear préstamo ----------
    if ($method === 'POST' && $path === '/prestamos/crear') {
        csrf_check();
        $errores = [];

        $concepto = trim((string) ($_POST['concepto'] ?? ''));
        $deudor   = trim((string) ($_POST['deudor'] ?? ''));
        $monto    = (float) ($_POST['monto_prestado'] ?? 0);
        $freq     = (string) ($_POST['frecuencia'] ?? '');
        $cuota    = (float) ($_POST['cuota'] ?? 0);
        $periodos = (int) ($_POST['num_periodos'] ?? 0);

        if ($concepto === '')                 $errores[] = 'El concepto es obligatorio.';
        if ($monto < 0)                       $errores[] = 'La cantidad prestada no puede ser negativa.';
        if (!in_array($freq, frecuencias(), true)) $errores[] = 'Selecciona una frecuencia válida.';
        if ($cuota <= 0)                      $errores[] = 'La cuota debe ser mayor que cero.';
        if ($periodos <= 0)                   $errores[] = 'El número de periodos debe ser mayor que cero.';

        if ($errores !== []) {
            $_SESSION['flash_error'] = implode(' ', $errores);
            $_SESSION['old']         = $_POST;
            redirect('/');
        }

        $id = $repo->crear([
            'concepto'       => $concepto,
            'deudor'         => $deudor,
            'monto_prestado' => $monto,
            'frecuencia'     => $freq,
            'cuota'          => $cuota,
            'num_periodos'   => $periodos,
        ]);

        $_SESSION['flash_ok'] = 'Préstamo creado correctamente.';
        redirect('/prestamo/' . $id);
    }

    // ---------- POST: registrar abono ----------
    if ($method === 'POST' && $path === '/pagos/crear') {
        csrf_check();
        $prestamoId = (int) ($_POST['prestamo_id'] ?? 0);
        $monto      = (float) ($_POST['monto'] ?? 0);
        $nota       = trim((string) ($_POST['nota'] ?? ''));

        $prestamo = $repo->encontrar($prestamoId);
        if ($prestamo === null) {
            if (wants_json()) json_out(['ok' => false, 'error' => 'Préstamo no encontrado.'], 404);
            $_SESSION['flash_error'] = 'Préstamo no encontrado.';
            redirect('/');
        }
        if ($monto <= 0) {
            if (wants_json()) json_out(['ok' => false, 'error' => 'El monto del abono debe ser mayor que cero.'], 422);
            $_SESSION['flash_error'] = 'El monto del abono debe ser mayor que cero.';
            redirect('/prestamo/' . $prestamoId);
        }

        $saldo = $repo->agregarPago($prestamoId, $monto, $nota);

        if (wants_json()) {
            $resumen = $repo->encontrar($prestamoId);
            json_out([
                'ok'            => true,
                'saldo'         => (float) $resumen['saldo'],
                'abonado'       => (float) $resumen['abonado'],
                'total'         => (float) $resumen['total_pagar'],
                'num_pagos'     => (int) $resumen['num_pagos'],
                'saldo_fmt'     => money($resumen['saldo']),
                'abonado_fmt'   => money($resumen['abonado']),
                'pct'           => (float) $resumen['total_pagar'] > 0
                                    ? round((float) $resumen['abonado'] / (float) $resumen['total_pagar'] * 100, 1)
                                    : 0,
                'pago'          => [
                    'monto'     => $monto,
                    'monto_fmt' => money($monto),
                    'nota'      => $nota,
                    'fecha_fmt' => date('d/m/Y H:i'),
                ],
            ]);
        }

        $_SESSION['flash_ok'] = 'Abono de ' . money($monto) . ' registrado. Saldo restante: ' . money($saldo) . '.';
        redirect('/prestamo/' . $prestamoId);
    }

    // ---------- POST: eliminar abono ----------
    if ($method === 'POST' && ($pagoId = $idFrom('#^/pagos/(\d+)/eliminar$#')) !== null) {
        csrf_check();
        $prestamoId = (int) ($_POST['prestamo_id'] ?? 0);
        $repo->eliminarPago($pagoId);
        $_SESSION['flash_ok'] = 'Abono eliminado.';
        redirect('/prestamo/' . $prestamoId);
    }

    // ---------- POST: eliminar préstamo ----------
    if ($method === 'POST' && ($delId = $idFrom('#^/prestamo/(\d+)/eliminar$#')) !== null) {
        csrf_check();
        $repo->eliminarPrestamo($delId);
        $_SESSION['flash_ok'] = 'Préstamo eliminado.';
        redirect('/');
    }

    // ---------- GET: detalle de préstamo ----------
    if ($method === 'GET' && ($verId = $idFrom('#^/prestamo/(\d+)$#')) !== null) {
        $prestamo = $repo->encontrar($verId);
        if ($prestamo === null) {
            http_response_code(404);
            render('error', ['mensaje' => 'Ese préstamo no existe.'], 'No encontrado');
            exit;
        }
        render('prestamo', [
            'prestamo' => $prestamo,
            'pagos'    => $repo->pagosDe($verId),
        ], $prestamo['concepto']);
        exit;
    }

    // ---------- GET: inicio ----------
    if ($method === 'GET' && $path === '/') {
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);
        render('home', [
            'prestamos' => $repo->todos(),
            'totales'   => $repo->totalesGlobales(),
            'old'       => $old,
        ], 'Panel de préstamos');
        exit;
    }

    // ---------- 404 ----------
    http_response_code(404);
    render('error', ['mensaje' => 'Página no encontrada.'], 'No encontrado');
} catch (Throwable $e) {
    error_log('App error: ' . $e->getMessage());
    if (wants_json()) json_out(['ok' => false, 'error' => 'Ocurrió un error inesperado.'], 500);
    http_response_code(500);
    render('error', ['mensaje' => 'Ocurrió un error inesperado.'], 'Error');
}
