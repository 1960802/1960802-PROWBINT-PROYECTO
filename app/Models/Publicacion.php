<?php

namespace App\Models;

use App\Core\Database;

final class Publicacion
{
    public function crear(array $datos): int
    {
        $sql = "INSERT INTO publicaciones (usuario_id, titulo, descripcion, categoria_id, es_publico, ruta_imagen, ruta_video, fecha_creacion)
                VALUES (:usuario_id, :titulo, :descripcion, :categoria_id, :es_publico, :ruta_imagen, :ruta_video, NOW())";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':usuario_id'   => $datos['usuario_id'],
            ':titulo'       => $datos['titulo'],
            ':descripcion'  => $datos['descripcion'],
            ':categoria_id' => $datos['categoria_id'],
            ':es_publico'   => $datos['es_publico'] ? 1 : 0,
            ':ruta_imagen'  => $datos['ruta_imagen'] ?? null,
            ':ruta_video'   => $datos['ruta_video'] ?? null,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function feedPublico(int $limite = 20, int $offset = 0): array
    {
        $sql = "SELECT p.id, p.titulo, p.descripcion, p.ruta_imagen, p.ruta_video, p.fecha_creacion, p.es_publico,
                       u.id AS usuario_id, u.nombre, u.apellido, u.foto_perfil,
                       c.nombre AS categoria
                FROM publicaciones p
                INNER JOIN usuarios u ON u.id = p.usuario_id
                LEFT JOIN categorias c ON c.id = p.categoria_id
                WHERE p.es_publico = 1
                ORDER BY p.fecha_creacion DESC
                LIMIT :limite OFFSET :offset";
        $stmt = Database::connection()->prepare($sql);
        $stmt->bindValue(':limite', $limite, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function porUsuario(int $usuarioId): array
    {
        $sql = "SELECT p.*, c.nombre AS categoria
                FROM publicaciones p
                LEFT JOIN categorias c ON c.id = p.categoria_id
                WHERE p.usuario_id = :usuario_id
                ORDER BY p.fecha_creacion DESC";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }

    public function buscar(string $q, ?int $categoriaId = null): array
    {
        $sql = "SELECT p.id, p.titulo, p.descripcion, p.ruta_imagen, p.fecha_creacion,
                       u.nombre, u.apellido,
                       c.nombre AS categoria
                FROM publicaciones p
                INNER JOIN usuarios u ON u.id = p.usuario_id
                LEFT JOIN categorias c ON c.id = p.categoria_id
                WHERE p.es_publico = 1
                  AND (LOWER(p.titulo) LIKE LOWER(:q) OR LOWER(p.descripcion) LIKE LOWER(:q) OR LOWER(u.nombre) LIKE LOWER(:q))";
        $params = [':q' => '%' . $q . '%'];

        if ($categoriaId !== null) {
            $sql .= " AND p.categoria_id = :cat";
            $params[':cat'] = $categoriaId;
        }

        $sql .= " ORDER BY p.fecha_creacion DESC LIMIT 50";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}
