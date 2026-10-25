<?php

namespace App\Controllers;

use App\Helpers\Response;
use App\Core\HttpException;
use App\Models\Usuario;
use App\Models\Categoria;

final class AdminController
{
    public function usuarios(): string
    {
        $usuarios = (new Usuario())->listar();

        return Response::ok(['usuarios' => $usuarios]);
    }

    public function bloquear(): string
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = isset($body['usuario_id']) ? (int) $body['usuario_id'] : 0;
        $activo = isset($body['activo']) ? (int) $body['activo'] : 0;

        if ($id === 0) {
            throw new HttpException(400, 'ID_INVALIDO', 'ID de usuario invalido.');
        }

        (new Usuario())->cambiarActivo($id, $activo);

        return Response::ok([], $activo === 1 ? 'Usuario activado.' : 'Usuario desactivado.');
    }

    public function crearCategoria(): string
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $nombre = trim($body['nombre'] ?? '');
        $desc = trim($body['descripcion'] ?? '');

        if ($nombre === '') {
            throw new HttpException(400, 'NOMBRE_REQUERIDO', 'El nombre de la categoria es obligatorio.');
        }

        $id = (new Categoria())->crear($nombre, $desc);

        return Response::creado(['categoria_id' => $id], 'Categoria creada.');
    }
}
