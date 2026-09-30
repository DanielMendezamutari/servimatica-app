<?php

namespace App\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user('api')?->role !== 'dueno') {
            return response()->json(['message' => 'No tiene permisos para acceder a esta sección.'], 403);
        }
        return $next($request);
    }
}
