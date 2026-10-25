<?php

namespace App\Helpers;

final class Validador
{
    public static function email(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function passwordFuerte(string $pass): array
    {
        $errores = [];

        if (strlen($pass) < 8) {
            $errores[] = 'Debe tener al menos 8 caracteres.';
        }

        if (!preg_match('/[A-Z]/', $pass)) {
            $errores[] = 'Debe incluir al menos una letra mayuscula.';
        }

        if (!preg_match('/[a-z]/', $pass)) {
            $errores[] = 'Debe incluir al menos una letra minuscula.';
        }

        if (!preg_match('/[0-9]/', $pass)) {
            $errores[] = 'Debe incluir al menos un numero.';
        }

        if (!preg_match('/[^A-Za-z0-9]/', $pass)) {
            $errores[] = 'Debe incluir al menos un simbolo (ej. !@#$%&*).';
        }

        return $errores;
    }

    public static function requerido(array $datos, array $campos): array
    {
        $faltantes = [];

        foreach ($campos as $campo) {
            if (!array_key_exists($campo, $datos)) {
                $faltantes[] = $campo;
                continue;
            }

            $valor = $datos[$campo];

            if (is_string($valor) && trim($valor) === '') {
                $faltantes[] = $campo;
                continue;
            }

            if ($valor === null) {
                $faltantes[] = $campo;
            }
        }

        return $faltantes;
    }
}
