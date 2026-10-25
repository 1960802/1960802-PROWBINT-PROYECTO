<?php

require_once dirname(__DIR__) . '/app/Core/Env.php';
require_once dirname(__DIR__) . '/app/Core/Config.php';
require_once dirname(__DIR__) . '/app/Core/Database.php';
require_once dirname(__DIR__) . '/app/Core/HttpException.php';
require_once dirname(__DIR__) . '/app/Core/Router.php';

require_once dirname(__DIR__) . '/app/Middleware/CorsMiddleware.php';
require_once dirname(__DIR__) . '/app/Middleware/AuthMiddleware.php';

require_once dirname(__DIR__) . '/app/Helpers/Response.php';
require_once dirname(__DIR__) . '/app/Helpers/Validador.php';
require_once dirname(__DIR__) . '/app/Helpers/Upload.php';

require_once dirname(__DIR__) . '/app/Models/Usuario.php';
require_once dirname(__DIR__) . '/app/Models/Publicacion.php';
require_once dirname(__DIR__) . '/app/Models/Categoria.php';

require_once dirname(__DIR__) . '/app/Controllers/AuthController.php';
require_once dirname(__DIR__) . '/app/Controllers/PerfilController.php';
require_once dirname(__DIR__) . '/app/Controllers/PublicacionController.php';
require_once dirname(__DIR__) . '/app/Controllers/CategoriaController.php';
require_once dirname(__DIR__) . '/app/Controllers/AdminController.php';
require_once dirname(__DIR__) . '/app/Controllers/PayPalController.php';

require_once dirname(__DIR__) . '/app/Services/PayPalService.php';

use App\Core\Env;
use App\Core\Config;
use App\Core\HttpException;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Controllers\AuthController;
use App\Controllers\PerfilController;
use App\Controllers\PublicacionController;
use App\Controllers\CategoriaController;
use App\Controllers\AdminController;
use App\Controllers\PayPalController;

Env::load(dirname(__DIR__) . '/.env');
$config = require dirname(__DIR__) . '/config/config.php';
Config::load($config);

if (session_status() !== PHP_SESSION_ACTIVE) {
    $ses = Config::get('session');
    session_name($ses['name'] ?? 'whiteart_sid');
    session_set_cookie_params([
        'lifetime' => (int) ($ses['lifetime'] ?? 7200),
        'path'     => '/',
        'secure'   => $ses['secure'] ?? false,
        'httponly' => true,
        'samesite' => $ses['samesite'] ?? 'Lax',
    ]);
    session_start();
}

if (Config::get('app.debug')) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

$router = new Router();

$router->post('auth/registro', [AuthController::class, 'registro']);
$router->post('auth/login',    [AuthController::class, 'login']);
$router->post('auth/google',   [AuthController::class, 'google']);
$router->post('auth/logout',   [AuthController::class, 'logout']);
$router->get('auth/yo',        [AuthController::class, 'yo']);

$router->get('perfil',        [PerfilController::class, 'ver'],        [new AuthMiddleware()]);
$router->post('perfil/actualizar', [PerfilController::class, 'actualizar'], [new AuthMiddleware()]);

$router->get('publicaciones/feed',      [PublicacionController::class, 'feed']);
$router->get('publicaciones/mis',       [PublicacionController::class, 'misPublicaciones'], [new AuthMiddleware()]);
$router->post('publicaciones/crear',    [PublicacionController::class, 'crear'], [new AuthMiddleware()]);
$router->get('publicaciones/buscar',    [PublicacionController::class, 'buscar']);

$router->get('categorias', [CategoriaController::class, 'listar']);

$router->post('paypal/crear-orden',  [PayPalController::class, 'crearOrden'],  [new AuthMiddleware()]);
$router->post('paypal/capturar',     [PayPalController::class, 'capturarOrden'], [new AuthMiddleware()]);

$router->get('admin/usuarios',       [AdminController::class, 'usuarios'],        [new AuthMiddleware(['administrador'])]);
$router->post('admin/usuarios/bloquear', [AdminController::class, 'bloquear'],   [new AuthMiddleware(['administrador'])]);
$router->post('admin/categorias',   [AdminController::class, 'crearCategoria'], [new AuthMiddleware(['administrador'])]);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri    = $_SERVER['REQUEST_URI'] ?? '/';

// Si viene por ruta virtual, usarla tal como viene
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
if ($scriptName !== '' && strpos($uri, $scriptName) === 0) {
    $uri = substr($uri, strlen($scriptName));
}

try {
    $router->dispatch($method, $uri);
} catch (HttpException $e) {
    http_response_code($e->status());
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok'       => false,
        'codigo'   => $e->codigo(),
        'mensaje'  => $e->getMessage(),
        'detalles' => $e->detalles(),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    $debug = Config::get('app.debug');

    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok'      => false,
        'codigo'  => 'ERROR_INTERNO',
        'mensaje' => $debug ? $e->getMessage() : 'Error interno del servidor.',
        'traza'   => $debug ? $e->getTrace() : [],
    ], JSON_UNESCAPED_UNICODE);
}
