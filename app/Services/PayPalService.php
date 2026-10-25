<?php

namespace App\Services;

use App\Core\Config;
use App\Core\HttpException;

final class PayPalService
{
    private string $base;
    private string $clientId;
    private string $clientSecret;
    private bool $demo;

    public function __construct()
    {
        $mode = Config::get('paypal.mode', 'sandbox');
        $this->base         = $mode === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
        $this->clientId     = Config::get('paypal.client_id');
        $this->clientSecret = Config::get('paypal.client_secret');
        $this->demo         = Config::bool('paypal.demo', true);
    }

    private function token(): string
    {
        if ($this->demo || $this->clientId === '' || $this->clientSecret === '') {
            return 'DEMO_TOKEN';
        }

        $ch = curl_init($this->base . '/v1/oauth2/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->clientId . ':' . $this->clientSecret);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Accept-Language: en_US',
        ]);

        $res = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new HttpException(502, 'PAYPAL_TOKEN_ERROR', 'Error al obtener token de PayPal.', ['detalle' => $err]);
        }

        $json = json_decode($res, true);

        if (!isset($json['access_token'])) {
            throw new HttpException(502, 'PAYPAL_TOKEN_ERROR', 'Respuesta inesperada al solicitar token de PayPal.', ['respuesta' => $json]);
        }

        return $json['access_token'];
    }

    public function crearOrden(array $items, float $total, string $moneda = 'USD'): array
    {
        if ($this->demo || $this->clientId === '' || $this->clientSecret === '') {
            $ordenId = 'DEMO-' . strtoupper(bin2hex(random_bytes(8)));
            return [
                'id'        => $ordenId,
                'status'    => 'CREATED',
                'links'     => [
                    ['rel' => 'approve', 'href' => '#paypal-demo-approve'],
                ],
                'demo'      => true,
                'mensaje'   => 'Modo DEMO activo (PAYPAL_DEMO=true). Pegue sus credenciales en .env para usar PayPal real.',
            ];
        }

        $token = $this->token();

        $body = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => $moneda,
                        'value'         => number_format($total, 2, '.', ''),
                    ],
                    'items'  => $items,
                ],
            ],
            'application_context' => [
                'return_url' => Config::get('app.url') . '/public/api/paypal/return',
                'cancel_url' => Config::get('app.url') . '/pago.html?cancelado=1',
                'brand_name' => 'WhiteArt',
                'user_action' => 'PAY_NOW',
            ],
        ];

        $ch = curl_init($this->base . '/v2/checkout/orders');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));

        $res = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            throw new HttpException(502, 'PAYPAL_ORDEN_ERROR', 'Error al crear orden en PayPal.', ['detalle' => $err]);
        }

        $json = json_decode($res, true);

        if ($code < 200 || $code >= 300) {
            throw new HttpException(502, 'PAYPAL_ORDEN_ERROR', 'PayPal rechazo la solicitud de crear orden.', [
                'codigo_http' => $code,
                'respuesta'   => $json,
            ]);
        }

        return $json;
    }

    public function capturarOrden(string $ordenId): array
    {
        if ($this->demo || str_starts_with($ordenId, 'DEMO-')) {
            return [
                'id'       => $ordenId,
                'status'   => 'COMPLETED',
                'demo'     => true,
                'mensaje'  => 'Pago simulado correctamente en modo DEMO.',
            ];
        }

        $token = $this->token();

        $ch = curl_init($this->base . '/v2/checkout/orders/' . $ordenId . '/capture');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');

        $res = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            throw new HttpException(502, 'PAYPAL_CAPTURA_ERROR', 'Error al capturar orden de PayPal.', ['detalle' => $err]);
        }

        $json = json_decode($res, true);

        if ($code < 200 || $code >= 300) {
            throw new HttpException(502, 'PAYPAL_CAPTURA_ERROR', 'PayPal rechazo la captura de la orden.', [
                'codigo_http' => $code,
                'respuesta'   => $json,
            ]);
        }

        return $json;
    }
}
