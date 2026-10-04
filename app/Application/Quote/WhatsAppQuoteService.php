<?php

namespace App\Application\Quote;

use App\Domain\Quote\Quote;
use App\Infrastructure\Persistence\Eloquent\CompanySettingModel;
use App\Infrastructure\Persistence\Eloquent\PaymentMethodModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;

final class WhatsAppQuoteService
{
    public function formatMessage(Quote $quote): string
    {
        // 1. Obtener datos dinámicos de la empresa
        $company = CompanySettingModel::first();
        $tradeName = $company?->trade_name ? trim($company->trade_name) : 'SERVIMÁTICA COMPUTACIÓN';
        $slogan = $company?->slogan ? trim($company->slogan) : 'Tecnología y Soluciones Digitales';
        $city = $company?->city ? trim(explode(',', $company->city)[0]) : 'Trinidad';
        $address = $company?->address ? trim($company->address) : '';

        // 2. Obtener garantías de los productos
        $productIds = array_map(fn($item) => $item->productId, $quote->items);
        $warranties = !empty($productIds)
            ? ProductModel::whereIn('id', $productIds)->pluck('warranty_days', 'id')->toArray()
            : [];

        // 3. Obtener métodos de pago activos
        $paymentMethods = PaymentMethodModel::where('is_active', true)
            ->whereIn('applies_to', ['sales', 'both'])
            ->orderBy('sort_order')
            ->get();

        $hasQr = $paymentMethods->contains(fn($p) => $p->type === 'qr' || stripos($p->name, 'qr') !== false);
        $bankNames = $paymentMethods->filter(fn($p) => !empty($p->bank_name))->pluck('bank_name')->unique()->values()->all();

        // 4. Construcción del mensaje comercial conciso y de alto impacto (Menos es más)
        $lines = [];
        $header = "*" . mb_strtoupper($tradeName, 'UTF-8') . "*";
        if (!empty($slogan)) {
            $header .= " — _" . $slogan . "_";
        }
        $lines[] = $header;
        $lines[] = "";
        $lines[] = "¡Hola, *" . trim($quote->clientName) . "*! 👋 Qué gusto saludarle de parte de *" . $tradeName . "*. Le compartimos su cotización:";
        $lines[] = "";

        $proformaLine = "📋 *Proforma:* " . $quote->quoteNumber;
        if (!empty($quote->validUntil)) {
            $formattedDate = date('d/m/Y', strtotime($quote->validUntil));
            $proformaLine .= " | ⏳ *Válido hasta:* " . $formattedDate;
        }
        $lines[] = $proformaLine;
        $lines[] = "";

        $lines[] = "💻 *Equipos Cotizados:*";
        foreach ($quote->items as $item) {
            $days = $warranties[$item->productId] ?? 0;
            $warrantyLabel = $this->formatWarrantyText((int) $days);

            $lines[] = sprintf(
                "• *%dx %s* — *Bs. %s*",
                $item->quantity,
                $item->productName,
                number_format($item->subtotal, 2, '.', ',')
            );
            $lines[] = "  └ 🛡️ *Garantía:* " . $warrantyLabel;
        }

        $lines[] = "";
        if ($quote->discountAmount > 0) {
            $lines[] = "Subtotal: Bs. " . number_format($quote->subtotal, 2, '.', ',') . " (Descuento: -Bs. " . number_format($quote->discountAmount, 2, '.', ',') . ")";
        }
        $lines[] = "💰 *TOTAL A PAGAR: Bs. " . number_format($quote->totalAmount, 2, '.', ',') . "*";

        if (!empty($quote->notes)) {
            $lines[] = "📝 _" . $quote->notes . "_";
        }

        $lines[] = "";
        $lines[] = "🎁 *Cortesía:* Configuración inicial y programas esenciales sin costo.";

        // Formas de pago compactas en una sola línea
        $paymentParts = [];
        if ($hasQr) {
            $paymentParts[] = "Pago rápido con QR (Simple)";
        }
        if (!empty($bankNames)) {
            $paymentParts[] = "Transferencia (" . implode(', ', $bankNames) . ")";
        } else {
            $paymentParts[] = "Transferencia bancaria";
        }
        $paymentParts[] = "Efectivo";

        $lines[] = "💳 *Formas de pago:* " . implode(' | ', $paymentParts) . ".";

        // URL pública de impresión / descarga PDF protegida por token criptográfico
        $token = !empty($quote->publicToken) ? $quote->publicToken : $quote->id;
        $printUrl = url("/api/quotes/public/{$token}/print");
        $lines[] = "";
        $lines[] = "📄 *Ver Proforma en PDF:*";
        $lines[] = "👉 " . $printUrl;


        $lines[] = "";
        $lines[] = "⚡ ¿Desea que se lo reservemos para entrega hoy mismo? Solo responda a este mensaje. 😊";
        $lines[] = "📍 " . $city . ($address ? " — " . $address : "");

        return implode("\n", $lines);
    }

    public function generateWhatsAppLink(Quote $quote, ?string $phoneOverride = null): string
    {
        $rawPhone = $phoneOverride ?: $quote->clientPhone;
        $digits = preg_replace('/\D+/', '', (string) $rawPhone);

        // Si es número boliviano de 8 dígitos (ej. 77012345 o 68912345), anteponer 591
        if (strlen($digits) === 8) {
            $digits = '591' . $digits;
        }

        $text = $this->formatMessage($quote);
        $encodedText = rawurlencode($text);

        if (!empty($digits)) {
            return "https://wa.me/{$digits}?text={$encodedText}";
        }

        return "https://wa.me/?text={$encodedText}";
    }

    private function formatWarrantyText(int $days): string
    {
        if ($days <= 0) {
            return 'Garantía técnica de tienda';
        }
        if ($days % 365 === 0) {
            $years = $days / 365;
            return $years === 1 ? '12 meses (1 año) de Garantía Oficial' : ($years * 12) . " meses de Garantía Oficial";
        }
        if ($days % 30 === 0) {
            $months = $days / 30;
            return "{$months} meses de Garantía Oficial";
        }
        return "{$days} días de Garantía Oficial";
    }
}
