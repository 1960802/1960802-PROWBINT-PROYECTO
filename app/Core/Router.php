<?php

namespace App\Core;

use App\Middleware\CorsMiddleware;
use App\Middleware\AuthMiddleware;

/**
 * Enrutador simple para este proyecto (MVC puro). No usa frameworks.
 */
final class Router
{
    private array $routes = [];
    private array $middlewares = [];

    public function get(string $path, array|callable $handler, array $middlewares = []): void
    {
        $this->add('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, array|callable $handler, array $middlewares = []): void
    {
        $this->add('POST', $path, $handler, $middlewares);
    }

    public function put(string $path, array|callable $handler, array $middlewares = []): void
    {
        $this->add('PUT', $path, $handler, $middlewares);
    }

    public function delete(string $path, array|callable $handler, array $middlewares = []): void
    {
        $this->add('DELETE', $path, $handler, $middlewares);
    }

    public function options(string $path, array|callable $handler, array $middlewares = []): void
    {
        $this->add('OPTIONS', $path, $handler, $middlewares);
    }

    private function add(string $method, string $path, array|callable $handler, array $middlewares): void
    {
        $this->routes[] = [
            'method'      => $method,
            'path'        => trim($path, '/'),
            'handler'     => $handler,
            'middlewares' => $middlewares,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        // CORS siempre primero.
        (new CorsMiddleware())->handle();

        if ($method === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        $rutaLimpia = trim(parse_url($uri, PHP_URL_PATH), '/');

        // Remueve prefijo /api si existe para trabajar rutas limpias.
        // Funciona tanto si se accede directamente como api/auth/login o con rutas base
        if (strpos($rutaLimpia, '/api/') !== false) {
            $pos = strpos($rutaLimpia, '/api/');
            $rutaLimpia = substr($rutaLimpia, $pos + 5);
        } elseif (str_starts_with($rutaLimpia, 'api/')) {
            $rutaLimpia = substr($rutaLimpia, 4);
        } elseif (str_ends_with($rutaLimpia, '/api')) {
            $rutaLimpia = '';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->match($route['path'], $rutaLimpia);

            if ($params === null) {
                continue;
            }

            // Middlewares de ruta (AuthMiddleware, etc.).
            foreach ($route['middlewares'] as $mw) {
                if (is_string($mw)) {
                    $mwInstance = new $mw();
                    $mwInstance->handle($params);
                    continue;
                }

                if (is_object($mw) && method_exists($mw, 'handle')) {
                    $mw->handle($params);
                    continue;
                }
            }

            $handler = $route['handler'];

            if (is_array($handler)) {
                [$class, $action] = $handler;
                $controller = new $class();

                echo $controller->$action($params);
                return;
            }

            if (is_callable($handler)) {
                echo $handler($params);
                return;
            }
        }

        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok'     => false,
            'codigo' => 'RUTA_NO_ENCONTRADA',
            'mensaje' => 'Endpoint no encontrado.',
        ], JSON_UNESCAPED_UNICODE);
    }

    private function match(string $routePath, string $requestPath): ?array
    {
        if ($routePath === $requestPath) {
            return [];
        }

        $routeParts = explode('/', $routePath);
        $reqParts   = explode('/', $requestPath);

        if (count($routeParts) !== count($reqParts)) {
            return null;
        }

        $params = [];

        foreach ($routeParts as $i => $part) {
            if (str_starts_with($part, '{') && str_ends_with($part, '}')) {
                $key = trim($part, '{}');
                $params[$key] = $reqParts[$i];
                continue;
            }

            if ($part !== $reqParts[$i]) {
                return null;
            }
        }

        return $params;
    }
}
