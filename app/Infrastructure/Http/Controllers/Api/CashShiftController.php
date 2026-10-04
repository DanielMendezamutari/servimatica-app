<?php

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\CashShift\CloseCashShiftUseCase;
use App\Application\CashShift\GetCurrentCashShiftUseCase;
use App\Application\CashShift\ListCashShiftsUseCase;
use App\Application\CashShift\OpenCashShiftUseCase;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CashShiftController
{
    public function current(Request $request, GetCurrentCashShiftUseCase $getCurrent): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $shift = $getCurrent->execute($userId);

        if ($shift === null) {
            return response()->json([
                'is_open' => false,
                'data' => null,
            ]);
        }

        return response()->json([
            'is_open' => true,
            'data' => $shift->toArray(),
        ]);
    }

    public function open(Request $request, OpenCashShiftUseCase $openShift): JsonResponse
    {
        $validated = $request->validate([
            'opening_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'opening_amount.required' => 'El monto inicial de caja es obligatorio.',
            'opening_amount.numeric' => 'El monto inicial debe ser un número válido.',
            'opening_amount.min' => 'El monto inicial no puede ser negativo.',
        ]);

        $userId = (int) $request->user()->id;

        try {
            $shift = $openShift->execute(
                userId: $userId,
                openingAmount: (float) $validated['opening_amount'],
                notes: $validated['notes'] ?? null
            );

            return response()->json([
                'message' => 'Turno de caja abierto correctamente.',
                'data' => $shift->toArray(),
            ], 201);
        } catch (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function close(Request $request, CloseCashShiftUseCase $closeShift): JsonResponse
    {
        $validated = $request->validate([
            'closing_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'closing_amount.required' => 'El monto de arqueo físico es obligatorio.',
            'closing_amount.numeric' => 'El monto de cierre debe ser un número válido.',
            'closing_amount.min' => 'El monto de cierre no puede ser negativo.',
        ]);

        $userId = (int) $request->user()->id;

        try {
            $shift = $closeShift->execute(
                userId: $userId,
                closingAmount: (float) $validated['closing_amount'],
                notes: $validated['notes'] ?? null
            );

            return response()->json([
                'message' => 'Turno de caja cerrado y arqueo registrado correctamente.',
                'data' => $shift->toArray(),
            ]);
        } catch (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function index(Request $request, ListCashShiftsUseCase $listShifts): JsonResponse
    {
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 15);

        // Vendedor only sees his own shifts unless role is dueno
        $userId = null;
        if ($request->user()->role === 'vendedor') {
            $userId = (int) $request->user()->id;
        } elseif ($request->filled('user_id')) {
            $userId = (int) $request->query('user_id');
        }

        $result = $listShifts->execute($page, $perPage, $userId);

        return response()->json($result);
    }

    public function receipt(int $id, \App\Domain\CashShift\CashShiftRepositoryInterface $repository, \App\Domain\Company\CompanySettingRepositoryInterface $companyRepo)
    {
        $shift = $repository->findById($id);
        if ($shift === null) {
            return response()->json(['message' => 'Turno de caja no encontrado.'], 404);
        }

        $company = $companyRepo->get();

        return response()->view('cash_shifts.receipt', [
            'shift' => $shift,
            'company' => $company,
        ]);
    }
}
