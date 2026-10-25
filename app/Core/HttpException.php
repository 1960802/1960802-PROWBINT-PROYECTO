<?php

namespace App\Core;

use RuntimeException;

/**
 * Excepcion HTTP: permite lanzar respuestas de error desde cualquier capa
 * y que el front controller las convierta en JSON con el codigo correcto.
 */
class HttpException extends RuntimeException
{
    private int $status;
    private string $codigo;
    private array $detalles;

    public function __construct(int $status, string $codigo, string $mensaje, array $detalles = [])
    {
        parent::__construct($mensaje);

        $this->status   = $status;
        $this->codigo   = $codigo;
        $this->detalles = $detalles;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function codigo(): string
    {
        return $this->codigo;
    }

    public function detalles(): array
    {
        return $this->detalles;
    }
}
