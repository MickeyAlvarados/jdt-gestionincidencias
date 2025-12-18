<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Temporal fix para Mac: excluir crear-sesion de CSRF
        // TODO: Investigar por qué Inertia.js causa problemas de CSRF en Mac
        '/chat/crear-sesion',
    ];
}
