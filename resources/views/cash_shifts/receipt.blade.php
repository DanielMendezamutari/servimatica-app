<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cierre de Caja #{{ $shift->id }} — {{ $company->tradeName }}</title>
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
        .info-table, .totals-table {
            width: 100%;
            border-collapse: collapse;
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
            🖨️ Imprimir Arqueo (80mm)
        </button>
    </div>

    <div class="text-center">
        @if(!empty($company->logoUrl))
            <img src="{{ $company->logoUrl }}" alt="{{ $company->tradeName }}" style="max-height: 38px; margin-bottom: 3px;" onerror="this.style.display='none'">
        @endif
        <div class="header-title">{{ mb_strtoupper($company->tradeName) }}</div>
        @if($company->branchName)
            <div>{{ $company->branchName }}</div>
        @endif
        <div>{{ $company->city }}</div>
        <div>ARQUEO Y CIERRE DE CAJA CHICA</div>
        <div>Turno #{{ $shift->id }}</div>
        <div class="divider"></div>
        <div>Apertura: {{ $shift->openedAt ?? 'N/A' }}</div>
        <div>Cierre: {{ $shift->closedAt ?? date('Y-m-d H:i') }}</div>
        <div>Cajero: {{ $shift->userName ?? 'Cajero' }}</div>
    </div>

    <div class="divider"></div>

    <div class="font-bold text-center">CONCILIACIÓN EN EFECTIVO FÍSICO</div>
    <table class="totals-table">
        <tr>
            <td>Fondo de Apertura:</td>
            <td class="text-right">Bs. {{ number_format($shift->openingAmount, 2) }}</td>
        </tr>
        <tr>
            <td>(+) Ventas en Efectivo:</td>
            <td class="text-right">Bs. {{ number_format($shift->totalCashSales, 2) }}</td>
        </tr>
        <tr class="font-bold">
            <td>(=) Total Físico Esperado:</td>
            <td class="text-right">Bs. {{ number_format($shift->expectedAmount ?? ($shift->openingAmount + $shift->totalCashSales), 2) }}</td>
        </tr>
        <tr>
            <td>(~) Conteo Físico Real:</td>
            <td class="text-right font-bold">Bs. {{ number_format($shift->closingAmount ?? 0, 2) }}</td>
        </tr>
        <tr class="font-bold">
            <td>Diferencia (Sobrante/Faltante):</td>
            <td class="text-right">
                {{ ($shift->difference ?? 0) >= 0 ? '+' : '' }}Bs. {{ number_format($shift->difference ?? 0, 2) }}
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <div class="font-bold text-center">COBROS DIGITALES (EN BANCOS / QR)</div>
    <table class="totals-table">
        @if(!empty($shift->digitalTotalsByMethod))
            @foreach($shift->digitalTotalsByMethod as $dt)
                <tr>
                    <td>{{ $dt['name'] }} ({{ $dt['count'] }}):</td>
                    <td class="text-right">Bs. {{ $dt['total_amount'] }}</td>
                </tr>
            @endforeach
        @endif
        <tr class="font-bold">
            <td>Total Digital Recibido:</td>
            <td class="text-right">Bs. {{ number_format($shift->totalQrSales, 2) }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <table class="totals-table total-highlight">
        <tr>
            <td>VENTAS TOTALES DEL TURNO:</td>
            <td class="text-right">Bs. {{ number_format($shift->totalCashSales + $shift->totalQrSales, 2) }}</td>
        </tr>
    </table>

    @if(!empty($shift->notes))
        <div class="divider"></div>
        <div><strong>Observaciones:</strong> {{ $shift->notes }}</div>
    @endif

    <div class="divider"></div>

    <br><br>
    <div style="border-top: 1px solid #000; text-align: center; margin: 0 20px;">
        Firma del Cajero Responsable
    </div>

    <div class="text-center footer">
        *** {{ $company->tradeName }} — Control de Caja ***
    </div>

    <script>
        if (new URLSearchParams(window.location.search).get('autoprint') === '1') {
            window.addEventListener('load', () => window.print());
        }
    </script>
</body>
</html>
