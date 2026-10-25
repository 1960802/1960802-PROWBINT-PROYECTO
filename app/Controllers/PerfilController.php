<?php

namespace App\Controllers;

use App\Helpers\Response;
use App\Helpers\Upload;
use App\Core\HttpException;
use App\Models\Usuario;

final class PerfilController
{
    public function ver(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $id = (int) ($_SESSION['usuario_id'] ?? 0);

        if ($id === 0) {
            throw new HttpException(401, 'NO_AUTENTICADO', 'Se requiere autenticacion.');
        }

        $usuario = (new Usuario())->buscarPorId($id);

        if (!$usuario) {
            throw new HttpException(404, 'USUARIO_NO_ENCONTRADO', 'Usuario no encontrado.');
        }

        return Response::ok(['usuario' => $usuario]);
    }

    public function actualizar(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $id = (int) ($_SESSION['usuario_id'] ?? 0);

        if ($id === 0) {
            throw new HttpException(401, 'NO_AUTENTICADO', 'Se requiere autenticacion.');
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (stripos($contentType, 'application/json') !== false) {
            $body = json_decode(file_get_contents('php://input'), true) ?? [];
            $datos = [];
            if (array_key_exists('nombre', $body)) $datos['nombre'] = trim($body['nombre']);
            if (array_key_exists('apellido', $body)) $datos['apellido'] = trim($body['apellido']);
            if (array_key_exists('biografia', $body)) $datos['biografia'] = trim($body['biografia']);
            (new Usuario())->actualizarPerfil($id, $datos);
            return Response::ok(['actualizado' => true], 'Perfil actualizado.');
        }

        // FormData (posible subida de foto)
        $datos = [];
        if (isset($_POST['nombre'])) $datos['nombre'] = trim($_POST['nombre']);
        if (isset($_POST['apellido'])) $datos['apellido'] = trim($_POST['apellido']);
        if (isset($_POST['biografia'])) $datos['biografia'] = trim($_POST['biografia']);

        if (!empty($_FILES['foto']) && ($_FILES['foto']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $subida = Upload::imagen($_FILES['foto'], 'perfiles');
            $datos['foto_perfil'] = $subida['url'];
            $_SESSION['usuario_foto'] = $subida['url'];
        }

        (new Usuario())->actualizarPerfil($id, $datos);

        return Response::ok(['actualizado' => true], 'Perfil actualizado.');
    }
}
