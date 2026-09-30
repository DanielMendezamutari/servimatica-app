<?php

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\User\AuthenticateUserUseCase;
use App\Domain\User\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Http\{JsonResponse, Request};

final class AuthController
{
    public function login(Request $request, AuthenticateUserUseCase $authenticate): JsonResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:150'],
            'password' => ['required_without:pin', 'prohibits:pin', 'nullable', 'string', 'max:72'],
            'pin' => ['required_without:password', 'prohibits:password', 'nullable', 'string', 'regex:/\A[0-9]{4}\z/'],
        ], [
            'login.required' => 'El campo de identificación es obligatorio.',
            'required_without' => 'Ingrese una contraseña o un PIN.',
            'prohibits' => 'Seleccione solamente un método de ingreso.',
            'pin.regex' => 'El PIN debe tener exactamente 4 números.',
            'string' => 'Ingrese un texto válido.', 'max' => 'Este campo supera la longitud permitida.',
        ]);
        $user = $authenticate->execute($data['login'], $data['password'] ?? null, $data['pin'] ?? null);
        $token = auth('api')->login(User::findOrFail($user->id));
        return response()->json(['accessToken' => $token, 'tokenType' => 'Bearer',
            'expiresIn' => auth('api')->factory()->getTTL() * 60,
            'userData' => $user->publicData(), 'userAbilityRules' => $user->abilities()]);
    }
    public function me(Request $request, UserRepositoryInterface $users): JsonResponse
    {
        $user = $users->find($request->user('api')->id);
        return response()->json(['userData' => $user->publicData(), 'userAbilityRules' => $user->abilities()]);
    }
    public function logout(): JsonResponse
    {
        auth('api')->logout();
        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }
}
