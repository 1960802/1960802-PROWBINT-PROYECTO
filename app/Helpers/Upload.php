<?php

namespace App\Helpers;

use App\Core\Config;
use App\Core\HttpException;

final class Upload
{
    public static function imagen(array $file, string $carpeta = 'publicaciones'): array
    {
        if (empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new HttpException(400, 'ARCHIVO_REQUERIDO', 'Es necesario adjuntar una imagen valida.');
        }

        $maxMb = (int) Config::get('uploads.max_mb', 5);
        $maxBytes = $maxMb * 1024 * 1024;

        if (($file['size'] ?? 0) > $maxBytes) {
            throw new HttpException(400, 'ARCHIVO_GRANDE', "El archivo supera el limite de {$maxMb} MB.");
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);

        $permitidos = Config::get('uploads.tipos', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);

        if (!in_array($mime, $permitidos, true)) {
            throw new HttpException(400, 'TIPO_NO_PERMITIDO', 'Solo se permiten imagenes JPEG, PNG, WebP o GIF.');
        }

        $extensiones = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        ];

        $ext  = $extensiones[$mime];
        $nombre = bin2hex(random_bytes(16)) . '.' . $ext;
        $destDir = Config::get('uploads.path') . '/' . trim($carpeta, '/');
        $destPath = $destDir . '/' . $nombre;

        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            throw new HttpException(500, 'SUBIDA_FALLIDA', 'No fue posible guardar el archivo.');
        }

        $urlBase = Config::get('uploads.url');

        return [
            'ruta' => $destPath,
            'url'  => rtrim($urlBase, '/') . '/' . trim($carpeta, '/') . '/' . $nombre,
            'mime' => $mime,
            'nombre' => $nombre,
        ];
    }
}
