<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Clase de conexion a MySQL usando POO (PDO).
 * Es la unica puerta de entrada a la base de datos: los modelos la usan,
 * las vistas y los controladores nunca crean conexiones.
 */
final class Database
{
    private static ?PDO $connection = null;

    private function __construct()
    {
    }

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $host    = Config::get('db.host');
        $port    = Config::get('db.port');
        $name    = Config::get('db.name');
        $charset = Config::get('db.charset', 'utf8mb4');

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

        try {
            self::$connection = new PDO(
                $dsn,
                Config::get('db.user'),
                Config::get('db.pass'),
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            throw new HttpException(
                500,
                'ERROR_BD',
                'No fue posible conectar con la base de datos.',
                Config::get('app.debug') ? ['detalle' => $e->getMessage()] : []
            );
        }

        return self::$connection;
    }

    public static function reset(): void
    {
        self::$connection = null;
    }
}
