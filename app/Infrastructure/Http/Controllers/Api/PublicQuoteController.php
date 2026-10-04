<?php

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\Quote\GetQuoteByPublicTokenUseCase;
use App\Domain\Company\CompanySettingRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\PaymentMethodModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class PublicQuoteController
{
    public function show(string $token, GetQuoteByPublicTokenUseCase $getQuote, CompanySettingRepositoryInterface $companyRepo): JsonResponse
    {
        $quote = $getQuote->execute($token);

        if ($quote === null) {
            return response()->json(['message' => 'Proforma no encontrada o enlace caducado.'], 404);
        }

        $company = $companyRepo->get();
        $paymentMethods = PaymentMethodModel::where('is_active', true)
            ->whereIn('applies_to', ['sales', 'both'])
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'data' => [
                'quote' => $quote->toArray(),
                'company' => $company->toArray(),
                'payment_methods' => $paymentMethods,
                'print_url' => url("api/quotes/public/{$token}/print"),
            ],
        ]);
    }

    public function print(string $token, GetQuoteByPublicTokenUseCase $getQuote, CompanySettingRepositoryInterface $companyRepo): Response|JsonResponse
    {
        $quote = $getQuote->execute($token);

        if ($quote === null) {
            return response()->json(['message' => 'Proforma no encontrada o enlace caducado.'], 404);
        }

        $company = $companyRepo->get();
        $paymentMethods = PaymentMethodModel::where('is_active', true)
            ->whereIn('applies_to', ['sales', 'both'])
            ->orderBy('sort_order')
            ->get();

        $productIds = array_map(fn($item) => $item->productId, $quote->items);
        $warranties = !empty($productIds)
            ? ProductModel::whereIn('id', $productIds)->pluck('warranty_days', 'id')->toArray()
            : [];

        return response()->view('quotes.print', [
            'quote' => $quote,
            'company' => $company,
            'paymentMethods' => $paymentMethods,
            'warranties' => $warranties,
            'publicToken' => $token,
        ]);
    }
}
