<?php

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\User\{
    ExportUsersToExcelUseCase,
    ListUsersUseCase,
    RegisterUserUseCase,
    ToggleUserStatusUseCase,
    UpdateUserUseCase
};
use App\Infrastructure\Http\Requests\UserRequest;
use Illuminate\Http\{JsonResponse, Request};
use Symfony\Component\HttpFoundation\StreamedResponse;

final class UserController
{
    public function index(ListUsersUseCase $list): JsonResponse
    {
        return response()->json(['data' => $list->execute()]);
    }

    public function exportExcel(ExportUsersToExcelUseCase $export): StreamedResponse
    {
        return $export->execute();
    }

    public function store(UserRequest $request, RegisterUserUseCase $register): JsonResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = $path;
        }

        $user = $register->execute($validated);

        return response()->json([
            'message' => 'Usuario creado exitosamente.',
            'data' => $user->publicData(),
        ], 201);
    }

    public function update(UserRequest $request, int $id, UpdateUserUseCase $update): JsonResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = $path;
        }

        $user = $update->execute($id, $request->user('api')->id, $validated);

        return response()->json([
            'message' => 'Usuario actualizado correctamente.',
            'data' => $user->publicData(),
        ]);
    }

    public function toggleStatus(Request $request, int $id, ToggleUserStatusUseCase $toggle): JsonResponse
    {
        $user = $toggle->execute($id, $request->user('api')->id);

        return response()->json([
            'message' => 'Estado de usuario actualizado correctamente.',
            'data' => ['id' => $user->id, 'status' => $user->status->value],
        ]);
    }
}
