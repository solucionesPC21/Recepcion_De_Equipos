<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotización Interna - {{ $cotizacion['cliente'] }}</title>
    <style>
        @page { margin: 20px; }

        body { 
            font-family: 'DejaVu Sans', sans-serif; 
            font-size: 12px;
            color: #000000;
            line-height: 1.4;
        }

        /* ===== Encabezado ===== */
        .header {
            width: 100%;
            border-bottom: 2px solid #3498db;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header td {
            vertical-align: top;
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

        .company-info p {
            margin: 0 0 3px 0;
        }

        .logo {
            text-align: right;
        }

        .company-logo {
            width: 80px;
            height: 60px;
            object-fit: contain;
        }

        .quote-title {
            font-size: 18px;
            font-weight: bold;
            color: #3498db;
            text-transform: uppercase;
            margin: 0;
            text-align: center;
        }

        /* ===== Información Interna ===== */
        .internal-info {
            background: #2c5aa0;
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 11px;
        }

        .internal-badge {
            background: #e74c3c;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        /* ===== Cliente ===== */
        .client-info {
            margin-top: 10px;
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

        /* ===== Secciones ===== */
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #000000;
            margin: 15px 0 8px;
            padding-bottom: 3px;
            border-bottom: 1px solid #bdc3c7;
        }

        /* ===== Tabla de productos DETALLADA ===== */
        table.table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            font-size: 10px;
        }

        table.table th {
            background-color: #2c5aa0;
            color: white;
            border: 1px solid #2c5aa0;
            padding: 6px;
            font-weight: bold;
        }

        table.table td {
            border: 1px solid #bdc3c7;
            padding: 6px;
        }

        table.table tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* Badges para información adicional */
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            color: white;
            font-size: 9px;
            font-weight: bold;
        }
        .badge-danger {
            background-color: #e74c3c;
        }
        .badge-info {
            background-color: #3498db;
        }

        .badge-transporte { background-color: #f39c12; }
        .badge-ganancia { background-color: #27ae60; }
        .badge-iva { background-color: #3498db; }

        /* ===== Totales ===== */
        .totals {
            width: 280px;
            margin-left: auto;
            margin-top: 15px;
            border-collapse: collapse;
            background: #f8f9fa;
            border-radius: 4px;
            overflow: hidden;
        }

        .totals td {
            padding: 6px 12px;
            font-size: 11px;
            border-bottom: 1px solid #ddd;
        }

        .totals tr:last-child td {
            border-top: 2px solid #2c3e50;
            font-weight: bold;
            background-color: #ecf0f1;
        }

        .discount-row { 
            color: #e74c3c; 
            font-weight: bold;
            background-color: #ffeaa7 !important;
        }

        .isr-row { color: #e67e22; }
        .iva-row { color: #3498db; }

        /* ===== Footer ===== */
        .footer {
            margin-top: 25px;
            padding-top: 8px;
            border-top: 1px solid #bdc3c7;
            text-align: center;
            font-size: 9px;
            color: #000000;
        }

        .text-success { color: #27ae60; }
        .text-info { color: #3498db; }
        .text-warning { color: #f39c12; }
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
                <div class="quote-title">COTIZACIÓN INTERNA</div>
            </td>
            <td style="width: 20%; vertical-align: top; text-align: right;">
                <!-- Espacio para información adicional si se necesita -->
            </td>
        </tr>
    </table>

    <!-- INFORMACIÓN INTERNA -->
    <div class="internal-info">
        <strong> VERSIÓN INTERNA</strong> | 
        Fecha: {{ $cotizacion['fecha'] }} | 
        Válido hasta: {{ $cotizacion['valido_hasta'] }}
    </div>

    <!-- INFORMACIÓN DEL CLIENTE -->
    <div class="client-info">
        <table class="client-table">
            <tr>
                <td><strong>Cliente:</strong> {{ $cotizacion['cliente'] ?? 'N/A' }}</td>
                <td><strong>Tipo Cliente:</strong> 
                    @if($cotizacion['tipo_cliente'] === 'persona_fisica')
                        PERSONA FÍSICA
                    @elseif($cotizacion['tipo_cliente'] === 'publico_general')
                        PÚBLICO GENERAL
                    @else
                        PERSONA MORAL <span class="badge" style="background:#e67e22;">ISR APLICADO</span>
                    @endif
                </td>
                @if(!empty($cotizacion['telefono']))
                    <td><strong>Teléfono:</strong> {{ $cotizacion['telefono'] }}</td>
                @endif
                @if(!empty($cotizacion['direccion']))
                    <td><strong>Dirección:</strong> {{ $cotizacion['direccion'] }}</td>
                @endif
            </tr>
        </table>
    </div>

    <!-- DETALLE DE PRODUCTOS (VERSIÓN INTERNA DETALLADA) -->
   <div class="section-title">DETALLE DE PRODUCTOS - INFORMACIÓN INTERNA</div>
<table class="table">
    <thead>
        <tr>
            <th width="6%" class="text-center">Cant</th>
            <th width="8%" class="text-center">CLAVE</th>
            <th width="25%">Producto / Servicio</th>
            <th width="10%" class="text-right">Precio Costo</th>
            <th width="8%" class="text-center">Transp</th>
            <th width="8%" class="text-center">% Gan</th>
            <th width="12%" class="text-right">Precio Sin IVA</th>
            <th width="9%" class="text-center">IVA Unit</th>
            <th width="10%" class="text-right">Precio Final</th>
            <th width="8%" class="text-right">Subtotal</th>
        </tr>
    </thead>
    <tbody>
        @forelse($cotizacion['productos'] as $i => $producto)
            <tr>
                <td class="text-center">{{ $producto['cantidad'] }}</td>
                <td class="text-center">
                    @if(!empty($producto['claveProducto']))
                        <small>{{ $producto['claveProducto'] }}</small>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td>
                    <strong>{{ $producto['nombre'] }}</strong>
                    @if(($producto['descuentoAplicado'] ?? false) && ($producto['precioFinalSinDescuento'] ?? 0) > $producto['precioFinal'])
                        <br><small class="text-danger">¡Incluye descuento!</small>
                    @endif
                </td>
                <td class="text-right">${{ number_format($producto['precioCosto'], 2) }}</td>
                <td class="text-center">
                    @if($producto['aplicaTransporte'] && $producto['transporte'] > 0 && !$producto['sinGanancia'])
                        <span class="badge badge-transporte">${{ number_format($producto['transporte'], 2) }}</span>
                    @else
                        <!-- ✅ DEJAR VACÍO CUANDO NO HAY TRANSPORTE O ES SIN GANANCIA -->
                    @endif
                </td>
                
                <td>
                    @if($producto['sinGanancia'])
                        <span class="badge badge-danger">SIN GANANCIA</span>
                    @else
                        <span class="badge badge-info">{{ $producto['porcentajeGanancia'] }}%</span>
                    @endif
                </td>

                <!-- PRECIO SIN IVA - CON COMPARATIVA -->
                <td class="text-right">
                    @if(($producto['descuentoAplicado'] ?? false) && ($producto['precioSinIvaFinalConDescuento'] ?? $producto['precioSinIvaFinal']) < $producto['precioSinIvaFinal'])
                        <div style="text-decoration: line-through; color: #e74c3c; font-size: 9px;">
                            ${{ number_format($producto['precioSinIvaFinal'], 2) }}
                        </div>
                        <div style="color: #27ae60; font-weight: bold;">
                            ${{ number_format($producto['precioSinIvaFinalConDescuento'] ?? $producto['precioSinIvaFinal'], 2) }}
                        </div>
                        <small class="text-primary" style="font-size: 8px;">
                            Ahorro: ${{ number_format($producto['precioSinIvaFinal'] - ($producto['precioSinIvaFinalConDescuento'] ?? $producto['precioSinIvaFinal']), 2) }}
                        </small>
                    @else
                        ${{ number_format($producto['precioSinIvaFinal'], 2) }}
                    @endif
                </td>

                <td class="text-center">
                    <span class="badge badge-iva">${{ number_format($producto['ivaFinal'], 2) }}</span>
                </td>

                <!-- PRECIO FINAL - CON COMPARATIVA -->
                <td class="text-right">
                    @if(($producto['descuentoAplicado'] ?? false) && ($producto['precioFinalSinDescuento'] ?? 0) > $producto['precioFinal'])
                        <div style="text-decoration: line-through; color: #e74c3c; font-size: 9px;">
                            ${{ number_format($producto['precioFinalSinDescuento'], 2) }}
                        </div>
                        <div style="color: #27ae60; font-weight: bold;">
                            ${{ number_format($producto['precioFinal'], 2) }}
                        </div>
                        <small class="text-primary" style="font-size: 8px;">
                            Ahorro: ${{ number_format($producto['precioFinalSinDescuento'] - $producto['precioFinal'], 2) }}
                        </small>
                    @else
                        <strong>${{ number_format($producto['precioFinal'], 2) }}</strong>
                    @endif
                </td>

                <!-- SUBTOTAL - CON COMPARATIVA -->
                <td class="text-right">
                    @if(($producto['descuentoAplicado'] ?? false) && ($producto['subtotalSinDescuento'] ?? 0) > $producto['subtotal'])
                        <div style="text-decoration: line-through; color: #e74c3c; font-size: 9px;">
                            ${{ number_format($producto['subtotalSinDescuento'], 2) }}
                        </div>
                        <div style="color: #27ae60; font-weight: bold;">
                            ${{ number_format($producto['subtotal'], 2) }}
                        </div>
                        <small class="text-primary" style="font-size: 8px;">
                            Ahorro: ${{ number_format($producto['subtotalSinDescuento'] - $producto['subtotal'], 2) }}
                        </small>
                    @else
                        <strong>${{ number_format($producto['subtotal'], 2) }}</strong>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="10" class="text-center" style="color:#95a5a6;padding:15px;">
                    No hay productos en esta cotización
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

    <!-- RESUMEN DE COTIZACIÓN -->
<!-- RESUMEN DE COTIZACIÓN - VERSIÓN CORREGIDA -->
<!-- RESUMEN DE COTIZACIÓN - VERSIÓN CORREGIDA -->
<div class="section-title">RESUMEN DE COTIZACIÓN</div>
<table class="totals">
    <tr>
        <td><strong>Subtotal:</strong></td>
        <td class="text-right">${{ number_format($cotizacion['subtotalParaPdfInterno'], 2) }}</td>
    </tr>
    <tr class="iva-row">
        <td><strong>IVA 8%:</strong></td>
        <td class="text-right">${{ number_format($cotizacion['subtotalParaPdfInterno'] * 0.08, 2) }}</td>
    </tr>
    
    @if($cotizacion['tipo_cliente'] === 'persona_moral')
    <tr class="isr-row">
        <td><strong>ISR 1.25%:</strong></td>
        <td class="text-right">${{ number_format($cotizacion['isrParaPdfInterno'] ?? 0, 2) }}</td>
    </tr>
    @endif
    
    @if(!empty($cotizacion['descuentoPorcentaje']) && $cotizacion['descuentoPorcentaje'] > 0)
    <tr class="discount-row">
        <td><strong>Descuento ({{ $cotizacion['descuentoPorcentaje'] }}%):</strong></td>
        <td class="text-right">-${{ number_format($cotizacion['totalDescuentoAcumulado'], 2) }}</td>
    </tr>
    @endif
    
    <tr>
        <td><strong>TOTAL:</strong></td>
        <td class="text-right text-success"><strong>
        ${{ number_format($cotizacion['total'], 2) }}
        </strong></td>
    </tr>
</table>

    <!-- FOOTER -->
    <div class="footer">
        <p><strong>INFORMACIÓN INTERNA - USO EXCLUSIVO DE LA EMPRESA</strong></p>
        <p>Precio sujeto al tipo de cambio | Productos disponibles al momento de cotizar</p>
        <p>Cotización válida hasta: {{ $cotizacion['valido_hasta'] }}</p>
        
        @if($cotizacion['tipo_cliente'] === 'persona_moral')
        <p style="color: #e67e22; margin-top: 3px;">
            <strong>* Incluye retención de ISR 1.25% aplicable para Persona Moral</strong>
        </p>
        @endif
    </div>

</body>
</html>