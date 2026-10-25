<?php

/**
 * Configuracion central de la aplicacion.
 * Los valores sensibles vienen del archivo .env (nunca del codigo).
 */

use App\Core\Env;

return [
    'app' => [
        'name'    => Env::get('APP_NAME', 'WhiteArt'),
        'env'     => Env::get('APP_ENV', 'local'),
        'debug'   => Env::bool('APP_DEBUG', false),
        'url'     => rtrim(Env::get('APP_URL', 'http://localhost:8080'), '/'),
    ],

    'db' => [
        'host'    => Env::get('DB_HOST', '127.0.0.1'),
        'port'    => Env::get('DB_PORT', '3306'),
        'name'    => Env::get('DB_NAME', 'whiteart'),
        'user'    => Env::get('DB_USER', 'root'),
        'pass'    => Env::get('DB_PASS', ''),
        'charset' => 'utf8mb4',
    ],

    'cors' => [
        'allowed_origins' => array_values(array_filter(array_map(
            'trim',
            explode(',', Env::get('CORS_ALLOWED_ORIGINS', 'http://localhost:8080'))
        ))),
    ],

    'session' => [
        'name'     => Env::get('SESSION_NAME', 'whiteart_sid'),
        'lifetime' => (int) Env::get('SESSION_LIFETIME', '7200'),
        'samesite' => Env::get('SESSION_SAMESITE', 'Lax'),
    ],

    'paypal' => [
        'mode'          => Env::get('PAYPAL_MODE', 'sandbox'),
        'client_id'     => Env::get('PAYPAL_CLIENT_ID', ''),
        'client_secret' => Env::get('PAYPAL_CLIENT_SECRET', ''),
        'demo'          => Env::bool('PAYPAL_DEMO', true),
        'currency'      => Env::get('PAYPAL_CURRENCY', 'USD'),
    ],

    'uploads' => [
        'max_mb'   => (int) Env::get('UPLOAD_MAX_MB', '5'),
        'path'     => dirname(__DIR__) . '/public/uploads',
        'url'      => '/uploads',
        'tipos'    => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
    ],
];
