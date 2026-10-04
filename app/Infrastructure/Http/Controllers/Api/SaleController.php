<?php

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\Sale\CancelSaleUseCase;
use App\Application\Sale\ConvertQuoteToSaleUseCase;
use App\Application\Sale\GenerateCommissionsReportUseCase;
use App\Application\Sale\ProcessSaleUseCase;
use App\Domain\Sale\SaleRepositoryInterface;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class SaleController
{
    public function index(Request $request, SaleRepositoryInterface $repository): JsonResponse
    {
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 15);
        $search = $request->query('search');
        $status = $request->query('status');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        // Sellers only see their own sales unless user is dueno/owner
        $sellerId = null;
        if ($request->user()->role === 'vendedor') {
            $sellerId = (int) $request->user()->id;
        } elseif ($request->filled('seller_id')) {
            $sellerId = (int) $request->query('seller_id');
        }

        $result = $repository->paginate($page, $perPage, $search, $sellerId, $status, $startDate, $endDate);

        return response()->json($result);
    }

    public function show(int $id, SaleRepositoryInterface $repository): JsonResponse
    {
        $sale = $repository->findById($id);

        if ($sale === null) {
            return response()->json(['message' => 'Venta no encontrada.'], 404);
        }

        return response()->json([
            'data' => $sale->toArray(),
        ]);
    }

    public function store(Request $request, ProcessSaleUseCase $processSale): JsonResponse
    {
        $validated = $request->validate([
            'cash_shift_id' => ['required', 'integer'],
            'payment_method' => ['nullable', 'required_without:payment_method_id', 'string', 'in:efectivo,qr,transferencia'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'client_name' => ['nullable', 'string', 'max:150'],
            'client_nit_ci' => ['nullable', 'string', 'max:30'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'cash_tendered' => ['nullable', 'numeric', 'min:0'],
            'quote_id' => ['nullable', 'integer', 'exists:quotes,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.warranty_days' => ['nullable', 'integer', 'min:0'],
            'items.*.serial_number' => ['nullable', 'string', 'max:100'],
        ], [
            'cash_shift_id.required' => 'El turno de caja es obligatorio para procesar la venta.',
            'items.required' => 'Debe agregar al menos un producto al carrito.',
            'items.min' => 'Debe agregar al menos un producto al carrito.',
            'items.*.quantity.min' => 'La cantidad debe ser de al menos 1 unidad.',
        ]);

        $sellerId = (int) $request->user()->id;

        try {
            $sale = $processSale->execute($validated, $sellerId);

            return response()->json([
                'message' => 'Venta registrada con éxito.',
                'data' => $sale->toArray(),
            ], 201);
        } catch (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function fromQuote(Request $request, ConvertQuoteToSaleUseCase $convertUseCase): JsonResponse
    {
        $validated = $request->validate([
            'quote_id' => ['required', 'integer', 'exists:quotes,id'],
            'cash_shift_id' => ['required', 'integer'],
            'payment_method' => ['required', 'string', 'in:efectivo,qr,transferencia'],
            'cash_tendered' => ['nullable', 'numeric', 'min:0'],
        ]);

        $sellerId = (int) $request->user()->id;

        try {
            $sale = $convertUseCase->execute(
                quoteId: (int) $validated['quote_id'],
                cashShiftId: (int) $validated['cash_shift_id'],
                paymentMethod: $validated['payment_method'],
                cashTendered: isset($validated['cash_tendered']) ? (float) $validated['cash_tendered'] : null,
                sellerId: $sellerId
            );

            return response()->json([
                'message' => 'Cotización convertida a venta exitosamente.',
                'data' => $sale->toArray(),
            ], 201);
        } catch (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function receipt(int $id, SaleRepositoryInterface $repository, \App\Domain\Company\CompanySettingRepositoryInterface $companyRepo): Response|JsonResponse
    {
        $sale = $repository->findById($id);

        if ($sale === null) {
            return response()->json(['message' => 'Venta no encontrada.'], 404);
        }

        $company = $companyRepo->get();

        return response()->view('sales.receipt', [
            'sale' => $sale,
            'company' => $company,
        ]);
    }

    public function cancel(int $id, Request $request, CancelSaleUseCase $cancelUseCase): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ], [
            'reason.required' => 'El motivo de anulación es obligatorio.',
            'reason.max' => 'El motivo no puede superar 255 caracteres.',
        ]);

        $ownerUserId = (int) $request->user()->id;

        try {
            $sale = $cancelUseCase->execute($id, $ownerUserId, $validated['reason']);

            return response()->json([
                'message' => 'Venta anulada y existencias de inventario repuestas correctamente.',
                'data' => $sale->toArray(),
            ]);
        } catch (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function commissionsReport(Request $request, GenerateCommissionsReportUseCase $reportUseCase): JsonResponse
    {
        $sellerId = $request->filled('seller_id') ? (int) $request->query('seller_id') : null;
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $report = $reportUseCase->execute($sellerId, $startDate, $endDate);

        return response()->json([
            'data' => $report,
        ]);
    }
}
