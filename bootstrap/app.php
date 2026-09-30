<?php

use App\Infrastructure\Http\Middleware\{AuthenticateJwt, RequireOwner};
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\{Exceptions, Middleware};
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up'
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias(['jwt.auth' => AuthenticateJwt::class, 'owner' => RequireOwner::class]);
        $middleware->trimStrings(except: ['password', 'pin']);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Los datos proporcionados son inválidos.', 'errors' => $exception->errors()], 422);
            }
        });
        $exceptions->render(function (UniqueConstraintViolationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'El alias o correo ya está registrado.',
                    'errors' => ['username' => ['Verifique que el alias y correo sean únicos.']]], 422);
            }
        });
        $exceptions->render(function (\InvalidArgumentException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }
        });
    })->create();
