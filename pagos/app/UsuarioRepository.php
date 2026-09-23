<?php

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Acceso a datos de usuarios del sistema.
 * Las contraseñas se guardan SIEMPRE como hash bcrypt (nunca en texto plano).
 */
final class UsuarioRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** Busca un usuario por su nombre (para el login). */
    public function porUsuario(string $usuario): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE usuario = :u LIMIT 1');
        $stmt->execute(['u' => $usuario]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** Lista todos los usuarios. */
    public function todos(): array
    {
        return $this->pdo->query(
            'SELECT id, usuario, rol, creado_en FROM usuarios ORDER BY creado_en ASC, id ASC'
        )->fetchAll();
    }

    public function contar(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
    }

    /**
     * Crea un usuario con contraseña hasheada.
     *
     * @throws RuntimeException si el nombre de usuario ya existe.
     */
    public function crear(string $usuario, string $password, string $rol): int
    {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO usuarios (usuario, pass_hash, rol) VALUES (:u, :h, :r)'
            );
            $stmt->execute(['u' => $usuario, 'h' => $hash, 'r' => $rol]);
        } catch (PDOException $e) {
            // 23000 = violación de restricción (usuario duplicado).
            if ($e->getCode() === '23000') {
                throw new RuntimeException('Ese nombre de usuario ya existe.');
            }
            throw $e;
        }

        return (int) $this->pdo->lastInsertId();
    }

    public function eliminar(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM usuarios WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /** Actualiza el hash (usado para re-hashear si cambia el algoritmo). */
    public function actualizarHash(int $id, string $hash): void
    {
        $stmt = $this->pdo->prepare('UPDATE usuarios SET pass_hash = :h WHERE id = :id');
        $stmt->execute(['h' => $hash, 'id' => $id]);
    }
}
