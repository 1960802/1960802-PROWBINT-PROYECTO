<?php

namespace App\Controllers;

use App\Helpers\Response;
use App\Core\HttpException;
use App\Services\PayPalService;

final class PayPalController
{
    public function crearOrden(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $id = (int) ($_SESSION['usuario_id'] ?? 0);

        if ($id === 0) {
            throw new HttpException(401, 'NO_AUTENTICADO', 'Debes iniciar sesion para realizar un pago.');
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $monto = isset($body['monto']) ? (float) $body['monto'] : 0.0;
        $curso = trim($body['curso'] ?? 'Curso WhiteArt');

        if ($monto <= 0) {
            throw new HttpException(400, 'MONTO_INVALIDO', 'El monto debe ser mayor a 0.');
        }

        $items = [
            [
                'name'  => $curso,
                'unit_amount' => [
                    'currency_code' => 'USD',
                    'value'         => number_format($monto, 2, '.', ''),
                ],
                'quantity' => '1',
            ],
        ];

        $servicio = new PayPalService();
        $orden = $servicio->crearOrden($items, $monto, 'USD');

        return Response::ok($orden, 'Orden de PayPal creada.');
    }

    public function capturarOrden(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $id = (int) ($_SESSION['usuario_id'] ?? 0);

        if ($id === 0) {
            throw new HttpException(401, 'NO_AUTENTICADO', 'Debes iniciar sesion para capturar el pago.');
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $ordenId = trim($body['orderID'] ?? $body['orden_id'] ?? '');

        if ($ordenId === '') {
            throw new HttpException(400, 'ORDER_ID_REQUERIDO', 'Falta el ID de la orden de PayPal.');
        }

        $servicio = new PayPalService();
        $captura = $servicio->capturarOrden($ordenId);

        // Aqui se podria guardar la compra en BD (compras, inscripciones, etc.)
        // Se deja listo para ampliar segun el contrato del equipo.

        return Response::ok($captura, 'Pago procesado.');
    }
}
