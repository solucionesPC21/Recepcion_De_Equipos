<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Abono</title>
    <style>
        @page {
            margin: 0;
            padding: 0;
            size: 80mm auto;
        }
        body { 
            font-family: 'Courier New', monospace, 'Arial';
            font-size: 7px;
            width: 70mm;
            margin: 0;
            padding: 2mm;
            line-height: 1;
        }
        .container {
            width: 66mm;
            margin: 0 auto;
        }
        .center { 
            text-align: center;
            margin: 1px 0;
        }
        .right { 
            text-align: right;
        }
        .bold { 
            font-weight: bold;
        }
        .separator {
            border-bottom: 1px dashed #000;
            margin: 2px 0;
            height: 1px;
        }
        .line {
            border-bottom: 1px solid #000;
            margin: 2px 0;
            height: 1px;
        }
        
        /* Sistema de columnas FIXED */
        .row {
            display: table;
            width: 100%;
            margin: 1px 0;
        }
        .col {
            display: table-cell;
            vertical-align: top;
        }
        .col-15 { width: 15%; }
        .col-40 { width: 40%; }
        .col-20 { width: 20%; }
        .col-25 { width: 25%; }
        
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }

        /* Estilo especial para descripciones largas */
        .long-description {
            word-wrap: break-word;
            white-space: normal;
            line-height: 1.1;
            padding: 1px 0;
        }
        
        .product-row {
            margin: 0;
        }
        
        .amount-section {
            margin: 2px 0;
        }
        
        .total-line {
            border-top: 1px solid #000;
            padding-top: 1px;
            margin-top: 2px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Encabezado -->
        <div class="center bold">SOLUCIONES PC</div>
        <div class="center">RFC: ZARE881013I12</div>
        <div class="center">TEL: 6161362976</div>
        <div class="separator"></div>

        <!-- Título -->
        <div class="center bold">RECIBO DE ABONO</div>
        <div class="separator"></div>

        <!-- Información de la venta -->
        <div class="row">
            <div class="col text-left">CLIENTE: {{ $venta->cliente->nombre }}</div>
        </div>
        <div class="row">
            <div class="col text-left">FECHA: {{ $abono->fecha_abono->format('d/m/Y H:i') }}</div>
        </div>
        <div class="row">
            <div class="col text-left">VENTA: #{{ $venta->id }}</div>
        </div>
        <div class="row">
            <div class="col text-left">ABONO: #{{ $abono->id }}</div>
        </div>
        <br>
        <div class="separator"></div>

        <!-- Encabezado de columnas -->
        <div class="row bold">
            <div class="col col-15 text-center">CANT</div>
            <div class="col col-40 text-left">DESCRIPCIÓN</div>
            <div class="col col-20 text-right">PRECIO</div>
            <div class="col col-25 text-right">IMPORTE</div>
        </div>
        <div class="separator"></div>
        
        <!-- Productos -->
        <!-- Productos -->
        @foreach($venta->detalles as $detalle)
            @php
                $concepto = $detalle->concepto;

                $nombreCompleto = trim(
                    $concepto->nombre
                    . (!empty($concepto->marca) ? ' - ' . $concepto->marca : '')
                    . (!empty($concepto->modelo) ? ' ' . $concepto->modelo : '')
                );
            @endphp

            <div class="row product-row">
                <div class="col col-15 text-center">{{ $detalle->cantidad }}</div>

                <div class="col col-40 text-left long-description">
                    {!! nl2br(e($nombreCompleto)) !!}
                </div>

                <div class="col col-20 text-right">
                    ${{ number_format($detalle->precio_unitario, 2) }}
                </div>

                <div class="col col-25 text-right">
                    ${{ number_format($detalle->subtotal, 2) }}
                </div>
            </div>
            @endforeach
        <br>
        <div class="separator"></div>

        <!-- Totales y información de pago -->
        <div class="amount-section">
            <div class="row">
                <div class="col col-60 text-left bold">TOTAL VENTA:</div>
                <div class="col col-40 text-right bold">${{ number_format($venta->total, 2) }}</div>
            </div>
            
            <div class="row">
                <div class="col col-60 text-left">SALDO ANTERIOR:</div>
                <div class="col col-40 text-right">${{ number_format($saldo_anterior, 2) }}</div>
            </div>
            
            <div class="row">
                <div class="col col-60 text-left bold">MONTO ABONADO:</div>
                <div class="col col-40 text-right bold">${{ number_format($abono->monto, 2) }}</div>
            </div>
            
            <div class="total-line">
                <div class="row">
                    <div class="col col-60 text-left bold">NUEVO SALDO:</div>
                    <div class="col col-40 text-right bold">${{ number_format($venta->saldo_restante, 2) }}</div>
                </div>
            </div>
        </div>

        @if(!empty($abono->tipoPago))
        <div class="row">
            <div class="col col-60 text-left">TIPO PAGO:</div>
            <div class="col col-40 text-right">{{ $abono->tipoPago->tipoPago }}</div>
        </div>
        @endif

        <div class="separator"></div>
        <div class="center bold">¡GRACIAS POR SU PREFERENCIA!</div>
    </div>
</body>
</html>