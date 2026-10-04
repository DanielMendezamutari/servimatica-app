<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nota de Recepción {{ $purchase->purchaseNumber }} — {{ $company->tradeName }}</title>
    <style>
        @page {
            size: letter;
            margin: 12mm 15mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            margin: 0;
            padding: 0;
            font-size: 12px;
            line-height: 1.4;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f766e;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }
        .company-name {
            font-size: 20px;
            font-weight: 800;
            color: #0f766e;
            letter-spacing: 0.5px;
        }
        .company-sub {
            font-size: 11px;
            color: #64748b;
        }
        .doc-badge {
            text-align: right;
            vertical-align: top;
        }
        .doc-title {
            font-size: 14px;
            font-weight: bold;
            color: #334155;
            text-transform: uppercase;
        }
        .doc-number {
            font-size: 18px;
            font-weight: 900;
            color: #0f766e;
        }
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 4px;
        }
        .status-received {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }
        .status-cancelled {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        .info-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 16px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            vertical-align: top;
            padding: 3px 0;
        }
        .info-label {
            font-size: 10px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .info-value {
            font-size: 12px;
            font-weight: 600;
            color: #0f172a;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .items-table th {
            background-color: #f1f5f9;
            color: #475569;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: 700;
            padding: 7px 8px;
            border-bottom: 2px solid #cbd5e1;
            text-align: left;
        }
        .items-table td {
            padding: 7px 8px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11px;
        }
        .items-table tr:last-child td {
            border-bottom: 2px solid #cbd5e1;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals-table {
            width: 40%;
            margin-left: auto;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .totals-table td {
            padding: 4px 6px;
        }
        .total-highlight {
            font-size: 14px;
            font-weight: 800;
            color: #0f766e;
            border-top: 1px solid #94a3b8;
        }
        .signatures-table {
            width: 100%;
            margin-top: 40px;
            border-collapse: collapse;
        }
        .signatures-table td {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 40px;
        }
        .signature-line {
            border-top: 1px solid #94a3b8;
            margin-bottom: 6px;
        }
        .signature-title {
            font-size: 11px;
            font-weight: bold;
            color: #334155;
        }
        .signature-subtitle {
            font-size: 9px;
            color: #64748b;
        }
        .cancelled-banner {
            background-color: #fef2f2;
            border: 1px dashed #ef4444;
            color: #991b1b;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 11px;
        }
        .print-btn-bar {
            background-color: #0f766e;
            color: #fff;
            padding: 10px 15px;
            text-align: center;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .print-btn {
            background: #fff;
            color: #0f766e;
            border: none;
            padding: 6px 16px;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            font-size: 12px;
        }
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="print-btn-bar no-print">
        <span>Comprobante de Recepción de Inventario</span>
        <button class="print-btn" onclick="window.print()">🖨️ Imprimir / Guardar PDF</button>
    </div>

    @if($purchase->status === 'cancelled')
        <div class="cancelled-banner">
            <strong>⚠️ ESTA COMPRA FUE ANULADA:</strong><br>
            Motivo: {{ $purchase->cancellationReason }}<br>
            Anulado por: {{ $purchase->cancelledByName ?? 'Administración' }} el {{ $purchase->cancelledAt }}
        </div>
    @endif

    <table class="header-table">
        <tr>
            <td>
                @if(!empty($company->logoUrl))
                    <img src="{{ $company->logoUrl }}" alt="{{ $company->tradeName }}" style="max-height: 44px; margin-bottom: 4px;" onerror="this.style.display='none'">
                @endif
                <div class="company-name">{{ mb_strtoupper($company->tradeName) }}</div>
                <div class="company-sub">
                    {{ $company->slogan ?? 'Tecnología, Venta de Hardware e Insumos Informáticos' }}<br>
                    {{ $company->city }}@if($company->address) — {{ $company->address }}@endif
                    @if($company->mobile || $company->phone)
                        <br>Telf: {{ implode(' / ', array_filter([$company->phone, $company->mobile])) }}
                    @endif
                </div>
            </td>
            <td class="doc-badge">
                <div class="doc-title">Nota de Recepción de Mercadería</div>
                <div class="doc-number">{{ $purchase->purchaseNumber }}</div>
                <div>
                    @if($purchase->status === 'received')
                        <span class="status-badge status-received">✓ Recepcionado / Ingresado</span>
                    @else
                        <span class="status-badge status-cancelled">✗ Anulado</span>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="info-card">
        <table class="info-table">
            <tr>
                <td style="width: 50%;">
                    <div class="info-label">Proveedor / Mayorista</div>
                    <div class="info-value">{{ $purchase->supplierName }}</div>
                </td>
                <td style="width: 25%;">
                    <div class="info-label">Factura / Nota Proveedor</div>
                    <div class="info-value">{{ $purchase->invoiceNumber }}</div>
                </td>
                <td style="width: 25%;">
                    <div class="info-label">Fecha de Compra</div>
                    <div class="info-value">{{ $purchase->purchaseDate }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="info-label">Responsable de Recepción</div>
                    <div class="info-value">{{ $purchase->userName ?? 'Administración' }}</div>
                </td>
                <td>
                    <div class="info-label">Condición de Pago</div>
                    <div class="info-value" style="text-transform: capitalize;">
                        {{ $purchase->paymentCondition }}
                        @if($purchase->paymentCondition === 'credito' && $purchase->dueDate)
                            (Vence: {{ $purchase->dueDate }})
                        @endif
                    </div>
                </td>
                <td>
                    <div class="info-label">Método Liquidación</div>
                    <div class="info-value">
                        {{ $purchase->paymentMethodName ?? ucfirst($purchase->paymentMethod) }}
                        @if($purchase->referenceNumber)
                            <br><span style="color: #64748b; font-size: 10px; font-weight: normal;">Ref/Comp: {{ $purchase->referenceNumber }}</span>
                        @endif
                    </div>
                </td>
            </tr>
            @if($purchase->notes)
                <tr>
                    <td colspan="3">
                        <div class="info-label">Observaciones</div>
                        <div class="info-value" style="font-weight: normal; color: #475569;">{{ $purchase->notes }}</div>
                    </td>
                </tr>
            @endif
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 30px;" class="text-center">#</th>
                <th style="width: 110px;">SKU</th>
                <th>Descripción del Producto</th>
                <th style="width: 80px;" class="text-center">Cant. Ingresada</th>
                <th style="width: 100px;" class="text-right">Costo Unit. (Bs.)</th>
                <th style="width: 110px;" class="text-right">Subtotal (Bs.)</th>
            </tr>
        </thead>
        <tbody>
            @php $totalQuantity = 0; @endphp
            @foreach($purchase->items as $index => $item)
                @php $totalQuantity += $item->quantity; @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td style="font-family: monospace;">{{ $item->productSku ?: 'N/A' }}</td>
                    <td><strong>{{ $item->productName }}</strong></td>
                    <td class="text-center font-bold">{{ $item->quantity }}</td>
                    <td class="text-right">Bs. {{ number_format($item->unitCost, 2) }}</td>
                    <td class="text-right font-bold">Bs. {{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td class="text-right font-bold">Total Unidades:</td>
            <td class="text-right">{{ $totalQuantity }} u.</td>
        </tr>
        <tr>
            <td class="text-right font-bold">Subtotal:</td>
            <td class="text-right">Bs. {{ number_format($purchase->subtotal, 2) }}</td>
        </tr>
        <tr class="total-highlight">
            <td class="text-right">TOTAL COMPRA:</td>
            <td class="text-right">Bs. {{ number_format($purchase->totalAmount, 2) }}</td>
        </tr>
    </table>

    <table class="signatures-table">
        <tr>
            <td>
                <div class="signature-line"></div>
                <div class="signature-title">Entregado Conforme</div>
                <div class="signature-subtitle">Distribuidor / Mayorista / Transportista</div>
            </td>
            <td>
                <div class="signature-line"></div>
                <div class="signature-title">Recibido Conforme</div>
                <div class="signature-subtitle">Almacén Central — Servimática</div>
            </td>
        </tr>
    </table>
</body>
</html>
