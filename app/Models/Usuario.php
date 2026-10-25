<?php

namespace App\Models;

use App\Core\Database;
use PDO;

final class Usuario
{
    public function crear(array $datos): int
    {
        $sql = "INSERT INTO usuarios (nombre, apellido, email, password_hash, rol, fecha_registro, activo, intentos_fallidos, bloqueado_hasta)
                VALUES (:nombre, :apellido, :email, :password_hash, :rol, NOW(), 1, 0, NULL)";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':nombre'        => $datos['nombre'],
            ':apellido'      => $datos['apellido'],
            ':email'         => strtolower(trim($datos['email'])),
            ':password_hash' => $datos['password_hash'],
            ':rol'           => $datos['rol'],
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function buscarPorEmail(string $email): ?array
    {
        $sql  = "SELECT * FROM usuarios WHERE LOWER(email) = LOWER(:email) LIMIT 1";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':email' => trim($email)]);
        $res = $stmt->fetch();

        return $res === false ? null : $res;
    }

    public function buscarPorId(int $id): ?array
    {
        $sql  = "SELECT id, nombre, apellido, email, rol, foto_perfil, biografia, activo, bloqueado_hasta, fecha_registro
                FROM usuarios WHERE id = :id LIMIT 1";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':id' => $id]);
        $res = $stmt->fetch();

        return $res === false ? null : $res;
    }

    public function actualizarPerfil(int $id, array $datos): void
    {
        $campos = [];
        $params = [':id' => $id];

        if (array_key_exists('nombre', $datos)) {
            $campos[] = 'nombre = :nombre';
            $params[':nombre'] = $datos['nombre'];
        }

        if (array_key_exists('apellido', $datos)) {
            $campos[] = 'apellido = :apellido';
            $params[':apellido'] = $datos['apellido'];
        }

        if (array_key_exists('biografia', $datos)) {
            $campos[] = 'biografia = :biografia';
            $params[':biografia'] = $datos['biografia'];
        }

        if (array_key_exists('foto_perfil', $datos)) {
            $campos[] = 'foto_perfil = :foto_perfil';
            $params[':foto_perfil'] = $datos['foto_perfil'];
        }

        if (empty($campos)) {
            return;
        }

        $sql  = "UPDATE usuarios SET " . implode(', ', $campos) . " WHERE id = :id";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
    }

    public function registrarIntentoFallido(int $id): void
    {
        $sql  = "UPDATE usuarios SET intentos_fallidos = intentos_fallidos + 1 WHERE id = :id";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':id' => $id]);
    }

    public function bloquearCuenta(int $id, int $minutos = 15): void
    {
        $sql  = "UPDATE usuarios SET bloqueado_hasta = DATE_ADD(NOW(), INTERVAL :minutos MINUTE), intentos_fallidos = 3 WHERE id = :id";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':id' => $id, ':minutos' => $minutos]);
    }

    public function resetearIntentos(int $id): void
    {
        $sql  = "UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = :id";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':id' => $id]);
    }

    public function estaBloqueado(array $usuario): bool
    {
        if (empty($usuario['bloqueado_hasta'])) {
            return false;
        }

        $stmt = Database::connection()->prepare("SELECT NOW() >= :hasta AS vencido");
        $stmt->execute([':hasta' => $usuario['bloqueado_hasta']]);
        $res = $stmt->fetch();

        return $res && $res['vencido'] == 0;
    }

    public function listar(): array
    {
        $sql  = "SELECT id, nombre, apellido, email, rol, activo, intentos_fallidos, bloqueado_hasta, fecha_registro FROM usuarios ORDER BY fecha_registro DESC";
        $stmt = Database::connection()->query($sql);

        return $stmt->fetchAll();
    }

    public function cambiarActivo(int $id, int $activo): void
    {
        $sql  = "UPDATE usuarios SET activo = :activo WHERE id = :id";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':activo' => $activo, ':id' => $id]);
    }
}
