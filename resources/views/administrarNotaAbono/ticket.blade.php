<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ticket {{ $venta->ticket }}</title>

    <style>
        /* ---------------------------------------------------
           ESTILO GENERAL - DISEÑO MINIMALISTA PROFESIONAL
        --------------------------------------------------- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Courier New", monospace !important;
        }

        body {
            width: 80mm;
            margin: 0 auto;
            padding: 4mm;
            font-size: 9px;
            color: #000;
            background: #fff;
        }

        /* ---------------------------------------------------
           ENCABEZADO
        --------------------------------------------------- */
        .header {
            text-align: center;
            margin-bottom: 4mm;
        }

        .company-name {
            font-size: 11px;
            font-weight: bold;
        }

        .company-info {
            font-size: 7px;
            color: #555;
        }

        .ticket-title {
            margin-top: 2mm;
            font-size: 10px;
            font-weight: bold;
            border: 1px solid #000;
            padding: 2px 0;
            display: inline-block;
            width: 100%;
        }

        .ticket-number {
            margin-top: 1mm;
            font-size: 11px;
            font-weight: bold;
        }

        /* ---------------------------------------------------
           SECCIONES
        --------------------------------------------------- */
        .section {
            margin-top: 4mm;
            padding-bottom: 3mm;
            border-bottom: 1px solid #e1e1e1;
        }

        .section-title {
            font-weight: bold;
            margin-bottom: 2mm;
            font-size: 9px;
            border-left: 2px solid #000;
            padding-left: 2mm;
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1mm;
        }

        /* ---------------------------------------------------
           TABLA DE PRODUCTOS
        --------------------------------------------------- */
        table {
            width: 100%;
            font-size: 9px;
        }

        th {
            text-align: left;
            border-bottom: 1px solid #000;
        }

        td {
            padding: 1mm 0;
            vertical-align: top;
        }

        .qty {
            width: 8mm;
            text-align: center;
        }

        .amount {
            text-align: right;
            white-space: nowrap;
        }

        .product-name small {
            font-size: 7px;
            color: #666;
        }

        /* ---------------------------------------------------
           PRECIOS CON IMPUESTOS
        --------------------------------------------------- */
        .price-final {
            font-weight: bold;
            color: #2196F3;
        }

        .price-breakdown {
            font-size: 6px;
            color: #666;
            line-height: 1.1;
        }

        .iva-badge {
            display: inline-block;
            background: #FFEBEE;
            color: #C62828;
            padding: 0 2px;
            border-radius: 1px;
            font-size: 6px;
            margin-right: 2px;
        }

        .isr-badge {
            display: inline-block;
            background: #FFF3E0;
            color: #EF6C00;
            padding: 0 2px;
            border-radius: 1px;
            font-size: 6px;
        }

        /* ---------------------------------------------------
           TIPO DE CLIENTE
        --------------------------------------------------- */
        .client-type {
            font-size: 8px;
            padding: 1px 5px;
            border-radius: 2px;
            margin: 2px auto;
            display: inline-block;
        }

        .persona-moral {
            background: #FF9800;
            color: white;
        }

        .persona-fisica {
            background: #2196F3;
            color: white;
        }

        /* ---------------------------------------------------
           RESUMEN
        --------------------------------------------------- */
        .summary-line {
            display: flex;
            justify-content: space-between;
            margin: 1mm 0;
        }

        .total-box {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 2mm 0;
            margin-top: 1mm;
            font-size: 11px;
            font-weight: bold;
        }

        /* ---------------------------------------------------
           SALDOS
        --------------------------------------------------- */
        .balance-box {
            background: #f5f5f5;
            padding: 3mm;
            border-radius: 2px;
            border: 1px solid #ddd;
        }

        .balance-line {
            display: flex;
            justify-content: space-between;
            margin: 1mm 0;
        }

        .available {
            font-size: 10px;
            font-weight: bold;
            color: #0a0;
        }

        /* ---------------------------------------------------
           BARCODE
        --------------------------------------------------- */
        .barcode-area {
            text-align: center;
            margin-top: 4mm;
        }

        /* ---------------------------------------------------
           PIE DE PÁGINA
        --------------------------------------------------- */
        .footer {
            text-align: center;
            margin-top: 6mm;
            font-size: 7px;
            color: #666;
        }

        /* ---------------------------------------------------
           IMPRESIÓN
        --------------------------------------------------- */
        @media print {
            .no-print { display: none !important; }
            body { padding: 2mm !important; }
        }
    </style>

</head>

<body>

    <!-- HEADER -->
    <div class="header">
        <div class="company-info">Soluciones PC</div>
        <div class="company-info">BLVD ADOLFO LOPEZ MATEOS 110, EJIDO NUEVO MEXICALI, SAN QUINTÍN B.C</div>
        <div class="company-info">RFC: ZARE881013I12</div>
        <div class="company-info">RFC: 6161362976</div>

        <div class="ticket-title">TICKET DE VENTA</div>
        <div class="ticket-number">{{ $venta->ticket }}</div>
        <div class="company-info">{{ $fecha }}</div>
        
    </div>

    <!-- INFORMACIÓN -->
    <div class="section">
        <div class="section-title">INFORMACIÓN</div>

        <div class="row"><span>Cliente:</span><span>{{ strtoupper($venta->cliente->nombre) }}</span></div>
        <div class="row"><span>Nota de abono:</span><span>{{ $venta->notaAbono->folio }}</span></div>

        @if($venta->responsable)
        <div class="row"><span>Responsable:</span><span>{{ $venta->responsable->nombre }}</span></div>
        @endif

        <div class="row"><span>Régimen:</span><span>{{ $venta->cliente->regimen->nombre ?? 'No especificado' }}</span></div>
    </div>
<!-- PRODUCTOS -->
<div class="section">
    <div class="section-title">
        PRODUCTOS 
        @if($hay_devoluciones)
            ({{ count($detalles) }}, {{ $total_productos_devueltos }} con devolución)
        @else
            ({{ count($detalles) }})
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 8mm;">Cant</th>
                <th>Descripción</th>
                <th style="width: 18mm; text-align: right;">Precio c/u</th>
                <th style="width: 15mm; text-align: right;">Total</th>
            </tr>
        </thead>

        <tbody>
            @foreach($detalles as $detalle)
            <tr style="@if($detalle['devuelto']) background-color: #FFF9C4; @endif">
                <td class="qty">
                    <!-- MOSTRAR CANTIDAD ORIGINAL Y DEVUELTA -->
                    @if($detalle['cantidad_devuelta'] > 0)
                        <div style="text-decoration: line-through; color: #F44336; font-size: 7px;">
                            {{ $detalle['cantidad_original'] }}
                        </div>
                        <div style="font-weight: bold; color: #4CAF50;">
                            {{ $detalle['cantidad_neto'] }}
                        </div>
                        <div style="font-size: 6px; color: #F44336;">
                            -{{ $detalle['cantidad_devuelta'] }} devolucion
                        </div>
                    @else
                        <div>{{ $detalle['cantidad_original'] }}</div>
                    @endif
                </td>
                <td class="product-name">
                    <div>{{ $detalle['nombre_producto'] }}</div>
                    
                    @if($detalle['devuelto'])
                        <div style="font-size: 6px; color: #F44336; font-style: italic;">
                            <strong>DEVOLUCIÓN:</strong> {{ $detalle['cantidad_devuelta'] }} unidad(es)
                        </div>
                    @endif
                    
                    @if($es_persona_moral && $detalle['precio_desglose'])
                    <div class="price-breakdown">
                        Base: ${{ number_format($detalle['precio_desglose']['base'], 2) }}
                        <span class="iva-badge">+IVA</span>
                        IVA: ${{ number_format($detalle['precio_desglose']['iva'], 2) }}
                        <span class="isr-badge">-ISR</span>
                        ISR: ${{ number_format($detalle['precio_desglose']['isr'], 2) }}
                    </div>
                    @elseif(!$es_persona_moral && $detalle['precio_desglose'])
                    <div class="price-breakdown">
                        Base: ${{ number_format($detalle['precio_desglose']['base'], 2) }}
                        <span class="iva-badge">+IVA</span>
                    </div>
                    @endif
                </td>
                <td class="amount price-final">
                    <!-- PRECIO FINAL QUE PAGA EL CLIENTE -->
                    ${{ number_format($detalle['precio_con_impuestos'], 2) }}
                    
                    @if($detalle['devuelto'] && $detalle['cantidad_devuelta'] > 0)
                        <div style="font-size: 6px; color: #666;">
                            x {{ $detalle['cantidad_neto'] }} unid.
                        </div>
                    @endif
                </td>
                <td class="amount">
                    @if($detalle['devuelto'])
                        <!-- MOSTRAR TOTAL ORIGINAL TACHADO Y NETO -->
                        <div style="text-decoration: line-through; color: #9E9E9E; font-size: 7px;">
                            ${{ number_format($detalle['total_original'], 2) }}
                        </div>
                        <div style="font-weight: bold; color: #4CAF50;">
                            <!-- TOTAL NETO -->
                            ${{ number_format($detalle['total_neto'], 2) }}
                        </div>
                        <div style="font-size: 6px; color: #F44336;">
                            <!-- DIFERENCIA -->
                            -${{ number_format($detalle['diferencia_item'], 2) }}
                        </div>
                    @else
                        <div>${{ number_format($detalle['total_original'], 2) }}</div>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <!-- RESUMEN DE DEVOLUCIONES SI LAS HAY -->
    @if($hay_devoluciones)
    <div style="margin-top: 3mm; padding: 2mm; background-color: #FFF3E0; border: 1px dashed #FF9800; border-radius: 2px;">
        <div style="font-size: 8px; font-weight: bold; color: #EF6C00; margin-bottom: 1mm;">
            📋 RESUMEN DE DEVOLUCIONES
        </div>
        
        @foreach($detalles->where('cantidad_devuelta', '>', 0) as $detalleDevuelto)
        <div style="font-size: 7px; color: #555; display: flex; justify-content: space-between; margin-bottom: 0.5mm;">
            <span>{{ $detalleDevuelto['nombre_producto'] }}:</span>
            <span>
                {{ $detalleDevuelto['cantidad_devuelta'] }} unidad(es) 
                (-${{ number_format($detalleDevuelto['diferencia_item'], 2) }})
            </span>
        </div>
        @endforeach

        
        <div style="font-size: 7px; color: #555; display: flex; justify-content: space-between; margin-top: 1mm; border-top: 1px solid #FFCC80; padding-top: 1mm;">
            <span><strong>Total devoluciones:</strong></span>
            <span style="color: #F44336; font-weight: bold;">
                -${{ number_format($diferencia_devoluciones, 2) }}
            </span>
        </div>
    </div>
    @endif
</div>

   <!-- RESUMEN DE IMPUESTOS -->
<div class="section">
    <div class="section-title">RESUMEN DE IMPUESTOS</div>

    @if($hay_devoluciones)
    <!-- MOSTRAR MONTOS ORIGINALES TACHADOS -->
    <div style="margin-bottom: 2mm; padding: 1mm; background-color: #F5F5F5; border-radius: 2px;">
        <div style="font-size: 7px; color: #666; margin-bottom: 0.5mm;">Montos originales:</div>
        <div style="display: flex; justify-content: space-between; font-size: 7px; color: #9E9E9E; text-decoration: line-through;">
            <span>Subtotal:</span>
            <span>${{ number_format($detalles->sum('subtotal_original'), 2) }}</span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 7px; color: #9E9E9E; text-decoration: line-through;">
            <span>IVA:</span>
            <span>+ ${{ number_format($detalles->sum('iva_original'), 2) }}</span>
        </div>
        @if($es_persona_moral)
        <div style="display: flex; justify-content: space-between; font-size: 7px; color: #9E9E9E; text-decoration: line-through;">
            <span>ISR:</span>
            <span>- ${{ number_format($detalles->sum('isr_original'), 2) }}</span>
        </div>
        @endif
    </div>
    @endif

    <!-- MONTOS NETOS (DESPUÉS DE DEVOLUCIONES) -->
    <div class="summary-line">
        <span>Subtotal (precio base):</span>
        <span>${{ number_format($subtotal_neto, 2) }}</span>
    </div>
    
    <div class="summary-line">
        <span>IVA ({{ $tasa_iva }}%):</span>
        <span style="color: #C62828;">+ ${{ number_format($iva_neto, 2) }}</span>
    </div>

    @if($es_persona_moral && $isr_neto > 0)
    <div class="summary-line">
        <span>ISR ({{ $tasa_isr }}%):</span>
        <span style="color: #EF6C00;">- ${{ number_format($isr_neto, 2) }}</span>
    </div>
    @endif

    <!-- TOTAL NETO -->
    <div class="summary-line total-box">
        <span>TOTAL NETO</span>
        <span style="color: #4CAF50;">${{ number_format($total_neto, 2) }}</span>
    </div>
    
    @if($hay_devoluciones)
    <div style="font-size: 7px; color: #666; text-align: center; margin-top: 1mm; padding: 1mm; background: #E8F5E9;">
        <strong>Nota:</strong> Montos ajustados por devoluciones. 
        Descuento total: <span style="color: #F44336;">-${{ number_format($detalles->sum('total_original') - $total_neto, 2) }}</span>
    </div>
    @endif
</div>

    <!-- SALDOS -->
    <div class="section">
        <div class="section-title">CONTROL DE SALDOS</div>

        <div class="balance-box">
            <div class="balance-line">
                <span>Saldo anterior:</span>
                <span>${{ number_format($venta->saldo_antes, 2) }}</span>
            </div>

            <div class="balance-line" style="color: #C62828;">
                <span>Retiro por compra:</span>
                <span>- ${{ number_format($venta->total, 2) }}</span>
            </div>
            @if($hay_pago_diferencia)
                <div class="section" style="margin-top:3mm;">
                    <div class="section-title">PAGO DE DIFERENCIA</div>

                    <div class="balance-box" style="border-style:dashed;">
                        @if($monto_efectivo > 0)
                            <div class="balance-line">
                                <span>Efectivo:</span>
                                <span>${{ number_format($monto_efectivo, 2) }}</span>
                            </div>
                        @endif

                        @if($monto_transferencia > 0)
                            <div class="balance-line">
                                <span>Transferencia:</span>
                                <span>${{ number_format($monto_transferencia, 2) }}</span>
                            </div>
                        @endif

                        @if(!empty($referencia_pago))
                            <div class="balance-line" style="font-size:7px; color:#555;">
                                <span>Referencia:</span>
                                <span>{{ $referencia_pago }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
            <!--<div class="balance-line" style="font-weight: bold;">
                <span>Saldo después:</span>
                <span>${{ number_format($venta->saldo_despues, 2) }}</span>
            </div>-->
            <hr>
            <div class="balance-line available">
                <span>Saldo disponible:</span>
                <span>${{ number_format($venta->notaAbono->saldo_actual, 2) }}</span>
            </div>
        </div>
    </div>

    <!-- VERIFICACIÓN (opcional, para debug) -->
    @if(isset($subtotal_verificado) && abs($subtotal_verificado - $venta->subtotal) > 0.01)
    <div style="font-size: 6px; color: #f44336; text-align: center; padding: 1mm; background: #ffebee; margin-top: 2mm;">
        <strong>Nota:</strong> Diferencia en cálculo: ${{ number_format(abs($subtotal_verificado - $venta->subtotal), 2) }}
    </div>
    @endif

    <!-- BARCODE -->
    <div class="barcode-area">
        <div id="barcode" style="font-size:8px; letter-spacing:1px;"></div>
        <div style="font-size:7px; color:#444;">{{ $venta->ticket }}</div>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        <div style="font-weight:bold; font-size:9px;">¡GRACIAS POR SU COMPRA!</div>
        <div style="margin-top:2mm;">
            Transacción #{{ $venta->id }} • Usuario: {{ $usuario }}<br>
            Impreso el {{ date('d/m/Y H:i:s') }}
        </div>
    </div>

</body>
</html>