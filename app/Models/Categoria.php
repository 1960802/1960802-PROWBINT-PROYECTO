<?php

namespace App\Models;

use App\Core\Database;

final class Categoria
{
    public function todas(): array
    {
        $sql  = "SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre ASC";
        $stmt = Database::connection()->query($sql);

        return $stmt->fetchAll();
    }

    public function crear(string $nombre, string $descripcion = ''): int
    {
        $sql  = "INSERT INTO categorias (nombre, descripcion, activo) VALUES (:nombre, :descripcion, 1)";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':nombre'      => $nombre,
            ':descripcion' => $descripcion,
        ]);

        return (int) Database::connection()->lastInsertId();
    }
}
