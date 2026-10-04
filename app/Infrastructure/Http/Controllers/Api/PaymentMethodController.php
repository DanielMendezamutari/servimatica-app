<?php

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\PaymentMethod\CreatePaymentMethodUseCase;
use App\Application\PaymentMethod\GetPaymentMethodOptionsUseCase;
use App\Application\PaymentMethod\ListPaymentMethodsUseCase;
use App\Application\PaymentMethod\TogglePaymentMethodStatusUseCase;
use App\Application\PaymentMethod\UpdatePaymentMethodUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PaymentMethodController
{
    public function options(Request $request, GetPaymentMethodOptionsUseCase $useCase): JsonResponse
    {
        $context = $request->query('context'); // 'sales', 'purchases' or null
        $methods = $useCase->execute($context);

        return response()->json(['data' => $methods]);
    }

    public function index(ListPaymentMethodsUseCase $useCase): JsonResponse
    {
        $methods = $useCase->execute();

        return response()->json(['data' => $methods]);
    }

    public function store(Request $request, CreatePaymentMethodUseCase $useCase): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|string|in:cash,qr,bank_transfer,card,other',
            'bank_name' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
            'account_holder' => 'nullable|string|max:150',
            'requires_reference' => 'nullable|boolean',
            'applies_to' => 'nullable|string|in:sales,purchases,both',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'qr_image' => 'nullable|image|max:2048',
        ]);

        $qrImage = $request->file('qr_image');
        $method = $useCase->execute($validated, $qrImage);

        return response()->json(['data' => $method], 201);
    }

    public function update(int $id, Request $request, UpdatePaymentMethodUseCase $useCase): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'type' => 'sometimes|required|string|in:cash,qr,bank_transfer,card,other',
            'bank_name' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
            'account_holder' => 'nullable|string|max:150',
            'requires_reference' => 'nullable|boolean',
            'applies_to' => 'nullable|string|in:sales,purchases,both',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'qr_image' => 'nullable|image|max:2048',
        ]);

        $qrImage = $request->file('qr_image');
        $method = $useCase->execute($id, $validated, $qrImage);

        return response()->json(['data' => $method]);
    }

    public function toggleStatus(int $id, TogglePaymentMethodStatusUseCase $useCase): JsonResponse
    {
        $method = $useCase->execute($id);

        return response()->json(['data' => $method]);
    }
}
