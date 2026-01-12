<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Corte</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 19px;
        }
        h1 {
            text-align: center;
        }

        .header {
            text-align: right;
        }

        .tickets-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .tickets-table th, .tickets-table td {
            padding: 8px;
            text-align: center;
        }

        .tickets-table th {
            background-color: #f2f2f2;
            text-align: center;
        }

        .category-title {
            margin-top: 40px;
            text-align: left;
            font-size: 18px;
            font-weight: bold;
        }

        .total-row {
            font-weight: bold;
            text-align: right;
        }

        .total-cell {
            text-align: right;
        }

        .separator {
            margin-top: 40px;
            margin-bottom: 40px;
            border-top: 2px solid #000000;
        }
        .gastos-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .gastos-table th, .gastos-table td {
        padding: 8px;
        text-align: left;
    }

    .gastos-table th {
        background-color: #f2f2f2;
        text-align: left;
    }

    .total-ingresos {
    color: #196f3d; /* Verde Bootstrap */
    font-weight: bold;
    }

    .total-gastos {
        color: #dc3545; /* Rojo Bootstrap */
        font-weight: bold;
    }
    </style>
</head>
<body>
    <h1>Reporte de Corte</h1>
    <div class="header">
        @if($esCorteDiario)
            <p><strong>Fecha de Corte:</strong> {{ date('d-m-Y', strtotime($fechaInicio)) }}</p>
        @else
            <p><strong>Fecha de inicio:</strong> {{ date('d-m-Y', strtotime($fechaInicio)) }}</p>
            <p><strong>Fecha de fin:</strong> {{ date('d-m-Y', strtotime($fechaFin)) }}</p>
        @endif
    </div>

    <div class="category-section">
        <h2 class="category-title">Mercancía - Efectivo</h2>
        <table class="tickets-table">
            <thead>
                <tr>
                    <th>Cantidad</th>
                    <th>Descripción</th>
                    <th>Pago</th>
                    <!-- Agrega más encabezados según tus necesidades -->
                </tr>
            </thead>
            <tbody>
                @php
                    $totalMercanciaEfectivo = 0; // Inicializa la variable para el total de mercancía pagada en efectivo
                @endphp
                @foreach($tickets as $ticket)
                    @if($ticket->tipoPago->tipoPago == 'EFECTIVO')
                        @foreach($ticket->concepto as $concepto)
                            @if($concepto->nombreConcepto?->categoria?->categoria == 'MERCANCIA')
                                <tr>
                                    <td>{{ $concepto->cantidad }}</td>
                                    <td>
                                        {{ $concepto->nombreConcepto?->nombre ?? '' }}
                                        {{ $concepto->nombreConcepto?->marca ? ' ' . $concepto->nombreConcepto->marca : '' }}
                                        {{ $concepto->nombreConcepto?->modelo ? ' ' . $concepto->nombreConcepto->modelo : '' }}
                                    </td>
                                    <td>${{ number_format($concepto->total, 2) }}</td>
                                    <!-- Aquí puedes agregar más columnas según necesites -->
                                </tr>
                                @php
                                    $totalMercanciaEfectivo += $concepto->total; // Suma el total del concepto al total de mercancía pagada en efectivo
                                @endphp
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </tbody>
        </table>
        <p class="total-row">TOTAL MERCANCÍA - EFECTIVO: <span class="total-cell">${{ number_format($totalMercanciaEfectivo, 2) }}</span></p>
    </div>

    <div class="separator"></div> <!-- Línea separadora -->

    <div class="category-section">
        <h2 class="category-title">Mercancía - Transferencia</h2>
        <table class="tickets-table">
            <thead>
                <tr>
                    <th>Cantidad</th>
                    <th>Descripción</th>
                    <th>Pago</th>
                    <!-- Agrega más encabezados según tus necesidades -->
                </tr>
            </thead>
            <tbody>
                @php
                    $totalMercanciaTransferencia = 0; // Inicializa la variable para el total de mercancía pagada por transferencia
                @endphp
                @foreach($tickets as $ticket)
                    @if($ticket->tipoPago->tipoPago == 'TRANSFERENCIA')
                        @foreach($ticket->concepto as $concepto)
                            @if($concepto->nombreConcepto?->categoria?->categoria == 'MERCANCIA')
                                <tr>
                                    <td>{{ $concepto->cantidad }}</td>
                                    <td>
                                        {{ $concepto->nombreConcepto?->nombre ?? '' }}
                                        {{ $concepto->nombreConcepto?->marca ? ' ' . $concepto->nombreConcepto->marca : '' }}
                                        {{ $concepto->nombreConcepto?->modelo ? ' ' . $concepto->nombreConcepto->modelo : '' }}
                                    </td>
                                    <td>${{ number_format($concepto->total, 2) }}</td>
                                    <!-- Aquí puedes agregar más columnas según necesites -->
                                </tr>
                                @php
                                    $totalMercanciaTransferencia += $concepto->total; // Suma el total del concepto al total de mercancía pagada por transferencia
                                @endphp
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </tbody>
        </table>
        <p class="total-row">TOTAL MERCANCÍA - TRANSFERENCIA: <span class="total-cell">${{ number_format($totalMercanciaTransferencia, 2) }}</span></p>
    </div>

    <div class="separator"></div> <!-- Línea separadora -->

    <div class="category-section">
        <h2 class="category-title">Servicio - Efectivo</h2>
        <table class="tickets-table">
            <thead>
                <tr>
                    <th>Cantidad</th>
                    <th>Descripción</th>
                    <th>Pago</th>
                    <!-- Agrega más encabezados según tus necesidades -->
                </tr>
            </thead>
            <tbody>
                @php
                    $totalServicioEfectivo = 0; // Inicializa la variable para el total de servicio pagado en efectivo
                @endphp
                @foreach($tickets as $ticket)
                    @if($ticket->tipoPago->tipoPago == 'EFECTIVO')
                        @foreach($ticket->concepto as $concepto)
                            @if($concepto->nombreConcepto?->categoria?->categoria == 'SERVICIO')
                                <tr>
                                    <td>{{ $concepto->cantidad }}</td>
                                    <td>{{ $concepto->nombreConcepto->nombre }}</td>
                                    <td>${{ number_format($concepto->total, 2) }}</td>
                                    <!-- Aquí puedes agregar más columnas según necesites -->
                                </tr>
                                @php
                                    $totalServicioEfectivo += $concepto->total; // Suma el total del concepto al total de servicio pagado en efectivo
                                @endphp
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </tbody>
        </table>
        <p class="total-row">TOTAL SERVICIO - EFECTIVO: <span class="total-cell">${{ number_format($totalServicioEfectivo, 2) }}</span></p>
    </div>

    <div class="separator"></div> <!-- Línea separadora -->

    <div class="category-section">
        <h2 class="category-title">Servicio - Transferencia</h2>
        <table class="tickets-table">
            <thead>
                <tr>
                    <th>Cantidad</th>
                    <th>Descripción</th>
                    <th>Pago</th>
                    <!-- Agrega más encabezados según tus necesidades -->
                </tr>
            </thead>
            <tbody>
                @php
                    $totalServicioTransferencia = 0; // Inicializa la variable para el total de servicio pagado por transferencia
                @endphp
                @foreach($tickets as $ticket)
                    @if($ticket->tipoPago->tipoPago == 'TRANSFERENCIA')
                        @foreach($ticket->concepto as $concepto)
                            @if($concepto->nombreConcepto?->categoria?->categoria == 'SERVICIO')
                                <tr>
                                    <td>{{ $concepto->cantidad }}</td>
                                    <td>{{ $concepto->nombreConcepto->nombre }}</td>
                                    <td>${{ number_format($concepto->total, 2) }}</td>
                                    <!-- Aquí puedes agregar más columnas según necesites -->
                                </tr>
                                @php
                                    $totalServicioTransferencia += $concepto->total; // Suma el total del concepto al total de servicio pagado por transferencia
                                @endphp
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </tbody>
        </table>
        <p class="total-row">TOTAL SERVICIO - TRANSFERENCIA: <span class="total-cell">${{ number_format($totalServicioTransferencia, 2) }}</span></p>
    </div>

    <div class="separator"></div> <!-- Línea separadora -->

    <div class="separator"></div> <!-- Línea separadora -->

<div class="category-section">
    <h2 class="category-title">Gastos</h2>
    @if($gastos->isNotEmpty())
        <table class="tickets-table">
            <thead>
                <tr>
                    <th>Descripción</th>
                    <th>Fecha</th>
                    <th>Monto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($gastos as $gasto)
                    <tr>
                        <td>{{ $gasto->descripcion }}</td>
                        <td>{{ date('d-m-Y', strtotime($gasto->fecha)) }}</td>
                        <td>${{ number_format($gasto->monto, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="total-row">TOTAL GASTOS: <span class="total-cell">${{ number_format($gastos->sum('monto'), 2) }}</span></p>
    @else
        <p>No se registraron gastos en este período</p>
    @endif
</div>

<div class="separator"></div> <!-- Línea separadora -->
<!-- Sección de Ventas a Crédito -->
<div class="category-section">
    <h2 class="category-title">Movimientos de Abonos ({{ $fechaInicio }} al {{ $fechaFin }})</h2>
    
    @php
        // Inicializar totales
        $totalEfectivo = 0;
        $totalTransferencia = 0;
        
        // Calcular totales de abonos por tipo de pago
        $abonosEfectivo = $abonos->filter(fn($a) => optional($a->tipoPago)->tipoPago == 'EFECTIVO');
        $abonosTransferencia = $abonos->filter(fn($a) => optional($a->tipoPago)->tipoPago == 'TRANSFERENCIA');
        
        $totalAbonosEfectivo = $abonosEfectivo->sum('monto');
        $totalAbonosTransferencia = $abonosTransferencia->sum('monto');
        
        // Sumar a los totales generales
        $totalEfectivo += $totalAbonosEfectivo;
        $totalTransferencia += $totalAbonosTransferencia;
    @endphp

    <!-- Subsección de Ventas a Crédito -->

    <div class="separator-light"></div>

    <!-- Subsección de Abonos -->
    <h3 class="subcategory-title">Abonos Recibidos</h3>
    @if($abonos->isNotEmpty())
        <table class="tickets-table">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th>Tipo de Pago</th>
                    <th>Monto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($abonos as $abono)
                    <tr>
                        <td>{{ optional($abono->venta->cliente)->nombre ?? 'N/A' }}</td>
                        <td>{{ $abono->fecha_abono->format('d/m/Y H:i') }}</td>
                         <td>{{ optional($abono->tipoPago)->tipoPago ?? 'No especificado' }}</td>
                        <td>${{ number_format($abono->monto, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>No se registraron abonos en este período</p>
    @endif

    <div class="separator-light"></div>

    <!-- Resumen Consolidado -->
    <div class="summary-container">
        <div class="payment-method-summary">
            <h4>Efectivo</h4>
            <p class="total-row">ABONOS EN EFECTIVO: <span class="total-cell">${{ number_format($totalAbonosEfectivo, 2) }}</span></p>
        </div>
        
        <div class="payment-method-summary">
            <h4>Transferencia</h4>
            <p class="total-row">ABONOS POR TRANSFERENCIA: <span class="total-cell">${{ number_format($totalAbonosTransferencia, 2) }}</span></p>
        </div>
    </div>

    <div class="grand-total">
        <p class="total-row">TOTAL GENERAL DE ABONOS: <span class="total-cell">${{ number_format($abonos->sum('monto'), 2) }}</span></p>
    </div>
</div>
<div class="separator"></div> <!-- Línea separadora -->


<!--Esta seccion es para productos que se han borrado de la BD pero estan guardados en tickets-->
{{-- ========================= --}}
{{-- SIN CATEGORÍA - EFECTIVO --}}
{{-- ========================= --}}
@php
    $totalSinCategoriaEfectivo = 0;
    $conceptosSinCategoriaEfectivo = [];
@endphp

@foreach($tickets as $ticket)
    @if(optional($ticket->tipoPago)->tipoPago === 'EFECTIVO')
        @foreach($ticket->concepto as $concepto)
            @php
                $categoria = optional($concepto->nombreConcepto?->categoria)->categoria;
            @endphp
            @if(empty($categoria))
                @php
                    $conceptosSinCategoriaEfectivo[] = $concepto;
                    $totalSinCategoriaEfectivo += $concepto->total;
                @endphp
            @endif
        @endforeach
    @endif
@endforeach

@if(count($conceptosSinCategoriaEfectivo) > 0)
    <div class="category-section">
        <h2 class="category-title">Sin Categoría - Efectivo</h2>
        <table class="tickets-table">
            <thead>
                <tr>
                    <th>Cantidad</th>
                    <th>Descripción</th>
                    <th>Pago</th>
                </tr>
            </thead>
            <tbody>
                @foreach($conceptosSinCategoriaEfectivo as $concepto)
                    <tr>
                        <td>{{ $concepto->cantidad }}</td>
                        <td>
                            {{ $concepto->nombreConcepto?->nombre ?? 'Sin descripción' }}
                            {{ $concepto->nombreConcepto?->marca ? ' ' . $concepto->nombreConcepto->marca : '' }}
                            {{ $concepto->nombreConcepto?->modelo ? ' ' . $concepto->nombreConcepto->modelo : '' }}
                        </td>
                        <td>${{ number_format($concepto->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="total-row">
            TOTAL SIN CATEGORÍA - EFECTIVO: 
            <span class="total-cell">${{ number_format($totalSinCategoriaEfectivo, 2) }}</span>
        </p>
    </div>

    <div class="separator"></div>
@endif


{{-- ============================= --}}
{{-- SIN CATEGORÍA - TRANSFERENCIA --}}
{{-- ============================= --}}
@php
    $totalSinCategoriaTransferencia = 0;
    $conceptosSinCategoriaTransferencia = [];
@endphp

@foreach($tickets as $ticket)
    @if(optional($ticket->tipoPago)->tipoPago === 'TRANSFERENCIA')
        @foreach($ticket->concepto as $concepto)
            @php
                $categoria = optional($concepto->nombreConcepto?->categoria)->categoria;
            @endphp
            @if(empty($categoria))
                @php
                    $conceptosSinCategoriaTransferencia[] = $concepto;
                    $totalSinCategoriaTransferencia += $concepto->total;
                @endphp
            @endif
        @endforeach
    @endif
@endforeach

@if(count($conceptosSinCategoriaTransferencia) > 0)
    <div class="category-section">
        <h2 class="category-title">Sin Categoría - Transferencia</h2>
        <table class="tickets-table">
            <thead>
                <tr>
                    <th>Cantidad</th>
                    <th>Descripción</th>
                    <th>Pago</th>
                </tr>
            </thead>
            <tbody>
                @foreach($conceptosSinCategoriaTransferencia as $concepto)
                    <tr>
                        <td>{{ $concepto->cantidad }}</td>
                        <td>
                            {{ $concepto->nombreConcepto?->nombre ?? 'Sin descripción' }}
                            {{ $concepto->nombreConcepto?->marca ? ' ' . $concepto->nombreConcepto->marca : '' }}
                            {{ $concepto->nombreConcepto?->modelo ? ' ' . $concepto->nombreConcepto->modelo : '' }}
                        </td>
                        <td>${{ number_format($concepto->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="total-row">
            TOTAL SIN CATEGORÍA - TRANSFERENCIA: 
            <span class="total-cell">${{ number_format($totalSinCategoriaTransferencia, 2) }}</span>
        </p>
    </div>
@endif


<div class="separator"></div>
@if($totalDiferenciaEfectivo > 0)
<div class="separator"></div>

<div class="category-section">
    <h2 class="category-title">Pagos de Diferencia Nota Abonos– Efectivo</h2>

    <table class="tickets-table">
        <thead>
            <tr>
                <th>Venta</th>
                <th>Fecha</th>
                <th>Monto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pagosDiferencia->where('monto_efectivo', '>', 0) as $pago)
                <tr>
                    <td>#{{ $pago->venta->ticket }}</td>
                    <td>{{ $pago->created_at->format('d/m/Y H:i') }}</td>
                    <td>${{ number_format($pago->monto_efectivo, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="total-row">
        TOTAL DIFERENCIA EFECTIVO:
        <span class="total-cell">
            ${{ number_format($totalDiferenciaEfectivo, 2) }}
        </span>
    </p>
</div>
@endif

<div class="separator"></div>
@if($totalDiferenciaTransferencia > 0)
<div class="separator"></div>

<div class="category-section">
    <h2 class="category-title">Pagos de Diferencia Nota Abonos – Transferencia</h2>

    <table class="tickets-table">
        <thead>
            <tr>
                <th>Venta</th>
                <th>Fecha</th>
                <th>Monto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pagosDiferencia->where('monto_transferencia', '>', 0) as $pago)
                <tr>
                    <td>#{{ $pago->venta_id }}</td>
                    <td>{{ $pago->created_at->format('d/m/Y H:i') }}</td>
                    <td>${{ number_format($pago->monto_transferencia, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="total-row">
        TOTAL DIFERENCIA TRANSFERENCIA:
        <span class="total-cell">
            ${{ number_format($totalDiferenciaTransferencia, 2) }}
        </span>
    </p>
</div>
@endif




<div class="category-section">
    @php
        $totalFinal = $totalMercanciaEfectivo + $totalServicioEfectivo + $totalAbonosEfectivo + $totalSinCategoriaEfectivo + $totalDiferenciaEfectivo;
        $totalGastos = $gastos->isNotEmpty() ? $gastos->sum('monto') : 0; // Inicializa $totalGastos
        $totalConGastos = $totalFinal - $totalGastos;
        $colorNeto = $totalConGastos < 0 ? 'color: #FF0000;' : 'color: #196f3d;'; // Rojo si negativo, verde si positivo
        $usuario = auth()->user()->nombre;
    @endphp
    <h2 class="category-title">Total Final (Efectivo)</h2>
    <p class="total-row total-ingresos">
    TOTAL INGRESOS (EFECTIVO): <span class="total-cell">${{ number_format($totalFinal, 2) }}</span>
    </p>
    <p class="total-row total-gastos">
        TOTAL GASTOS: <span class="total-cell">-${{ number_format($totalGastos, 2) }}</span>
    </p>
    <p class="total-row" style="font-size: 1.2em; {{ $colorNeto }} font-weight: bold;">
        TOTAL NETO: <span class="total-cell">${{ number_format($totalConGastos, 2) }}</span>
    </p>
    <p class="total-row">CORTE GENERADO POR: <span class="total-cell">{{ $usuario }}</span></p>
</div>
    
</body>
</html>
