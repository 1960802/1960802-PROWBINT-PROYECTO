<?php

namespace App\Middleware;

use App\Core\Config;

/**
 * Middleware de CORS configurado de forma explicita.
 * Permite el origen del cliente (definido en .env) y rechaza los origenes no autorizados.
 */
final class CorsMiddleware
{
    public function handle(): void
    {
        $origenes = Config::get('cors.allowed_origins', []);
        $origen   = $_SERVER['HTTP_ORIGIN'] ?? '';

        if ($origen !== '' && in_array($origen, $origenes, true)) {
            header("Access-Control-Allow-Origin: {$origen}");
            header('Vary: Origin');
        } elseif ($origen !== '' && empty($origenes)) {
            // En caso de no configurar nada, se niega explicitamente (no se usa *).
            // Esto obliga a configurar los origenes.
        } elseif ($origen !== '' && !in_array($origen, $origenes, true)) {
            // Origen no autorizado: no se envia cabecera Allow-Origin.
            // El navegador bloqueara la peticion.
        }

        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 86400');
    }
}
