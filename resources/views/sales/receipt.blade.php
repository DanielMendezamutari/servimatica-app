<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket {{ $sale->invoiceNumber }} — {{ $company->tradeName }}</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 3mm;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            width: 72mm;
            margin: 0 auto;
            color: #000;
            font-size: 11px;
            line-height: 1.3;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .divider {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }
        .header-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .info-table, .items-table, .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        .items-table th {
            text-align: left;
            border-bottom: 1px dashed #000;
            padding: 3px 0;
        }
        .items-table td {
            padding: 2px 0;
            vertical-align: top;
        }
        .totals-table td {
            padding: 2px 0;
        }
        .total-highlight {
            font-size: 13px;
            font-weight: bold;
        }
        .footer {
            margin-top: 10px;
            font-size: 10px;
        }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 10px; text-align: center;">
        <button onclick="window.print()" style="padding: 6px 12px; font-weight: bold; cursor: pointer; background: #000; color: #fff; border: none; border-radius: 4px;">
            🖨️ Imprimir Ticket (80mm)
        </button>
    </div>

    <div class="text-center">
        @if(!empty($company->logoUrl))
            <img src="{{ $company->logoUrl }}" alt="{{ $company->tradeName }}" style="max-height: 40px; margin-bottom: 4px;" onerror="this.style.display='none'">
        @endif
        <div class="header-title">{{ mb_strtoupper($company->tradeName) }}</div>
        @if($company->slogan)
            <div>{{ $company->slogan }}</div>
        @endif
        <div>{{ $company->city }}</div>
        @if($company->address)
            <div>{{ $company->address }}</div>
        @endif
        @if($company->taxId)
            <div class="font-bold">NIT: {{ $company->taxId }}</div>
        @endif
        @if($company->mobile || $company->phone)
            <div>Telf: {{ implode(' - ', array_filter([$company->phone, $company->mobile])) }}</div>
        @endif
        <div class="divider"></div>
        <div class="font-bold">COMPROBANTE DE VENTA</div>
        <div class="font-bold">{{ $sale->invoiceNumber }}</div>
        <div>Fecha: {{ $sale->createdAt ? date('d/m/Y H:i', strtotime($sale->createdAt)) : date('d/m/Y H:i') }}</div>
    </div>

    <div class="divider"></div>

    <table class="info-table">
        <tr>
            <td style="width: 35%;">Cajero:</td>
            <td class="font-bold">{{ $sale->sellerName ?? 'Vendedor' }}</td>
        </tr>
        <tr>
            <td>Cliente:</td>
            <td class="font-bold">{{ $sale->clientName }}</td>
        </tr>
        @if($sale->clientNitCi)
        <tr>
            <td>NIT/CI:</td>
            <td>{{ $sale->clientNitCi }}</td>
        </tr>
        @endif
        <tr>
            <td>Pago:</td>
            <td class="font-bold">{{ $sale->paymentMethodName ?? strtoupper($sale->paymentMethod) }}</td>
        </tr>
        @if($sale->referenceNumber)
        <tr>
            <td>N° Ref/Comp:</td>
            <td class="font-bold">{{ $sale->referenceNumber }}</td>
        </tr>
        @endif
    </table>

    <div class="divider"></div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 15%;">Cant</th>
                <th style="width: 55%;">Producto</th>
                <th style="width: 30%;" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $it)
            <tr>
                <td class="font-bold">{{ $it->quantity }}x</td>
                <td>
                    {{ $it->productName }}
                    <div style="font-size: 9px; color: #555;">@ Bs. {{ number_format($it->unitPrice, 2, '.', '') }}</div>
                    @if(!empty($it->serialNumber))
                        <div style="font-size: 8.5px; color: #222; font-weight: bold;">S/N: {{ $it->serialNumber }}</div>
                    @endif
                    @if(($it->warrantyHardwareDays ?? $it->warrantyDays) > 0)
                        <div style="font-size: 8.5px; color: #333;">🛡️ G. Hardware: {{ $it->warrantyHardwareDays ?? $it->warrantyDays }} días (Vence: {{ ($it->warrantyHardwareExpiresAt ?? $it->warrantyExpiresAt) ? date('d/m/Y', strtotime($it->warrantyHardwareExpiresAt ?? $it->warrantyExpiresAt)) : 'N/A' }})</div>
                    @endif
                    @if(($it->warrantySoftwareDays ?? 0) > 0)
                        <div style="font-size: 8.5px; color: #333;">💻 G. Software: {{ $it->warrantySoftwareDays }} días (Vence: {{ $it->warrantySoftwareExpiresAt ? date('d/m/Y', strtotime($it->warrantySoftwareExpiresAt)) : 'N/A' }})</div>
                    @endif
                </td>
                <td class="text-right font-bold">Bs. {{ number_format($it->subtotal, 2, '.', '') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="divider"></div>

    <table class="totals-table">
        <tr>
            <td>Subtotal:</td>
            <td class="text-right">Bs. {{ number_format($sale->subtotal, 2, '.', '') }}</td>
        </tr>
        @if($sale->discountAmount > 0)
        <tr>
            <td>Descuento:</td>
            <td class="text-right">- Bs. {{ number_format($sale->discountAmount, 2, '.', '') }}</td>
        </tr>
        @endif
        <tr class="total-highlight">
            <td>TOTAL:</td>
            <td class="text-right">Bs. {{ number_format($sale->totalAmount, 2, '.', '') }}</td>
        </tr>
        @if($sale->paymentMethod === 'efectivo')
        <tr>
            <td>Efectivo Entregado:</td>
            <td class="text-right">Bs. {{ number_format($sale->cashTendered ?? $sale->totalAmount, 2, '.', '') }}</td>
        </tr>
        <tr class="font-bold">
            <td>Cambio / Vuelto:</td>
            <td class="text-right">Bs. {{ number_format($sale->changeDue ?? 0.00, 2, '.', '') }}</td>
        </tr>
        @endif
    </table>

    <div class="divider"></div>

    <div class="footer text-center">
        <div>¡Muchas gracias por su preferencia!</div>
        <div>{{ $company->receiptFooterMessage ?? 'Garantía legal con su comprobante' }}</div>
        @if(!empty($company->warrantyTerms))
        <div class="divider"></div>
        <div style="font-size: 8px; text-align: justify; line-height: 1.2; margin-top: 4px; padding: 0 2px;">
            <div class="font-bold text-center" style="font-size: 8.5px; margin-bottom: 2px;">POLÍTICAS DE GARANTÍA</div>
            {!! nl2br(e($company->warrantyTerms)) !!}
        </div>
        @endif
        <div style="margin-top: 5px; font-size: 8px;">{{ $company->tradeName }} — Servimática App</div>
    </div>

    @if(request()->query('autoprint'))
    <script>
        window.addEventListener('load', function() {
            setTimeout(function() { window.print(); }, 300);
        });
    </script>
    @endif
</body>
</html>
