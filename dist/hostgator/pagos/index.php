<?php

declare(strict_types=1);

use App\Auth;
use App\Database;
use App\PrestamoRepository;
use App\UsuarioRepository;

session_start();

require __DIR__ . '/app/helpers.php';
require __DIR__ . '/app/Database.php';
require __DIR__ . '/app/PrestamoRepository.php';
require __DIR__ . '/app/UsuarioRepository.php';
require __DIR__ . '/app/Auth.php';

/* -------------------------------------------------------------------------
 *  Errores (según config.php)
 * ---------------------------------------------------------------------- */
$__cfg = require __DIR__ . '/config.php';
if (!empty($__cfg['debug'])) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

/* -------------------------------------------------------------------------
 *  Arranque
 * ---------------------------------------------------------------------- */
try {
    $pdo         = Database::conn();
    $repo        = new PrestamoRepository($pdo);
    $usuarioRepo = new UsuarioRepository($pdo);
} catch (Throwable $e) {
    http_response_code(503);
    render('error', [
        'mensaje' => 'No se pudo conectar a la base de datos. Revisa las credenciales en config.php.',
    ], 'Sin conexión');
    exit;
}

/* -------------------------------------------------------------------------
 *  Enrutado (respeta la ruta base si la app vive en un subdirectorio)
 * ---------------------------------------------------------------------- */
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$base   = base_path();
if ($base !== '' && str_starts_with($path, $base)) {
    $path = substr($path, strlen($base));
}
$path = rtrim($path, '/');
$path = $path === '' ? '/' : $path;

/** Extrae un id entero positivo de un patrón de ruta, o null. */
$idFrom = static function (string $pattern) use ($path): ?int {
    if (preg_match($pattern, $path, $m) === 1) {
        return (int) $m[1];
    }
    return null;
};

try {
    /* =====================================================================
     *  AUTENTICACIÓN (rutas públicas: /login y /logout)
     * ================================================================== */

    // ---------- GET: mostrar login ----------
    if ($method === 'GET' && $path === '/login') {
        if (Auth::check()) {
            redirect('/');
        }
        render('login', [], 'Iniciar sesión');
        exit;
    }

    // ---------- POST: procesar login ----------
    if ($method === 'POST' && $path === '/login') {
        csrf_check();
        $usuario = trim((string) ($_POST['usuario'] ?? ''));
        $pass    = (string) ($_POST['password'] ?? '');

        $u = $usuario !== '' ? $usuarioRepo->porUsuario($usuario) : null;

        if ($u === null || !password_verify($pass, $u['pass_hash'])) {
            usleep(300000); // pequeño retraso ante intentos fallidos
            $_SESSION['flash_error'] = 'Usuario o contraseña incorrectos.';
            $_SESSION['old_login']   = $usuario;
            redirect('/login');
        }

        // Re-hashea si el algoritmo/costo cambió.
        if (password_needs_rehash($u['pass_hash'], PASSWORD_BCRYPT)) {
            $usuarioRepo->actualizarHash((int) $u['id'], password_hash($pass, PASSWORD_BCRYPT));
        }

        Auth::login($u);
        redirect('/');
    }

    // ---------- Cerrar sesión ----------
    if ($path === '/logout') {
        if ($method === 'POST') {
            csrf_check();
        }
        Auth::logout();
        session_start();
        $_SESSION['flash_ok'] = 'Sesión cerrada.';
        redirect('/login');
    }

    /* =====================================================================
     *  MURO: de aquí en adelante hay que estar autenticado
     * ================================================================== */
    if (!Auth::check()) {
        if (wants_json()) {
            json_out(['ok' => false, 'error' => 'Sesión expirada. Vuelve a iniciar sesión.'], 401);
        }
        $_SESSION['flash_error'] = 'Inicia sesión para continuar.';
        redirect('/login');
    }

    // Usuario dueño de los datos: todo se filtra por él.
    $uid = (int) Auth::user()['id'];

    /* =====================================================================
     *  GESTIÓN DE USUARIOS (solo rol "Dios")
     * ================================================================== */
    if ($path === '/usuarios' || str_starts_with($path, '/usuarios/')) {
        if (!Auth::isDios()) {
            http_response_code(403);
            render('error', ['mensaje' => 'No tienes permiso para administrar usuarios.'], 'Sin permiso');
            exit;
        }

        // Crear usuario
        if ($method === 'POST' && $path === '/usuarios/crear') {
            csrf_check();
            $nuevo = trim((string) ($_POST['usuario'] ?? ''));
            $pass  = (string) ($_POST['password'] ?? '');
            $rol   = ($_POST['rol'] ?? 'normal') === 'dios' ? 'dios' : 'normal';

            $errores = [];
            if (mb_strlen($nuevo) < 3)   $errores[] = 'El usuario debe tener al menos 3 caracteres.';
            if (mb_strlen($pass) < 8)    $errores[] = 'La contraseña debe tener al menos 8 caracteres.';

            if ($errores === []) {
                try {
                    $usuarioRepo->crear($nuevo, $pass, $rol);
                    $_SESSION['flash_ok'] = 'Usuario "' . $nuevo . '" creado.';
                } catch (RuntimeException $e) {
                    $_SESSION['flash_error'] = $e->getMessage();
                }
            } else {
                $_SESSION['flash_error'] = implode(' ', $errores);
            }
            redirect('/usuarios');
        }

        // Eliminar usuario
        if ($method === 'POST' && ($uid = $idFrom('#^/usuarios/(\d+)/eliminar$#')) !== null) {
            csrf_check();
            if ($uid === (int) Auth::user()['id']) {
                $_SESSION['flash_error'] = 'No puedes eliminar tu propio usuario.';
            } else {
                $usuarioRepo->eliminar($uid);
                $_SESSION['flash_ok'] = 'Usuario eliminado.';
            }
            redirect('/usuarios');
        }

        // Listado
        if ($method === 'GET' && $path === '/usuarios') {
            render('usuarios', ['usuarios' => $usuarioRepo->todos()], 'Usuarios');
            exit;
        }
    }

    /* =====================================================================
     *  APLICACIÓN (préstamos y pagos)
     * ================================================================== */

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
        // Total exacto: si el usuario lo escribió, se respeta; si no, cuota × periodos.
        $totalIn  = $_POST['total_pagar'] ?? '';
        $total    = (is_numeric($totalIn) && (float) $totalIn > 0)
                        ? round((float) $totalIn, 2)
                        : round($cuota * $periodos, 2);

        if ($concepto === '')                      $errores[] = 'El concepto es obligatorio.';
        if ($monto < 0)                            $errores[] = 'La cantidad prestada no puede ser negativa.';
        if (!in_array($freq, frecuencias(), true)) $errores[] = 'Selecciona una frecuencia válida.';
        if ($cuota <= 0)                           $errores[] = 'La cuota debe ser mayor que cero.';
        if ($periodos <= 0)                        $errores[] = 'El número de periodos debe ser mayor que cero.';
        if ($total <= 0)                           $errores[] = 'La cantidad total debe ser mayor que cero.';

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
            'total_pagar'    => $total,
        ], $uid);

        $_SESSION['flash_ok'] = 'Préstamo creado correctamente.';
        redirect('/prestamo/' . $id);
    }

    // ---------- POST: registrar abono ----------
    if ($method === 'POST' && $path === '/pagos/crear') {
        csrf_check();
        $prestamoId = (int) ($_POST['prestamo_id'] ?? 0);
        $monto      = (float) ($_POST['monto'] ?? 0);
        $nota       = trim((string) ($_POST['nota'] ?? ''));

        $prestamo = $repo->encontrar($prestamoId, $uid);
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

        $saldo = $repo->agregarPago($prestamoId, $monto, $nota, $uid);

        if (wants_json()) {
            $resumen = $repo->encontrar($prestamoId, $uid);
            json_out([
                'ok'          => true,
                'saldo'       => (float) $resumen['saldo'],
                'abonado'     => (float) $resumen['abonado'],
                'total'       => (float) $resumen['total_pagar'],
                'num_pagos'   => (int) $resumen['num_pagos'],
                'saldo_fmt'   => money($resumen['saldo']),
                'abonado_fmt' => money($resumen['abonado']),
                'pct'         => (float) $resumen['total_pagar'] > 0
                                  ? round((float) $resumen['abonado'] / (float) $resumen['total_pagar'] * 100, 1)
                                  : 0,
                'pago'        => [
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
        $repo->eliminarPago($pagoId, $uid);
        $_SESSION['flash_ok'] = 'Abono eliminado.';
        redirect('/prestamo/' . $prestamoId);
    }

    // ---------- POST: eliminar préstamo ----------
    if ($method === 'POST' && ($delId = $idFrom('#^/prestamo/(\d+)/eliminar$#')) !== null) {
        csrf_check();
        $repo->eliminarPrestamo($delId, $uid);
        $_SESSION['flash_ok'] = 'Préstamo eliminado.';
        redirect('/');
    }

    // ---------- POST: editar préstamo ----------
    if ($method === 'POST' && ($edId = $idFrom('#^/prestamo/(\d+)/editar$#')) !== null) {
        csrf_check();
        if ($repo->encontrar($edId, $uid) === null) {
            $_SESSION['flash_error'] = 'Préstamo no encontrado.';
            redirect('/');
        }

        $errores  = [];
        $concepto = trim((string) ($_POST['concepto'] ?? ''));
        $deudor   = trim((string) ($_POST['deudor'] ?? ''));
        $monto    = (float) ($_POST['monto_prestado'] ?? 0);
        $freq     = (string) ($_POST['frecuencia'] ?? '');
        $cuota    = (float) ($_POST['cuota'] ?? 0);
        $periodos = (int) ($_POST['num_periodos'] ?? 0);
        $totalIn  = $_POST['total_pagar'] ?? '';
        $total    = (is_numeric($totalIn) && (float) $totalIn > 0)
                        ? round((float) $totalIn, 2)
                        : round($cuota * $periodos, 2);

        if ($concepto === '')                      $errores[] = 'El concepto es obligatorio.';
        if ($monto < 0)                            $errores[] = 'La cantidad prestada no puede ser negativa.';
        if (!in_array($freq, frecuencias(), true)) $errores[] = 'Selecciona una frecuencia válida.';
        if ($cuota <= 0)                           $errores[] = 'La cuota debe ser mayor que cero.';
        if ($periodos <= 0)                        $errores[] = 'El número de periodos debe ser mayor que cero.';
        if ($total <= 0)                           $errores[] = 'La cantidad total debe ser mayor que cero.';

        if ($errores !== []) {
            $_SESSION['flash_error'] = implode(' ', $errores);
            $_SESSION['old']         = $_POST;
            redirect('/prestamo/' . $edId . '/editar');
        }

        $repo->actualizar($edId, [
            'concepto'       => $concepto,
            'deudor'         => $deudor,
            'monto_prestado' => $monto,
            'frecuencia'     => $freq,
            'cuota'          => $cuota,
            'num_periodos'   => $periodos,
            'total_pagar'    => $total,
        ], $uid);

        $_SESSION['flash_ok'] = 'Préstamo actualizado.';
        redirect('/prestamo/' . $edId);
    }

    // ---------- GET: formulario de edición ----------
    if ($method === 'GET' && ($edId = $idFrom('#^/prestamo/(\d+)/editar$#')) !== null) {
        $prestamo = $repo->encontrar($edId, $uid);
        if ($prestamo === null) {
            http_response_code(404);
            render('error', ['mensaje' => 'Ese préstamo no existe.'], 'No encontrado');
            exit;
        }
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);
        render('editar', ['prestamo' => $prestamo, 'old' => $old], 'Editar préstamo');
        exit;
    }

    // ---------- GET: detalle de préstamo ----------
    if ($method === 'GET' && ($verId = $idFrom('#^/prestamo/(\d+)$#')) !== null) {
        $prestamo = $repo->encontrar($verId, $uid);
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
            'prestamos' => $repo->todos($uid),
            'totales'   => $repo->totalesGlobales($uid),
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
