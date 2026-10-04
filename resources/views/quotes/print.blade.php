<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proforma {{ $quote->quoteNumber }} — {{ $company->tradeName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        @page {
            size: letter;
            margin: 8mm 10mm;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #1e293b;
            background-color: #f1f5f9;
            font-size: 12px;
            line-height: 1.4;
        }

        /* Barra de herramientas en pantalla */
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: #0f172a;
            color: #ffffff;
            padding: 8px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .toolbar-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            font-weight: 600;
        }

        .toolbar-badge {
            background: rgba(37, 99, 235, 0.3);
            color: #60a5fa;
            border: 1px solid rgba(96, 165, 250, 0.4);
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
        }

        .toolbar-actions {
            display: flex;
            gap: 8px;
        }

        .btn-print {
            background: #2563eb;
            color: #ffffff;
            border: none;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 5px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-print:hover {
            background: #1d4ed8;
        }

        .btn-close {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 500;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-close:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Contenedor de la hoja */
        .page-wrapper {
            padding: 20px 16px 30px 16px;
            display: flex;
            justify-content: center;
        }

        .paper-sheet {
            width: 100%;
            max-width: 820px;
            background: #ffffff;
            padding: 28px 34px;
            border-radius: 6px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Membrete Compacto */
        .header-layout {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }

        .brand-box {
            display: flex;
            flex-direction: column;
            max-width: 500px;
        }

        .brand-logo-img {
            max-height: 40px;
            width: auto;
            max-width: 190px;
            object-fit: contain;
            margin-bottom: 4px;
        }

        .brand-title {
            font-size: 17px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.2px;
            line-height: 1.15;
        }

        .brand-sub {
            font-size: 10.5px;
            color: #64748b;
            font-weight: 500;
            line-height: 1.35;
        }

        .brand-sub strong {
            color: #334155;
        }

        .doc-badge-box {
            text-align: right;
        }

        .doc-tag {
            display: inline-block;
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 4px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .doc-number {
            font-size: 17px;
            font-weight: 800;
            color: #2563eb;
            letter-spacing: -0.2px;
        }

        .doc-date {
            font-size: 10.5px;
            color: #64748b;
            margin-top: 1px;
        }

        /* Barra Compacta del Cliente */
        .client-bar {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            padding: 8px 14px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .client-info-item {
            font-size: 11px;
            color: #475569;
        }

        .client-info-item strong {
            color: #0f172a;
        }

        /* Tabla de Productos Compacta */
        .table-container {
            margin-bottom: 12px;
        }

        table.items-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            overflow: hidden;
        }

        table.items-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 6px 8px;
            border: none;
        }

        table.items-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 11.5px;
            color: #334155;
            vertical-align: middle;
        }

        table.items-table tbody tr:last-child td {
            border-bottom: none;
        }

        table.items-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .col-center {
            text-align: center;
        }

        .col-right {
            text-align: right;
        }

        .item-name {
            font-weight: 600;
            color: #0f172a;
        }

        .warranty-pill {
            display: inline-block;
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 600;
            padding: 1px 5px;
            white-space: nowrap;
        }

        /* Sección inferior: Condiciones + Pagos + Totales */
        .bottom-section {
            display: grid;
            grid-template-columns: 1.3fr 0.7fr;
            gap: 12px;
            align-items: start;
            margin-bottom: 12px;
        }

        .terms-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            padding: 8px 12px;
        }

        .terms-title {
            font-size: 10px;
            font-weight: 700;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 3px;
        }

        .terms-list {
            margin: 0;
            padding-left: 14px;
            font-size: 10px;
            color: #64748b;
            line-height: 1.4;
        }

        .notes-highlight {
            margin-top: 4px;
            padding-top: 4px;
            border-top: 1px dashed #cbd5e1;
            font-size: 10px;
            color: #475569;
        }

        /* Totales */
        .totals-card {
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            overflow: hidden;
            background: #ffffff;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 10px;
            font-size: 11px;
            border-bottom: 1px solid #f1f5f9;
        }

        .totals-label {
            color: #64748b;
            font-weight: 500;
        }

        .totals-val {
            font-weight: 600;
            color: #1e293b;
        }

        .discount-val {
            color: #dc2626;
            font-weight: 700;
        }

        .total-final-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 7px 10px;
            background-color: #eff6ff;
            border-top: 2px solid #2563eb;
        }

        .total-final-label {
            font-size: 11.5px;
            font-weight: 800;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .total-final-val {
            font-size: 15px;
            font-weight: 800;
            color: #1d4ed8;
        }

        /* Cuentas Bancarias y Métodos de Pago Habilitados */
        .payments-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            padding: 8px 12px;
            margin-bottom: 12px;
            page-break-inside: avoid;
        }

        .payments-title {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: #1e3a8a;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .payments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 6px;
        }

        .payment-item {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 5px 8px;
            font-size: 10.5px;
        }

        .payment-bank-name {
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 1px;
        }

        .payment-account-num {
            font-family: monospace;
            font-size: 10.5px;
            color: #1d4ed8;
            font-weight: 700;
        }

        .payment-holder {
            color: #64748b;
            font-size: 10px;
        }

        /* Firmas */
        .signatures-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 8px;
            page-break-inside: avoid;
        }

        .sig-box {
            text-align: center;
        }

        .sig-line {
            border-top: 1px solid #94a3b8;
            padding-top: 4px;
            font-size: 10.5px;
            color: #475569;
            font-weight: 600;
        }

        .sig-role {
            font-size: 9.5px;
            color: #64748b;
            font-weight: 400;
        }

        /* Reglas de Impresión Carta / A4 Limpia a 1 Hoja */
        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
                font-size: 11px;
            }

            .no-print {
                display: none !important;
            }

            .page-wrapper {
                padding: 0 !important;
                display: block !important;
            }

            .paper-sheet {
                box-shadow: none !important;
                border-radius: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
                min-height: auto !important;
            }

            .header-layout {
                border-bottom: 2px solid #000000 !important;
            }

            table.items-table {
                border: 1px solid #000000 !important;
            }

            table.items-table th {
                background-color: #000000 !important;
                color: #ffffff !important;
            }

            .total-final-row {
                background-color: #f1f5f9 !important;
                border-top: 2px solid #000000 !important;
            }

            .total-final-val {
                color: #000000 !important;
            }

            .warranty-pill {
                border: 1px solid #000000 !important;
                color: #000000 !important;
                background: transparent !important;
            }

            .payments-box {
                border: 1px solid #000000 !important;
                background: transparent !important;
            }

            .payment-item {
                border: 1px solid #cbd5e1 !important;
            }
        }
    </style>
</head>
<body>

    <!-- Barra de Herramientas Superior en Pantalla -->
    <header class="toolbar no-print">
        <div class="toolbar-title">
            <span>📄 Vista Previa de Proforma Comercial</span>
            <span class="toolbar-badge">{{ $quote->quoteNumber }}</span>
        </div>
        <div class="toolbar-actions">
            <button type="button" class="btn-print" onclick="window.print()">
                <span>🖨️</span> Imprimir / Guardar PDF
            </button>
            <button type="button" class="btn-close" onclick="window.close()">
                ✕ Cerrar
            </button>
        </div>
    </header>

    <div class="page-wrapper">
        <article class="paper-sheet">
            <div>
                <!-- Membrete de la Empresa y Datos de la Cotización -->
                <div class="header-layout">
                    <div class="brand-box">
                        <img src="{{ $company->logoUrl ?: asset('images/logo_servimatica.png') }}" 
                             alt="{{ $company->tradeName }}" 
                             class="brand-logo-img" 
                             onerror="this.onerror=null; this.src='{{ asset('images/logo_servimatica.png') }}';">
                        
                        <div class="brand-title">{{ mb_strtoupper($company->tradeName) }}</div>
                        @if($company->slogan)
                            <div class="brand-sub"><em>{{ $company->slogan }}</em></div>
                        @endif
                        <div class="brand-sub">
                            {{ $company->city }}
                            @if($company->address)
                                — {{ $company->address }}
                            @endif
                        </div>
                        @if($company->mobile || $company->phone)
                            <div class="brand-sub">
                                <strong>Tel/Cel:</strong> {{ implode(' / ', array_filter([$company->phone, $company->mobile])) }}
                                @if($company->email) | <strong>Email:</strong> {{ $company->email }} @endif
                            </div>
                        @endif
                        @if($company->taxId)
                            <div class="brand-sub">
                                <strong>NIT:</strong> {{ $company->taxId }}
                            </div>
                        @endif
                    </div>
                    <div class="doc-badge-box">
                        <span class="doc-tag">COTIZACIÓN / PROFORMA</span>
                        <div class="doc-number">{{ $quote->quoteNumber }}</div>
                        <div class="doc-date">Emisión: <strong>{{ $quote->createdAt ? date('d/m/Y', strtotime($quote->createdAt)) : date('d/m/Y') }}</strong></div>
                        <div class="doc-date">Válido hasta: <strong>{{ date('d/m/Y', strtotime($quote->validUntil)) }}</strong></div>
                    </div>
                </div>

                <!-- Barra Compacta de Datos del Cliente -->
                <div class="client-bar">
                    <div class="client-info-item">
                        Cliente: <strong>{{ $quote->clientName }}</strong>
                    </div>
                    @if($quote->clientPhone)
                        <div class="client-info-item">
                            Tel/WhatsApp: <strong>{{ $quote->clientPhone }}</strong>
                        </div>
                    @endif
                    <div class="client-info-item">
                        Atendido por: <strong>{{ $quote->sellerName ?? 'Asesor Comercial' }}</strong>
                    </div>
                </div>

                <!-- Tabla de Productos con Garantía Técnica -->
                <div class="table-container">
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th style="width: 32px;" class="col-center">#</th>
                                <th>Descripción del Producto</th>
                                <th style="width: 120px;" class="col-center">Garantía Técnica</th>
                                <th style="width: 50px;" class="col-center">Cant.</th>
                                <th style="width: 100px;" class="col-right">P. Unitario</th>
                                <th style="width: 110px;" class="col-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($quote->items as $idx => $item)
                                @php
                                    $days = $warranties[$item->productId] ?? null;
                                    $warrantyStr = 'Garantía oficial';
                                    if ($days !== null && $days > 0) {
                                        if ($days % 365 === 0) {
                                            $years = $days / 365;
                                            $warrantyStr = $years === 1 ? '12 meses' : ($years * 12) . ' meses';
                                        } elseif ($days % 30 === 0) {
                                            $warrantyStr = ($days / 30) . ' meses';
                                        } else {
                                            $warrantyStr = "{$days} días";
                                        }
                                    }
                                @endphp
                                <tr>
                                    <td class="col-center" style="color: #64748b; font-weight: 500;">{{ $idx + 1 }}</td>
                                    <td>
                                        <div class="item-name">{{ $item->productName }}</div>
                                    </td>
                                    <td class="col-center">
                                        <span class="warranty-pill">🛡️ {{ $warrantyStr }}</span>
                                    </td>
                                    <td class="col-center" style="font-weight: 600;">{{ $item->quantity }}</td>
                                    <td class="col-right">Bs. {{ number_format($item->unitPrice, 2, '.', ',') }}</td>
                                    <td class="col-right" style="font-weight: 700; color: #0f172a;">Bs. {{ number_format($item->subtotal, 2, '.', ',') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Términos y Totales -->
                <div class="bottom-section">
                    <div class="terms-card">
                        <div class="terms-title">Términos Comerciales</div>
                        @if(!empty($company->defaultQuoteTerms))
                            <ul class="terms-list">
                                @foreach(explode("\n", str_replace("\r", "", $company->defaultQuoteTerms)) as $termLine)
                                    @if(trim($termLine) !== '')
                                        <li>{{ trim($termLine) }}</li>
                                    @endif
                                @endforeach
                            </ul>
                        @else
                            <ul class="terms-list">
                                <li>Precios en Bolivianos (Bs.), válidos hasta la fecha indicada.</li>
                                <li>Cotización sujeta a inventario disponible al momento de la compra.</li>
                                <li>Garantía técnica oficial y soporte local.</li>
                            </ul>
                        @endif
                        @if($quote->notes)
                            <div class="notes-highlight">
                                <strong>Observaciones:</strong> {{ $quote->notes }}
                            </div>
                        @endif
                    </div>

                    <div class="totals-card">
                        <div class="totals-row">
                            <span class="totals-label">Subtotal:</span>
                            <span class="totals-val">Bs. {{ number_format($quote->subtotal, 2, '.', ',') }}</span>
                        </div>
                        @if($quote->discountAmount > 0)
                            <div class="totals-row">
                                <span class="totals-label">Descuento:</span>
                                <span class="totals-val discount-val">- Bs. {{ number_format($quote->discountAmount, 2, '.', ',') }}</span>
                            </div>
                        @endif
                        <div class="total-final-row">
                            <span class="total-final-label">Total a Pagar:</span>
                            <span class="total-final-val">Bs. {{ number_format($quote->totalAmount, 2, '.', ',') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Cuentas Bancarias y Métodos de Pago Habilitados -->
                @if(isset($paymentMethods) && $paymentMethods->isNotEmpty())
                    <div class="payments-box">
                        <div class="payments-title">
                            💳 Cuentas Bancarias y Medios de Pago Habilitados
                        </div>
                        <div class="payments-grid">
                            @foreach($paymentMethods as $pm)
                                @if($pm->type === 'bank_transfer' && !empty($pm->account_number))
                                    <div class="payment-item">
                                        <div class="payment-bank-name">{{ $pm->bank_name ?: $pm->name }}</div>
                                        <div class="payment-account-num">Cta: {{ $pm->account_number }}</div>
                                        @if(!empty($pm->account_holder))
                                            <div class="payment-holder">Titular: {{ $pm->account_holder }}</div>
                                        @endif
                                    </div>
                                @elseif($pm->type === 'qr')
                                    <div class="payment-item">
                                        <div class="payment-bank-name">📱 {{ $pm->name }}</div>
                                        <div class="payment-holder">Cobro QR Simple interbancario disponible</div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Firmas y Sellos -->
            <div class="signatures-grid">
                <div class="sig-box">
                    <div class="sig-line">{{ $company->tradeName }}</div>
                    <div class="sig-role">Firma y Sello Comercial</div>
                </div>
                <div class="sig-box">
                    <div class="sig-line">{{ $quote->clientName }}</div>
                    <div class="sig-role">Aceptación del Cliente</div>
                </div>
            </div>
        </article>
    </div>

    @if(request()->query('autoprint'))
    <script>
        window.addEventListener('load', function() {
            setTimeout(function() { window.print(); }, 400);
        });
    </script>
    @endif
</body>
</html>
