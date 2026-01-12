<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotización - {{ $cotizacion['cliente'] }}</title>
    <style>
        @page { margin: 20px; }
        body { 
            font-family: 'DejaVu Sans', sans-serif; 
            font-size: 12px;
            color: #2c3e50;
            line-height: 1.4;
        }
        .header {
            width: 100%;
            border-bottom: 2px solid #3498db;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 22px;
            font-weight: bold;
            color: #000000;
            margin: 0;
            text-align: center;
        }
        .company-info {
            font-size: 10px;
            color: #000000;
            line-height: 1.3;
            text-align: center;
        }
        .quote-title {
            font-size: 18px;
            font-weight: bold;
            color: #3498db;
            text-transform: uppercase;
            margin: 0;
            text-align:center;
        }
        .client-info {
            margin-top: 15px;
            font-size: 11px;
            width: 100%;
        }
        .client-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
        }
        .client-table td {
            padding: 4px 8px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            background: #f8f9fa;
            vertical-align: top;
            word-wrap: break-word;
        }
        table.table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        table.table th {
            background-color: #2c5aa0;
            color: white;
            border: 1px solid #2c5aa0;
            padding: 6px;
            font-size: 10px;
        }
        table.table td {
            border: 1px solid #bdc3c7;
            padding: 6px;
            font-size: 10px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals {
            width: 280px;
            margin-left: auto;
            margin-top: 15px;
            border-collapse: collapse;
        }
        .totals td {
            padding: 6px 10px;
            font-size: 11px;
        }
        .totals tr:last-child td {
            border-top: 2px solid #2c3e50;
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .discount-row { 
            color: #e74c3c; 
            font-weight: bold;
            background-color: #fff5f5 !important;
        }
        .isr-row { 
            color: #000000; 
            font-weight: bold;
            background-color: #fffaf0 !important;
        }
        .footer {
            margin-top: 25px;
            padding-top: 8px;
            border-top: 1px solid #bdc3c7;
            text-align: center;
            font-size: 9px;
            color: #000000;
        }
        .text-success { color: #000000; }
        .text-danger { color: #e74c3c; }
    </style>
</head>
<body>

    <!-- ENCABEZADO -->
    <table class="header" width="100%">
        <tr>
            <td style="width: 20%; vertical-align: top; text-align: left;">
                @php
                    $logoPath = public_path('imagenes/logo.jpg');
                @endphp
                @if (file_exists($logoPath))
                    <img src="data:image/jpeg;base64,{{ base64_encode(file_get_contents($logoPath)) }}" alt="SOLUCIONES PC" class="company-logo" style="width:80px;height:60px;object-fit:contain;">
                @else
                    <div style="width:80px;height:60px;border:1px dashed #ccc;text-align:center;font-size:8px;line-height:60px;">LOGO</div>
                @endif
            </td>
            <td style="width: 60%; vertical-align: top; text-align: center;">
                <div class="company-name">SOLUCIONES PC</div>
                <div class="company-info">
                    <p>RFC: ZARE881013I12</p>
                    <p>BLVD ADOLFO LOPEZ MATEOS 110, EJIDO NUEVO MEXICALI, SAN QUINTÍN B.C</p>
                    <p>6161362976</p>
                </div>
                <div class="quote-title">COTIZACIÓN</div>
            </td>
            <td style="width: 20%; vertical-align: top; text-align: right;"></td>
        </tr>
    </table>

    <!-- CLIENTE -->
    <div class="client-info">
        <table class="client-table">
            <tr>
                <td><strong>Cliente:</strong> {{ $cotizacion['cliente'] ?? 'N/A' }}</td>
                <td><strong>Fecha:</strong> {{ $cotizacion['fecha'] }}</td>
                @if(!empty($cotizacion['telefono']))
                    <td><strong>Teléfono:</strong> {{ $cotizacion['telefono'] }}</td>
                @endif
                @if(!empty($cotizacion['direccion']))
                    <td><strong>Dirección:</strong> {{ $cotizacion['direccion'] }}</td>
                @endif
                <td><strong>Tipo:</strong> 
                    @if($cotizacion['tipo_cliente'] == 'persona_moral')
                        Persona Moral
                    @elseif($cotizacion['tipo_cliente'] == 'persona_fisica')
                        Persona Física
                    @else
                        Público General
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <!-- DETALLE DE PRODUCTOS -->
<!-- DETALLE DE PRODUCTOS -->
<table class="table">
    <thead>
        <tr>
            <th width="10%" class="text-center">CANTIDAD</th>
    <th width="45%">PRODUCTO / SERVICIO</th>
            <th width="15%" class="text-right">PRECIO UNITARIO</th>
            <th width="15%" class="text-right">SUBTOTAL</th>
        </tr>
    </thead>
    <tbody>
        @forelse($cotizacion['productos'] as $producto)
            <tr>
                <td class="text-center">{{ $producto['cantidad'] }}</td>
                <td>{{ $producto['nombre'] }}</td>
                <td class="text-right">
                    ${{ number_format($producto['precioFinalSinDescuento'] ?? $producto['precioFinal'], 2) }}
                </td>
                <td class="text-right">
                    <!--  CORRECCIÓN: Calcular subtotal = precio unitario × cantidad -->
                    ${{ number_format(($producto['precioFinalSinDescuento'] ?? $producto['precioFinal']) * $producto['cantidad'], 2) }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="text-center" style="color:#95a5a6;padding:15px;">No hay productos en esta cotización</td>
            </tr>
        @endforelse
    </tbody>
</table>

    <!-- TOTALES -->
    <!-- TOTALES -->
<table class="totals">
    <!-- Descuento General - MOSTRAR SIEMPRE CUANDO HAYA PORCENTAJE -->
    @if(isset($cotizacion['descuentoPorcentaje']) && $cotizacion['descuentoPorcentaje'] > 0)
        <!-- CASO 1: Persona Moral CON descuento -->
        @if($cotizacion['tipo_cliente'] == 'persona_moral')
            <tr class="isr-row">
                <td><strong>Total Sin Descuento:</strong></td>
                <td class="text-right">${{ number_format($cotizacion['subtotalPdfClienteF'] ?? 0, 2) }}</td>
            </tr>
            <tr class="isr-row">
                <td><strong>Subtotal Sin Descuento:</strong></td>
                <td class="text-right">${{ number_format($cotizacion['subtotalSinIvaClienteMoral'] ?? 0, 2) }}</td>
            </tr>
            <tr class="isr-row">
                <td><strong>Subtotal Con Descuento del ({{ $cotizacion['descuentoPorcentaje'] }}%):</strong></td>
                <td class="text-right">${{ number_format($cotizacion['subtotalSinIvaDescuento'] ?? 0, 2) }}</td>
            </tr>
             <tr class="isr-row">
                <td><strong>+ IVA:</strong></td>
                <td class="text-right">${{ number_format($cotizacion['ivaFinalTotal'] ?? 0, 2) }}</td>
            </tr>
            
            <tr class="isr-row">
                <td><strong>- ISR 1.25%:</strong></td>
                <td class="text-right">${{ number_format($cotizacion['isrTotal'] ?? 0, 2) }}</td>
            </tr>
        
        <!-- CASO 2: Persona Física/Público General CON descuento -->
        @else
            <tr>
                <td><strong>Subtotal:</strong></td>
                <td class="text-right">${{ number_format($cotizacion['subtotalPdfClienteF'] ?? 0, 2) }}</td>
            </tr>
            <tr style="color: #e74c3c; font-weight: bold; background-color: #fff5f5;">
                <td><strong>- Se Ha Aplicado Un Descuento DEL ({{ $cotizacion['descuentoPorcentaje'] }}%):</strong></td>
                <td class="text-right">-${{ number_format($cotizacion['totalDescuentoAcumulado'] ?? 0, 2) }}</td>
            </tr>
        @endif
    
    <!-- SIN DESCUENTO -->
    @else
        <!-- CASO 3: Persona Moral SIN descuento -->
        @if($cotizacion['tipo_cliente'] == 'persona_moral' && ($cotizacion['isrTotal'] ?? 0) > 0)
            <tr class="isr-row">
                <td><strong>Subtotal:</strong></td>
                <td class="text-right">${{ number_format($cotizacion['subtotalParaPdfInterno'] ?? 0, 2) }}</td>
            </tr>
            <tr class="isr-row">
                <td><strong>+ IVA:</strong></td>
                <td class="text-right">${{ number_format($cotizacion['subtotalParaPdfInterno'] * 0.08, 2) }}</td>
            </tr>
            <tr class="isr-row">
                <td><strong>- ISR 1.25%:</strong></td>
                <td class="text-right">${{ number_format($cotizacion['isrTotal'] ?? 0, 2) }}</td>
            </tr>
        
        <!-- CASO 4: Persona Física/Público General SIN descuento -->
        @endif
    @endif

    <!-- Total Final (SIEMPRE SE MUESTRA) -->
    <tr>
        <td><strong>TOTAL:</strong></td>
        <td class="text-right text-success">${{ number_format($cotizacion['total'], 2) }}</td>
    </tr>
</table>

    <!-- FOOTER -->
    <div class="footer">
        <p><strong>TÉRMINOS Y CONDICIONES</strong></p>
        <p>Precio sujeto al tipo de cambio | Productos disponibles al momento de cotizar</p>
        <p>Cotización válida hasta: {{ $cotizacion['valido_hasta'] }}</p>
    </div>

</body>
</html>