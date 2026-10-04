<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Devolución {{ $return->returnNumber }} — {{ $company->tradeName }}</title>
    @if(($format ?? 'thermal') === 'letter')
    <style>
        @page {
            size: letter portrait;
            margin: 15mm 20mm;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #222;
            font-size: 13px;
            line-height: 1.5;
            margin: 0;
            padding: 20px;
        }
        .header-box {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #000;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .company-info h1 {
            margin: 0 0 4px 0;
            font-size: 20px;
            color: #111;
        }
        .return-title {
            text-align: right;
        }
        .return-title h2 {
            margin: 0 0 4px 0;
            font-size: 18px;
            color: #d32f2f;
        }
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            background: #f8f9fa;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th {
            background: #212529;
            color: #fff;
            text-align: left;
            padding: 8px 12px;
            font-size: 12px;
        }
        .table td {
            padding: 8px 12px;
            border-bottom: 1px solid #dee2e6;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .signatures {
            display: flex;
            justify-content: space-around;
            margin-top: 60px;
            padding-top: 20px;
        }
        .sign-line {
            width: 220px;
            border-top: 1px solid #000;
            text-align: center;
            font-size: 11px;
            padding-top: 5px;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
    @else
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
        .info-table, .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        .items-table th {
            text-align: left;
            border-bottom: 1px dashed #000;
            padding: 3px 0;
        }
        .items-table td {
            padding: 3px 0;
            vertical-align: top;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
    @endif
</head>
<body>

    <div class="no-print" style="margin-bottom: 15px; text-align: center;">
        <button onclick="window.print()" style="padding: 8px 16px; font-weight: bold; cursor: pointer; background: #000; color: #fff; border: none; border-radius: 4px;">
            🖨️ Imprimir Comprobante
        </button>
        <span style="margin: 0 10px;">|</span>
        <a href="?format={{ ($format ?? 'thermal') === 'letter' ? 'thermal' : 'letter' }}" style="font-size: 12px; color: #0066cc;">
            Cambiar a formato {{ ($format ?? 'thermal') === 'letter' ? 'Térmico (80mm)' : 'Carta' }}
        </a>
    </div>

    @if(($format ?? 'thermal') === 'letter')
    <!-- FORMATO CARTA FORMAL -->
    <div class="header-box">
        <div class="company-info">
            @if(!empty($company->logoUrl))
                <img src="{{ $company->logoUrl }}" alt="{{ $company->tradeName }}" style="max-height: 45px; margin-bottom: 6px;" onerror="this.style.display='none'">
            @endif
            <h1>{{ mb_strtoupper($company->tradeName) }}</h1>
            <div>{{ $company->address ?? $company->city }}</div>
            <div>NIT: {{ $company->taxId ?? 'S/N' }} | Telf: {{ implode(' - ', array_filter([$company->phone, $company->mobile])) }}</div>
        </div>
        <div class="return-title">
            <h2>NOTA DE DEVOLUCIÓN Y GARANTÍA</h2>
            <div class="font-bold" style="font-size: 16px;">N° {{ $return->returnNumber }}</div>
            <div>Fecha: {{ $return->createdAt ? date('d/m/Y H:i', strtotime($return->createdAt)) : date('d/m/Y H:i') }}</div>
            <div style="margin-top: 4px;">Factura Origen: <strong>{{ $return->invoiceNumber }}</strong></div>
        </div>
    </div>

    <div class="details-grid">
        <div>
            <div><strong>Cliente:</strong> {{ $return->clientName }}</div>
            <div><strong>Atendido por:</strong> {{ $return->userName ?? 'Personal Técnico' }}</div>
        </div>
        <div>
            <div><strong>Resolución:</strong> 
                @if($return->resolution === 'cambio_fisico')
                    <span style="color: #2e7d32; font-weight: bold;">CAMBIO FÍSICO 1 A 1</span>
                @elseif($return->resolution === 'reembolso_efectivo')
                    <span style="color: #c62828; font-weight: bold;">REEMBOLSO EN EFECTIVO</span>
                @else
                    <span style="color: #1565c0; font-weight: bold;">NOTA DE CRÉDITO / SALDO A FAVOR</span>
                @endif
            </div>
            <div><strong>Estado:</strong> {{ strtoupper($return->status) }}</div>
        </div>
        <div style="grid-column: span 2;">
            <strong>Diagnóstico / Motivo:</strong> {{ $return->reason }}
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th style="width: 10%;">Cant.</th>
                <th style="width: 45%;">Producto & Número de Serie</th>
                <th style="width: 25%;">Destino de Mercadería</th>
                <th style="width: 20%;" class="text-right">Monto Liquidado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($return->items as $it)
            <tr>
                <td class="font-bold">{{ $it->quantity }}x</td>
                <td>
                    <strong>{{ $it->productName }}</strong>
                    @if($it->serialNumber)
                        <div style="font-size: 11px; color: #555;">S/N: {{ $it->serialNumber }}</div>
                    @endif
                </td>
                <td>
                    @if($it->condition === 'stock_operativo')
                        <span style="color: #2e7d32;">Stock Operativo (Regresa a Venta)</span>
                    @else
                        <span style="color: #d32f2f;">Falla Técnica (Cuarentena RMA)</span>
                    @endif
                </td>
                <td class="text-right font-bold">Bs. {{ number_format($it->subtotal, 2, '.', '') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right font-bold" style="padding-top: 12px; font-size: 14px;">TOTAL:</td>
                <td class="text-right font-bold" style="padding-top: 12px; font-size: 16px; color: #d32f2f;">
                    Bs. {{ number_format($return->totalRefundAmount, 2, '.', '') }}
                </td>
            </tr>
        </tfoot>
    </table>

    <div class="signatures">
        <div class="sign-line">
            Firma del Cliente<br>
            CI: _______________________
        </div>
        <div class="sign-line">
            Servicio Técnico / Autorizado<br>
            {{ $company->tradeName }}
        </div>
    </div>

    @else
    <!-- FORMATO TÉRMICO 80MM -->
    <div class="text-center">
        @if(!empty($company->logoUrl))
            <img src="{{ $company->logoUrl }}" alt="{{ $company->tradeName }}" style="max-height: 40px; margin-bottom: 4px;" onerror="this.style.display='none'">
        @endif
        <div class="header-title">{{ mb_strtoupper($company->tradeName) }}</div>
        @if($company->slogan)
            <div>{{ $company->slogan }}</div>
        @endif
        <div>{{ $company->address ?? $company->city }}</div>
        @if($company->taxId)
            <div class="font-bold">NIT: {{ $company->taxId }}</div>
        @endif
        <div class="divider"></div>
        <div class="font-bold">COMPROBANTE DE DEVOLUCIÓN</div>
        <div class="font-bold">{{ $return->returnNumber }}</div>
        <div>Fecha: {{ $return->createdAt ? date('d/m/Y H:i', strtotime($return->createdAt)) : date('d/m/Y H:i') }}</div>
        <div>Factura Origen: {{ $return->invoiceNumber }}</div>
    </div>

    <div class="divider"></div>

    <table class="info-table">
        <tr>
            <td style="width: 35%;">Cliente:</td>
            <td class="font-bold">{{ $return->clientName }}</td>
        </tr>
        <tr>
            <td>Atendido por:</td>
            <td>{{ $return->userName ?? 'Personal' }}</td>
        </tr>
        <tr>
            <td>Resolución:</td>
            <td class="font-bold">
                @if($return->resolution === 'cambio_fisico')
                    CAMBIO FÍSICO 1 A 1
                @elseif($return->resolution === 'reembolso_efectivo')
                    REEMBOLSO EFECTIVO
                @else
                    NOTA DE CRÉDITO
                @endif
            </td>
        </tr>
        <tr>
            <td>Motivo:</td>
            <td>{{ $return->reason }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 15%;">Cant</th>
                <th style="width: 55%;">Producto</th>
                <th style="width: 30%;" class="text-right">Monto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($return->items as $it)
            <tr>
                <td class="font-bold">{{ $it->quantity }}x</td>
                <td>
                    {{ $it->productName }}
                    @if($it->serialNumber)
                        <div style="font-size: 8.5px; color: #333;">S/N: {{ $it->serialNumber }}</div>
                    @endif
                    <div style="font-size: 8px; color: #555;">
                        {{ $it->condition === 'stock_operativo' ? '[Buen estado]' : '[Defectuoso / RMA]' }}
                    </div>
                </td>
                <td class="text-right font-bold">Bs. {{ number_format($it->subtotal, 2, '.', '') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="divider"></div>

    <div style="display: flex; justify-content: space-between; font-size: 13px; font-weight: bold;">
        <span>TOTAL LIQUIDADO:</span>
        <span>Bs. {{ number_format($return->totalRefundAmount, 2, '.', '') }}</span>
    </div>

    <div class="divider"></div>

    <div style="margin-top: 30px; text-align: center;">
        <div style="border-top: 1px dashed #000; width: 60%; margin: 0 auto 5px auto;"></div>
        <div>Firma Cliente</div>
    </div>

    <div class="text-center" style="margin-top: 15px; font-size: 9px; color: #555;">
        Comprobante de respaldo de garantía técnica.<br>
        {{ $company->tradeName }}
    </div>
    @endif

</body>
</html>
