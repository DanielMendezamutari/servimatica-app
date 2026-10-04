<?php

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\Supplier\CreateSupplierUseCase;
use App\Application\Supplier\ListSuppliersUseCase;
use App\Application\Supplier\ToggleSupplierStatusUseCase;
use App\Application\Supplier\UpdateSupplierUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SupplierController
{
    public function index(Request $request, ListSuppliersUseCase $listSuppliers): JsonResponse
    {
        $page = (int)$request->query('page', 1);
        $perPage = (int)$request->query('per_page', 15);
        $search = $request->query('search') ?? $request->query('query');
        $status = $request->query('status', 'all');

        $result = $listSuppliers->execute($page, $perPage, $search, $status);

        return response()->json($result);
    }

    public function options(ListSuppliersUseCase $listSuppliers): JsonResponse
    {
        $options = $listSuppliers->options();

        return response()->json([
            'data' => $options,
        ]);
    }

    public function store(Request $request, CreateSupplierUseCase $createSupplier): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'nit' => ['nullable', 'string', 'max:30'],
            'contact_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'city' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
        ], [
            'name.required' => 'La razón social o nombre del proveedor es obligatorio.',
            'name.max' => 'El nombre no puede superar 150 caracteres.',
            'email.email' => 'El correo electrónico debe ser una dirección válida.',
        ]);

        $supplier = $createSupplier->execute($validated);

        return response()->json([
            'message' => 'Proveedor registrado exitosamente.',
            'data' => $supplier->toArray(),
        ], 201);
    }

    public function update(int $id, Request $request, UpdateSupplierUseCase $updateSupplier): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'nit' => ['nullable', 'string', 'max:30'],
            'contact_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'city' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'La razón social o nombre del proveedor es obligatorio.',
            'name.max' => 'El nombre no puede superar 150 caracteres.',
            'email.email' => 'El correo electrónico debe ser una dirección válida.',
        ]);

        $supplier = $updateSupplier->execute($id, $validated);

        return response()->json([
            'message' => 'Proveedor actualizado correctamente.',
            'data' => $supplier->toArray(),
        ]);
    }

    public function toggleStatus(int $id, ToggleSupplierStatusUseCase $toggleSupplierStatus): JsonResponse
    {
        $supplier = $toggleSupplierStatus->execute($id);

        return response()->json([
            'message' => 'Estado de proveedor actualizado exitosamente.',
            'data' => $supplier->toArray(),
        ]);
    }
}
