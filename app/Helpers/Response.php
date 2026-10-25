<?php

namespace App\Helpers;

final class Response
{
    public static function json(mixed $data, int $status = 200): string
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function ok(mixed $data = [], string $mensaje = 'OK'): string
    {
        return self::json([
            'ok'      => true,
            'mensaje' => $mensaje,
            'data'    => $data,
        ], 200);
    }

    public static function creado(mixed $data = [], string $mensaje = 'Creado correctamente'): string
    {
        return self::json([
            'ok'      => true,
            'mensaje' => $mensaje,
            'data'    => $data,
        ], 201);
    }
}
