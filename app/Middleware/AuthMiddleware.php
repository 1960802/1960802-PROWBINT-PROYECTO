<?php

namespace App\Middleware;

use App\Core\HttpException;

/**
 * Middleware de control de acceso: valida la sesion y el rol antes de ejecutar la accion.
 * Las rutas protegidas son inaccesibles sin credenciales validas.
 */
final class AuthMiddleware
{
    private array $rolesRequeridos;

    public function __construct(array $rolesRequeridos = [])
    {
        $this->rolesRequeridos = $rolesRequeridos;
    }

    public function handle(array $params = []): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['usuario_id'])) {
            throw new HttpException(401, 'NO_AUTENTICADO', 'Se requiere autenticacion para acceder a este recurso.');
        }

        // Si no se especifican roles, solo basta con estar autenticado.
        if (empty($this->rolesRequeridos)) {
            return;
        }

        $rol = $_SESSION['usuario_rol'] ?? '';

        if (!in_array($rol, $this->rolesRequeridos, true)) {
            throw new HttpException(403, 'SIN_PERMISOS', 'No tienes permisos para realizar esta accion.', [
                'rol_requerido' => $this->rolesRequeridos,
                'tu_rol'        => $rol,
            ]);
        }
    }
}
