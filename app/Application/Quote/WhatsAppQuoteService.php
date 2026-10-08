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

        // 2. Obtener garantías de los productos (soporte autónomo Hardware y Software)
        $productIds = array_map(fn($item) => $item->productId, $quote->items);
        $products = !empty($productIds)
            ? ProductModel::whereIn('id', $productIds)
                ->get(['id', 'warranty_days', 'warranty_hardware_days', 'warranty_software_days'])
                ->keyBy('id')
            : collect();

        // 3. Obtener métodos de pago activos
        $paymentMethods = PaymentMethodModel::where('is_active', true)
            ->whereIn('applies_to', ['sales', 'both'])
            ->orderBy('sort_order')
            ->get();

        $hasQr = $paymentMethods->contains(fn($p) => $p->type === 'qr' || stripos($p->name, 'qr') !== false);
        $bankNames = $paymentMethods->filter(fn($p) => !empty($p->bank_name))->pluck('bank_name')->unique()->values()->all();

        // 4. Construcción del mensaje comercial sobrio y profesional (Menos es más, cero emojis corruptibles)
        $lines = [];
        $header = "*" . mb_strtoupper($tradeName, 'UTF-8') . "*";
        if (!empty($slogan)) {
            $header .= " — _" . $slogan . "_";
        }
        $lines[] = $header;
        $lines[] = "";
        $lines[] = "¡Hola, *" . trim($quote->clientName) . "*! Qué gusto saludarle de parte de *" . $tradeName . "*. Le compartimos su cotización:";
        $lines[] = "";

        $proformaLine = "*Proforma:* " . $quote->quoteNumber;
        if (!empty($quote->validUntil)) {
            $formattedDate = date('d/m/Y', strtotime($quote->validUntil));
            $proformaLine .= " | *Válido hasta:* " . $formattedDate;
        }
        $lines[] = $proformaLine;
        $lines[] = "";

        $lines[] = "*Equipos Cotizados:*";
        foreach ($quote->items as $item) {
            $prod = $products->get($item->productId);
            $hwDays = (int) ($prod?->warranty_hardware_days ?: ($prod?->warranty_days ?: 0));
            $swDays = (int) ($prod?->warranty_software_days ?: 0);
            $warrantyLabel = $this->formatAutonomousWarrantyText($hwDays, $swDays);

            $lines[] = sprintf(
                "• *%dx %s* — *Bs. %s*",
                $item->quantity,
                $item->productName,
                number_format($item->subtotal, 2, '.', ',')
            );
            $lines[] = "  - *Garantía:* " . $warrantyLabel;
        }

        $lines[] = "";
        if ($quote->discountAmount > 0) {
            $lines[] = "Subtotal: Bs. " . number_format($quote->subtotal, 2, '.', ',') . " (Descuento: -Bs. " . number_format($quote->discountAmount, 2, '.', ',') . ")";
        }
        $lines[] = "*TOTAL A PAGAR: Bs. " . number_format($quote->totalAmount, 2, '.', ',') . "*";

        if (!empty($quote->notes)) {
            $lines[] = "";
            $lines[] = "_" . trim($quote->notes) . "_";
        }

        $lines[] = "";
        $lines[] = "• *Cortesía:* Configuración inicial y programas esenciales sin costo.";

        // Formas de pago compactas y ejecutivas
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

        $lines[] = "• *Formas de pago:* " . implode(' | ', $paymentParts) . ".";

        // URL pública de impresión / descarga PDF protegida por token criptográfico
        $token = !empty($quote->publicToken) ? $quote->publicToken : $quote->id;
        $printUrl = url("/api/quotes/public/{$token}/print");
        $lines[] = "";
        $lines[] = "*Ver Proforma en PDF:*";
        $lines[] = $printUrl;

        $lines[] = "";
        $lines[] = "¿Desea que se lo reservemos para entrega hoy mismo? Solo responda a este mensaje.";
        if (!empty($city) || !empty($address)) {
            $lines[] = $city . ($address ? " — " . $address : "");
        }

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

    private function formatAutonomousWarrantyText(int $hwDays, int $swDays): string
    {
        if ($hwDays <= 0 && $swDays <= 0) {
            return 'Garantía técnica de tienda';
        }

        if ($hwDays > 0 && $swDays > 0) {
            return 'HW: ' . $this->formatPeriodText($hwDays) . ' | Software: ' . $this->formatPeriodText($swDays);
        }

        if ($hwDays > 0) {
            return $this->formatPeriodText($hwDays) . ' de Garantía Oficial';
        }

        return 'Software: ' . $this->formatPeriodText($swDays);
    }

    private function formatPeriodText(int $days): string
    {
        if ($days % 365 === 0) {
            $years = $days / 365;
            return $years === 1 ? '12 meses (1 año)' : ($years * 12) . " meses ({$years} años)";
        }
        if ($days % 30 === 0) {
            $months = $days / 30;
            return "{$months} meses";
        }
        return "{$days} días";
    }
}
