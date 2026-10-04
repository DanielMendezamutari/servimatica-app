<?php

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\Client\CreateClientUseCase;
use App\Application\Client\ExportClientsToCsvUseCase;
use App\Application\Client\GetClientDetailUseCase;
use App\Application\Client\GetClientWarrantiesUseCase;
use App\Application\Client\ListClientsUseCase;
use App\Application\Client\PaginateClientsUseCase;
use App\Application\Client\ToggleClientStatusUseCase;
use App\Application\Client\UpdateClientUseCase;
use App\Domain\Client\ClientRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class ClientController
{
    public function index(
        Request $request,
        PaginateClientsUseCase $paginateClients,
        ListClientsUseCase $listClients
    ): JsonResponse {
        // Soporte retrocompatible para búsqueda rápida en POS / Cotizaciones
        if ($request->has('query') && ! $request->has('page')) {
            $clients = $listClients->execute($request->query('query'), (int) $request->query('per_page', 20));
            return response()->json([
                'data' => $clients,
            ]);
        }

        $filters = [
            'search' => $request->query('search') ?? $request->query('query'),
            'client_type' => $request->query('client_type'),
            'is_active' => $request->query('is_active'),
        ];

        $perPage = (int) $request->query('per_page', 15);
        $page = (int) $request->query('page', 1);

        $result = $paginateClients->execute($filters, $perPage, $page);

        return response()->json($result);
    }

    public function show(int $id, GetClientDetailUseCase $getClientDetail): JsonResponse
    {
        $data = $getClientDetail->execute($id);

        return response()->json([
            'data' => $data,
        ]);
    }

    public function store(Request $request, CreateClientUseCase $createClient): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'nit_ci' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'client_type' => ['nullable', 'string', 'in:final,mayorista,empresa'],
            'city' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'name.required' => 'La razón social o nombre del cliente es obligatorio.',
            'name.max' => 'El nombre no puede superar 150 caracteres.',
            'email.email' => 'El correo electrónico debe ser una dirección válida.',
            'client_type.in' => 'El tipo de cliente debe ser final, mayorista o empresa.',
        ]);

        $client = $createClient->execute($validated);

        return response()->json([
            'message' => 'Cliente registrado correctamente.',
            'data' => $client->toArray(),
        ], 201);
    }

    public function update(int $id, Request $request, UpdateClientUseCase $updateClient): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'nit_ci' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'client_type' => ['nullable', 'string', 'in:final,mayorista,empresa'],
            'city' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'La razón social o nombre del cliente es obligatorio.',
            'email.email' => 'El correo electrónico debe ser una dirección válida.',
            'client_type.in' => 'El tipo de cliente debe ser final, mayorista o empresa.',
        ]);

        $client = $updateClient->execute($id, $validated);

        return response()->json([
            'message' => 'Cliente actualizado correctamente.',
            'data' => $client->toArray(),
        ]);
    }

    public function toggleStatus(int $id, Request $request, ToggleClientStatusUseCase $toggleStatus): JsonResponse
    {
        $user = $request->user();
        if ($user && ! in_array($user->role, ['dueno', 'administrador'])) {
            return response()->json([
                'message' => 'No autorizado para cambiar el estado del cliente.',
            ], 403);
        }

        $client = $toggleStatus->execute($id);

        return response()->json([
            'message' => 'Estado del cliente actualizado correctamente.',
            'data' => $client->toArray(),
        ]);
    }

    public function sales(int $id, Request $request, ClientRepositoryInterface $repository): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 10);
        $page = (int) $request->query('page', 1);

        $result = $repository->getClientSales($id, $perPage, $page);

        return response()->json($result);
    }

    public function quotes(int $id, Request $request, ClientRepositoryInterface $repository): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 10);
        $page = (int) $request->query('page', 1);

        $result = $repository->getClientQuotes($id, $perPage, $page);

        return response()->json($result);
    }

    public function warranties(int $id, GetClientWarrantiesUseCase $getWarranties): JsonResponse
    {
        $warranties = $getWarranties->execute($id);

        return response()->json([
            'data' => $warranties,
        ]);
    }

    public function export(Request $request, ExportClientsToCsvUseCase $exportClients): Response|JsonResponse
    {
        $user = $request->user();
        if ($user && ! in_array($user->role, ['dueno', 'administrador'])) {
            return response()->json([
                'message' => 'Acceso denegado. Solo el dueño o administrador puede exportar la cartera de clientes.',
            ], 403);
        }

        $csv = $exportClients->execute();
        $filename = 'clientes_servimatica_' . date('Y-m-d') . '.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
