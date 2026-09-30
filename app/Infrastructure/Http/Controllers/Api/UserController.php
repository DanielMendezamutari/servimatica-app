<?php

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\User\{ListUsersUseCase, RegisterUserUseCase, ToggleUserStatusUseCase, UpdateUserUseCase};
use App\Infrastructure\Http\Requests\UserRequest;
use Illuminate\Http\{JsonResponse, Request};

final class UserController
{
    public function index(ListUsersUseCase $list): JsonResponse
    {
        return response()->json(['data' => $list->execute()]);
    }
    public function store(UserRequest $request, RegisterUserUseCase $register): JsonResponse
    {
        return response()->json(['message' => 'Usuario creado exitosamente.',
            'data' => $register->execute($request->validated())->publicData()], 201);
    }
    public function update(UserRequest $request, int $id, UpdateUserUseCase $update): JsonResponse
    {
        return response()->json(['message' => 'Usuario actualizado correctamente.',
            'data' => $update->execute($id, $request->user('api')->id, $request->validated())->publicData()]);
    }
    public function toggleStatus(Request $request, int $id, ToggleUserStatusUseCase $toggle): JsonResponse
    {
        $user = $toggle->execute($id, $request->user('api')->id);
        return response()->json(['message' => 'Estado de usuario actualizado correctamente.',
            'data' => ['id' => $user->id, 'status' => $user->status->value]]);
    }
}
