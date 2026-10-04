<?php

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\Quote\CreateQuoteUseCase;
use App\Application\Quote\GetQuoteUseCase;
use App\Application\Quote\ListQuotesUseCase;
use App\Application\Quote\WhatsAppQuoteService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class QuoteController
{
    public function index(Request $request, ListQuotesUseCase $listQuotes): JsonResponse
    {
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 15);
        $search = $request->query('search');

        // Vendedor only sees his own quotes by default unless owner
        $sellerId = null;
        if ($request->user()->role === 'vendedor') {
            $sellerId = (int) $request->user()->id;
        } elseif ($request->filled('seller_id')) {
            $sellerId = (int) $request->query('seller_id');
        }

        $result = $listQuotes->execute($page, $perPage, $search, $sellerId);

        return response()->json($result);
    }

    public function show(int $id, GetQuoteUseCase $getQuote, WhatsAppQuoteService $waService): JsonResponse
    {
        $quote = $getQuote->execute($id);

        if ($quote === null) {
            return response()->json(['message' => 'Proforma no encontrada.'], 404);
        }

        $data = $quote->toArray();
        $data['whatsapp_link'] = $waService->generateWhatsAppLink($quote);

        return response()->json([
            'data' => $data,
        ]);
    }

    public function store(Request $request, CreateQuoteUseCase $createQuote, WhatsAppQuoteService $waService): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'client_name' => ['required', 'string', 'max:150'],
            'client_phone' => ['nullable', 'string', 'max:30'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'valid_days' => ['nullable', 'integer', 'min:1', 'max:90'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.product_name' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ], [
            'client_name.required' => 'El nombre del cliente es obligatorio.',
            'items.required' => 'Debe agregar al menos un producto a la proforma.',
            'items.min' => 'Debe agregar al menos un producto a la proforma.',
            'items.*.quantity.min' => 'La cantidad debe ser al menos 1.',
        ]);

        $sellerId = (int) $request->user()->id;

        try {
            $quote = $createQuote->execute($validated, $sellerId);

            $data = $quote->toArray();
            $data['whatsapp_link'] = $waService->generateWhatsAppLink($quote);

            return response()->json([
                'message' => 'Proforma generada correctamente.',
                'data' => $data,
            ], 201);
        } catch (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function whatsappLink(int $id, Request $request, GetQuoteUseCase $getQuote, WhatsAppQuoteService $waService): JsonResponse
    {
        $quote = $getQuote->execute($id);

        if ($quote === null) {
            return response()->json(['message' => 'Proforma no encontrada.'], 404);
        }

        $phone = $request->query('phone');
        $link = $waService->generateWhatsAppLink($quote, $phone);
        $messageText = $waService->formatMessage($quote);

        return response()->json([
            'whatsapp_link' => $link,
            'message_text' => $messageText,
            'formatted_message' => $messageText,
            'phone_used' => $phone ?: $quote->clientPhone,
        ]);
    }

    public function printQuote(int $id, GetQuoteUseCase $getQuote, \App\Domain\Company\CompanySettingRepositoryInterface $companyRepo): Response|JsonResponse
    {
        $quote = $getQuote->execute($id);

        if ($quote === null) {
            return response()->json(['message' => 'Proforma no encontrada.'], 404);
        }

        $company = $companyRepo->get();
        $paymentMethods = \App\Infrastructure\Persistence\Eloquent\PaymentMethodModel::where('is_active', true)
            ->whereIn('applies_to', ['sales', 'both'])
            ->orderBy('sort_order')
            ->get();

        $productIds = array_map(fn($item) => $item->productId, $quote->items);
        $warranties = !empty($productIds)
            ? \App\Infrastructure\Persistence\Eloquent\ProductModel::whereIn('id', $productIds)->pluck('warranty_days', 'id')->toArray()
            : [];

        return response()->view('quotes.print', [
            'quote' => $quote,
            'company' => $company,
            'paymentMethods' => $paymentMethods,
            'warranties' => $warranties,
        ]);
    }
}
