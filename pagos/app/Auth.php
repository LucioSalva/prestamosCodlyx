<?php

declare(strict_types=1);

namespace App;

/**
 * Autenticación basada en sesión.
 */
final class Auth
{
    /** Usuario autenticado o null. */
    public static function user(): ?array
    {
        return $_SESSION['auth'] ?? null;
    }

    public static function check(): bool
    {
        return isset($_SESSION['auth']);
    }

    /** ¿El usuario actual tiene rol "Dios" (super-admin)? */
    public static function isDios(): bool
    {
        return (($_SESSION['auth']['rol'] ?? '') === 'dios');
    }

    /** Inicia sesión: regenera el id y guarda los datos mínimos. */
    public static function login(array $usuario): void
    {
        session_regenerate_id(true);
        $_SESSION['auth'] = [
            'id'      => (int) $usuario['id'],
            'usuario' => (string) $usuario['usuario'],
            'rol'     => (string) $usuario['rol'],
        ];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}
