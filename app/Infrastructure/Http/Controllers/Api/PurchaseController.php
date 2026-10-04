<?php

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\Purchase\CancelPurchaseUseCase;
use App\Application\Purchase\GetPurchaseUseCase;
use App\Application\Purchase\ListPurchasesUseCase;
use App\Application\Purchase\ProcessPurchaseUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class PurchaseController
{
    public function index(Request $request, ListPurchasesUseCase $listPurchases): JsonResponse
    {
        $page = (int)$request->query('page', 1);
        $perPage = (int)$request->query('per_page', 15);
        $supplierId = $request->query('supplier_id') ? (int)$request->query('supplier_id') : null;
        $search = $request->query('search') ?? $request->query('query');
        $status = $request->query('status');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $result = $listPurchases->execute(
            page: $page,
            perPage: $perPage,
            supplierId: $supplierId,
            search: $search,
            status: $status,
            startDate: $startDate,
            endDate: $endDate
        );

        return response()->json($result);
    }

    public function show(int $id, GetPurchaseUseCase $getPurchase): JsonResponse
    {
        $purchase = $getPurchase->execute($id);

        return response()->json([
            'data' => $purchase->toArray(),
        ]);
    }

    public function store(Request $request, ProcessPurchaseUseCase $processPurchase): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'invoice_number' => ['required', 'string', 'max:50'],
            'purchase_date' => ['required', 'date'],
            'payment_condition' => ['required', 'string', 'in:contado,credito'],
            'payment_method' => ['nullable', 'required_without:payment_method_id', 'string', 'in:efectivo,transferencia,otro'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'due_date' => ['nullable', 'date', 'required_if:payment_condition,credito'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.new_sale_price' => ['nullable', 'numeric', 'min:0'],
        ], [
            'supplier_id.required' => 'Debe seleccionar un proveedor mayorista.',
            'supplier_id.exists' => 'El proveedor seleccionado no existe en el catálogo.',
            'invoice_number.required' => 'El número de factura o nota de compra es obligatorio.',
            'purchase_date.required' => 'La fecha de compra es obligatoria.',
            'due_date.required_if' => 'La fecha de vencimiento es obligatoria para compras a crédito.',
            'items.required' => 'Debe incluir al menos un producto en la compra.',
            'items.min' => 'Debe incluir al menos un producto en la compra.',
            'items.*.product_id.required' => 'El producto es requerido.',
            'items.*.quantity.min' => 'La cantidad ingresada debe ser de al menos 1 unidad.',
            'items.*.unit_cost.min' => 'El costo unitario debe ser mayor o igual a cero.',
        ]);

        $validated['user_id'] = $request->user()->id;

        $purchase = $processPurchase->execute($validated);

        return response()->json([
            'message' => 'Compra recepcionada e inventario actualizado exitosamente.',
            'data' => $purchase->toArray(),
        ], 201);
    }

    public function cancel(int $id, Request $request, CancelPurchaseUseCase $cancelPurchase): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ], [
            'reason.required' => 'El motivo de anulación es obligatorio.',
            'reason.min' => 'El motivo debe tener al menos 5 caracteres.',
            'reason.max' => 'El motivo no puede superar 255 caracteres.',
        ]);

        $cancelledBy = $request->user()->id;
        $purchase = $cancelPurchase->execute($id, $cancelledBy, $validated['reason']);

        return response()->json([
            'message' => 'Compra anulada y stock descontado del inventario exitosamente.',
            'data' => $purchase->toArray(),
        ]);
    }

    public function receipt(int $id, GetPurchaseUseCase $getPurchase, \App\Domain\Company\CompanySettingRepositoryInterface $companyRepo): Response
    {
        $purchase = $getPurchase->execute($id);
        $company = $companyRepo->get();

        return response()->view('purchases.receipt', [
            'purchase' => $purchase,
            'company' => $company,
        ]);
    }
}
