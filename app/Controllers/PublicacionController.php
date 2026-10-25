<?php

namespace App\Controllers;

use App\Helpers\Response;
use App\Helpers\Upload;
use App\Core\HttpException;
use App\Models\Publicacion;

final class PublicacionController
{
    public function feed(): string
    {
        $limite = isset($_GET['limite']) ? (int) $_GET['limite'] : 20;
        $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;

        $pubs = (new Publicacion())->feedPublico($limite, $offset);

        return Response::ok(['publicaciones' => $pubs, 'limite' => $limite, 'offset' => $offset]);
    }

    public function misPublicaciones(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $id = (int) ($_SESSION['usuario_id'] ?? 0);

        if ($id === 0) {
            throw new HttpException(401, 'NO_AUTENTICADO', 'Se requiere autenticacion.');
        }

        $pubs = (new Publicacion())->porUsuario($id);

        return Response::ok(['publicaciones' => $pubs]);
    }

    public function crear(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $id = (int) ($_SESSION['usuario_id'] ?? 0);

        if ($id === 0) {
            throw new HttpException(401, 'NO_AUTENTICADO', 'Se requiere autenticacion.');
        }

        $titulo      = trim($_POST['titulo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $categoriaId = isset($_POST['categoria_id']) && $_POST['categoria_id'] !== '' ? (int) $_POST['categoria_id'] : null;
        $esPublico   = isset($_POST['es_publico']) ? (int) $_POST['es_publico'] : 1;

        if ($titulo === '') {
            throw new HttpException(400, 'TITULO_REQUERIDO', 'El titulo es obligatorio.');
        }

        $rutaImagen = null;

        if (!empty($_FILES['imagen']) && ($_FILES['imagen']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $subida = Upload::imagen($_FILES['imagen'], 'publicaciones');
            $rutaImagen = $subida['url'];
        } elseif (empty($_FILES['imagen']) || ($_FILES['imagen']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            // Permite publicar solo con texto si lo desea, pero segun el proyecto es multimedia; se deja flexible.
        }

        $pubId = (new Publicacion())->crear([
            'usuario_id'   => $id,
            'titulo'       => $titulo,
            'descripcion'  => $descripcion,
            'categoria_id' => $categoriaId,
            'es_publico'   => $esPublico,
            'ruta_imagen'  => $rutaImagen,
            'ruta_video'   => null,
        ]);

        return Response::creado(['publicacion_id' => $pubId], 'Publicacion creada correctamente.');
    }

    public function buscar(): string
    {
        $q = trim($_GET['q'] ?? '');
        $cat = isset($_GET['categoria_id']) && $_GET['categoria_id'] !== '' ? (int) $_GET['categoria_id'] : null;

        if ($q === '' && $cat === null) {
            return Response::ok(['publicaciones' => []]);
        }

        $pubs = (new Publicacion())->buscar($q, $cat);

        return Response::ok(['publicaciones' => $pubs, 'q' => $q]);
    }
}
