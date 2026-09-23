<?php

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Conexión PDO a MySQL (hosting compartido) como singleton.
 * Lee las credenciales de /config.php en la raíz del proyecto.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function conn(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $cfg = require dirname(__DIR__) . '/config.php';

        // El puerto es opcional: en HostGator se usa el socket local
        // (no hace falta), pero permite conectar por TCP cuando se define.
        $dsn = sprintf('mysql:host=%s;', $cfg['host']);
        if (!empty($cfg['port'])) {
            $dsn .= 'port=' . $cfg['port'] . ';';
        }
        $dsn .= sprintf('dbname=%s;charset=%s', $cfg['name'], $cfg['charset'] ?? 'utf8mb4');

        try {
            self::$pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            error_log('DB connection failed: ' . $e->getMessage());
            throw new RuntimeException('No se pudo conectar a la base de datos.', 0, $e);
        }

        return self::$pdo;
    }
}
