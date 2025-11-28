<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cotización #{{ $quotation->id_cotizacion }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #1976D2;
            padding-bottom: 10px;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #1976D2;
            margin-bottom: 5px;
        }
        .company-info {
            font-size: 10px;
            color: #666;
        }
        .document-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 20px 0;
            text-transform: uppercase;
            background-color: #f5f5f5;
            padding: 5px;
        }
        .info-section {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 5px;
            vertical-align: top;
        }
        .label {
            font-weight: bold;
            color: #555;
            width: 120px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th {
            background-color: #1976D2;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: bold;
        }
        .table td {
            border-bottom: 1px solid #ddd;
            padding: 8px;
        }
        .text-right {
            text-align: right;
        }
        .total-section {
            width: 100%;
            text-align: right;
            margin-top: 10px;
        }
        .total-table {
            float: right;
            width: 300px;
            border-collapse: collapse;
        }
        .total-table td {
            padding: 5px;
            border-bottom: 1px solid #eee;
        }
        .total-label {
            font-weight: bold;
        }
        .total-amount {
            font-size: 14px;
            font-weight: bold;
            color: #1976D2;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $company['name'] ?? 'GRUPO 08 SA' }}</div>
        <div class="company-info">
            {{ $company['address'] ?? 'Santa Cruz, Bolivia' }}<br>
            Tel: {{ $company['phone'] ?? '+591 78562356' }} | Email: {{ $company['email'] ?? 'info@grupo08.com' }}
        </div>
    </div>

    <div class="document-title">COTIZACIÓN #{{ str_pad($quotation->id_cotizacion, 6, '0', STR_PAD_LEFT) }}</div>

    <table class="info-table">
        <tr>
            <td width="50%">
                <strong>CLIENTE:</strong><br>
                {{ $quotation->client->nombre }} {{ $quotation->client->apellido }}<br>
                {{ $quotation->client->email }}<br>
                {{ $quotation->client->telefono }}
            </td>
            <td width="50%">
                <strong>DETALLES:</strong><br>
                <table>
                    <tr>
                        <td class="label">Fecha:</td>
                        <td>{{ $quotation->fecha_creacion->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="label">Servicio:</td>
                        <td>{{ $quotation->service->nombre }}</td>
                    </tr>
                    <tr>
                        <td class="label">Estado:</td>
                        <td>{{ $quotation->estado }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <h3 style="margin-top: 20px; color: #333;">Productos / Materiales</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Descripción</th>
                <th class="text-right" width="15%">Cant.</th>
                <th class="text-right" width="20%">Precio Unit.</th>
                <th class="text-right" width="20%">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quotation->details as $detail)
            <tr>
                <td>{{ $detail->product->nombre }}</td>
                <td class="text-right">{{ $detail->cantidad }}</td>
                <td class="text-right">Bs. {{ number_format($detail->precio_unit, 2) }}</td>
                <td class="text-right">Bs. {{ number_format($detail->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-section">
        <table class="total-table">
            <tr>
                <td class="total-label">Subtotal:</td>
                <td class="text-right">Bs. {{ number_format($quotation->subtotal, 2) }}</td>
            </tr>
            @if($quotation->descuento > 0)
            <tr>
                <td class="total-label">Descuento:</td>
                <td class="text-right">- Bs. {{ number_format($quotation->descuento, 2) }}</td>
            </tr>
            @endif
            <tr>
                <td class="total-label" style="border-top: 2px solid #333;">TOTAL:</td>
                <td class="text-right total-amount" style="border-top: 2px solid #333;">Bs. {{ number_format($quotation->total, 2) }}</td>
            </tr>
        </table>
    </div>

    @if($quotation->observaciones)
    <div style="margin-top: 30px; background: #f9f9f9; padding: 10px; border-radius: 5px;">
        <strong>Observaciones:</strong><br>
        {{ $quotation->observaciones }}
    </div>
    @endif

    <div class="footer">
        Generado el {{ date('d/m/Y H:i') }}<br>
        Este documento es una cotización y no representa una factura fiscal. Validez: 15 días.
    </div>
</body>
</html>
