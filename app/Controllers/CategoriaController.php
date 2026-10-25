<?php

namespace App\Controllers;

use App\Helpers\Response;
use App\Models\Categoria;

final class CategoriaController
{
    public function listar(): string
    {
        $cats = (new Categoria())->todas();

        return Response::ok(['categorias' => $cats]);
    }
}
