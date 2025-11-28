<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Recibo {{ $receipt->id_recibo }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            line-height: 1.1;
            margin: 0;
            padding: 5px;
            color: #000;
        }
        .header {
            text-align: center;
            margin-bottom: 8px;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
        }
        .company-name {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .company-info {
            font-size: 7px;
            color: #666;
        }
        .receipt-title {
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            margin: 8px 0;
            text-transform: uppercase;
        }
        .receipt-info {
            margin-bottom: 8px;
        }
        .info-row {
            display: flex;
            margin-bottom: 2px;
            font-size: 8px;
        }
        .info-label {
            font-weight: bold;
            width: 70px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
            font-size: 8px;
        }
        .table th {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            padding: 3px;
            text-align: left;
            font-weight: bold;
        }
        .table td {
            border: 1px solid #ddd;
            padding: 3px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 10px;
            padding-top: 5px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 7px;
            color: #666;
        }
        .signature {
            margin-top: 15px;
            border-top: 1px solid #000;
            width: 150px;
            text-align: center;
            padding-top: 3px;
            margin-left: auto;
            margin-right: auto;
            font-size: 8px;
        }
        .compact-table {
            font-size: 8px;
            line-height: 1;
        }
        .compact-table td {
            padding: 2px !important;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $company['name'] ?? 'GRUPO 08 SA' }}</div>
        <div class="company-info">
            {{ $company['address'] ?? 'Santa Cruz, Bolivia' }}<br>
            {{ $company['phone'] ?? '+591 XXX XXX XXX' }}
        </div>
    </div>

    <div class="receipt-title">RECIBO DE PAGO</div>

    <!-- Información compacta del recibo -->
    <div class="receipt-info">
        <div class="info-row">
            <div class="info-label">Recibo N°:</div>
            <div>{{ $receipt->numero_recibo ?? 'REC-' . str_pad($receipt->id_recibo, 6, '0', STR_PAD_LEFT) }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Fecha:</div>
            <div>{{ $receipt->fecha->format('d/m/Y H:i') }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Cliente:</div>
            <div>{{ $receipt->sale->client->nombre }} {{ $receipt->sale->client->apellido }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Venta N°:</div>
            <div>{{ $receipt->id_venta }}</div>
        </div>
        @if($receipt->observacion)
        <div class="info-row">
            <div class="info-label">Observación:</div>
            <div>{{ $receipt->observacion }}</div>
        </div>
        @endif
    </div>

    <!-- Métodos de pago compactos -->
    <table class="table compact-table">
        <thead>
            <tr>
                <th style="width: 50%;">Método de Pago</th>
                <th style="width: 30%;">Referencia</th>
                <th style="width: 20%; text-align: right;">Monto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($receipt->paymentDetails as $detail)
            <tr>
                <td>{{ $detail->metodo }}</td>
                <td>{{ $detail->referencia ?? 'N/A' }}</td>
                <td class="text-right">Bs. {{ number_format($detail->monto, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2" class="text-right"><strong>TOTAL PAGADO:</strong></td>
                <td class="text-right"><strong>Bs. {{ number_format($receipt->total, 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <!-- Detalle de venta ultra compacto -->
    @if($receipt->sale->details && $receipt->sale->details->count() > 0)
    <div style="margin-top: 8px;">
        <div style="font-weight: bold; margin-bottom: 3px; font-size: 9px; background: #f8f9fa; padding: 2px;">
            DETALLE DE VENTA
        </div>
        <table style="width: 100%; border-collapse: collapse; font-size: 7px; line-height: 1;">
            <thead>
                <tr style="background-color: #e9ecef;">
                    <th style="width: 50%; border: 1px solid #dee2e6; padding: 2px;">PRODUCTO/SERVICIO</th>
                    <th style="width: 15%; border: 1px solid #dee2e6; padding: 2px; text-align: center;">CANT</th>
                    <th style="width: 17%; border: 1px solid #dee2e6; padding: 2px; text-align: right;">PRECIO</th>
                    <th style="width: 18%; border: 1px solid #dee2e6; padding: 2px; text-align: right;">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($receipt->sale->details as $detail)
                @php
                    $productName = $detail->product ? $detail->product->nombre : ($detail->descripcion ?? 'Producto');
                    // Acortar nombres largos
                    if (strlen($productName) > 30) {
                        $productName = substr($productName, 0, 27) . '...';
                    }
                @endphp
                <tr>
                    <td style="border: 1px solid #dee2e6; padding: 2px; word-break: break-word;">{{ $productName }}</td>
                    <td style="border: 1px solid #dee2e6; padding: 2px; text-align: center;">{{ $detail->cantidad }}</td>
                    <td style="border: 1px solid #dee2e6; padding: 2px; text-align: right;">{{ number_format($detail->precio_unit, 2) }}</td>
                    <td style="border: 1px solid #dee2e6; padding: 2px; text-align: right;">{{ number_format($detail->subtotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background-color: #f8f9fa; font-weight: bold;">
                    <td colspan="3" style="border: 1px solid #dee2e6; padding: 3px; text-align: right;">TOTAL VENTA:</td>
                    <td style="border: 1px solid #dee2e6; padding: 3px; text-align: right;">Bs. {{ number_format($receipt->sale->total, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif

    <div class="signature">
        Firma y Sello
    </div>

    <div class="footer">
        Recibo generado el {{ date('d/m/Y H:i') }}<br>
        Documento válido como comprobante de pago
    </div>
</body>
</html>