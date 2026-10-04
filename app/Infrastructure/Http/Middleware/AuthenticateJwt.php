<?php

namespace App\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;

final class AuthenticateJwt
{
    public function handle(Request $request, Closure $next): Response
    {
        // Resolve the current bearer token on every request, including consecutive test requests.
        $guard = auth('api');
        $guard->forgetUser();
        try {
            $token = $request->bearerToken();
            $user = $token ? $guard->setToken($token)->user() : null;
        } catch (JWTException $exception) {
            return response()->json(['message' => 'Sesión no válida o expirada. Inicie sesión nuevamente.'], 401);
        }
        if (!$user) {
            return response()->json(['message' => 'Debe iniciar sesión.'], 401);
        }
        if ($user->status !== 'active') {
            return response()->json(['message' => 'Su cuenta se encuentra inactiva. Consulte con administración.'], 403);
        }
        auth()->shouldUse('api');
        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');
        return $response;
    }
}
