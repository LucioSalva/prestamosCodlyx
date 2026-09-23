<?php

declare(strict_types=1);

/**
 * Utilidades compartidas: rutas base, escape, dinero, CSRF, render y respuestas.
 * Compatible con hosting compartido en subdirectorio o en la raíz del dominio.
 */

/** Ruta base de la app (vacía en la raíz, "/pagos" en un subdirectorio). */
function base_path(): string
{
    static $base = null;
    if ($base === null) {
        $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $base   = rtrim(str_replace('\\', '/', dirname($script)), '/');
        if ($base === '/' || $base === '.') {
            $base = '';
        }
    }
    return $base;
}

/** Construye una URL de la app respetando la ruta base. */
function url(string $path = ''): string
{
    return base_path() . $path;
}

/** Escapa texto para HTML. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Formatea un número como dinero en pesos mexicanos. */
function money(int|float|string $value): string
{
    return '$' . number_format((float) $value, 2, '.', ',') . ' MXN';
}

/** Etiqueta legible de una frecuencia. */
function frecuencia_label(string $freq): string
{
    return match ($freq) {
        'diario'    => 'Diario',
        'semanal'   => 'Semanal',
        'quincenal' => 'Quincenal',
        'mensual'   => 'Mensual',
        'anual'     => 'Anual',
        default     => ucfirst($freq),
    };
}

/** Sustantivo del periodo según la frecuencia (para "20 quincenas"). */
function periodo_sustantivo(string $freq, int $n): string
{
    $mapa = [
        'diario'    => ['día', 'días'],
        'semanal'   => ['semana', 'semanas'],
        'quincenal' => ['quincena', 'quincenas'],
        'mensual'   => ['mes', 'meses'],
        'anual'     => ['año', 'años'],
    ];
    [$sing, $plur] = $mapa[$freq] ?? ['periodo', 'periodos'];

    return $n === 1 ? $sing : $plur;
}

/** Lista de frecuencias válidas. */
function frecuencias(): array
{
    return ['diario', 'semanal', 'quincenal', 'mensual', 'anual'];
}

/** Token CSRF de la sesión (se genera una sola vez). */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

/** Valida el token CSRF recibido. */
function csrf_check(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(403);
        exit('Token de seguridad inválido. Recarga la página e inténtalo de nuevo.');
    }
}

/** Redirección con patrón Post/Redirect/Get (respeta la ruta base). */
function redirect(string $path): void
{
    header('Location: ' . base_path() . $path, true, 303);
    exit;
}

/** Responde JSON y termina. */
function json_out(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** ¿La petición espera JSON (AJAX)? */
function wants_json(): bool
{
    $xrw    = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';

    return strtolower($xrw) === 'xmlhttprequest' || str_contains($accept, 'application/json');
}

/** Renderiza una vista dentro del layout. */
function render(string $template, array $data = [], string $title = 'Pagos'): void
{
    extract($data, EXTR_SKIP);
    ob_start();
    include __DIR__ . '/../views/' . $template . '.php';
    $content = ob_get_clean();
    include __DIR__ . '/../views/layout.php';
}
