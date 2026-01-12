@extends('layouts.administrarNotaAbono.app-master')

@section('content')
<div class="container-fluid">
    <!-- Header Principal Mejorado -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1 text-dark fw-semibold">
                        <i class="fas fa-file-invoice-dollar text-primary me-2"></i>Nota de Abono #{{ $notaAbono->folio }}
                    </h4>
                    <p class="text-muted mb-0">Cliente: <span class="text-dark">{{ $notaAbono->cliente->nombre }}</span></p>
                </div>
                <div class="text-end">
                    <div class="d-flex align-items-center gap-3">
                        <div class="text-end">
                            <div class="h4 mb-0 text-success fw-bold">${{ number_format($notaAbono->saldo_actual, 2) }}</div>
                            <small class="text-muted">Saldo disponible</small>
                        </div>
                        @if($notaAbono->estado === 'activa')
                        <button class="btn btn-primary px-4" data-bs-toggle="modal" data-bs-target="#modalRealizarVenta">
                            <i class="fas fa-cash-register me-2"></i>Realizar Venta
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Panel de Resumen -->
        <div class="col-lg-3">
            <!-- Tarjeta de Información del Cliente -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 text-dark fw-semibold">
                        <i class="fas fa-user-tie text-primary me-2"></i>Información del Cliente
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block">Nombre completo</small>
                        <span class="fw-semibold">{{ $notaAbono->cliente->nombre }}</span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Régimen fiscal</small>
                        <span class="fw-semibold">{{ $notaAbono->cliente->regimen->nombre }}</span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">RFC</small>
                        <span class="fw-semibold">{{ $notaAbono->cliente->rfc ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <small class="text-muted d-block">Contacto</small>
                        <span class="fw-semibold">{{ $notaAbono->cliente->telefono ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>

            <!-- Tarjeta de Resumen Financiero -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 text-dark fw-semibold">
                        <i class="fas fa-chart-pie text-primary me-2"></i>Resumen Financiero
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block">Abono inicial</small>
                        <span class="fw-semibold text-success">${{ number_format($notaAbono->abono_inicial, 2) }}</span>
                    </div>
                     <div class="mb-3">
                        <small class="text-muted d-block">Total Abonos</small>
                        <!-- Cambiar $notaAbono->abono_inicial por $totalAbonos -->
                        <span class="fw-semibold text-success">${{ number_format($totalAbonos, 2) }}</span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Saldo actual</small>
                        <span class="fw-semibold text-primary">${{ number_format($notaAbono->saldo_actual, 2) }}</span>
                    </div>
                    
                
                    <div class="mb-3">
                        <small class="text-muted d-block">Estado</small>
                        <span class="badge bg-success rounded-pill">{{ ucfirst($notaAbono->estado) }}</span>
                    </div>
                    <div>
                        <small class="text-muted d-block">Fecha apertura</small>
                        <span class="fw-semibold">{{ \Carbon\Carbon::parse($notaAbono->fecha_apertura)->format('d/m/Y') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel Principal - Historial de Ventas -->
        <div class="col-lg-9">
            <!-- Header del Historial -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 text-dark fw-semibold">
                            <i class="fas fa-receipt text-primary me-2"></i>Historial de Ventas
                        </h6>
                        <!-- Agrega esto después del header -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    <div class="d-flex flex-wrap gap-2">
                                        <span class="badge bg-success">Completadas: {{ $ventas->where('estado', 'completada')->count() }}</span>
                                        <span class="badge bg-warning">Totalmente Devuelta: {{ $ventas->where('estado', 'totalmente_devuelta')->count() }}</span>
                                        <span class="badge bg-danger">Canceladas: {{ $ventas->where('estado', 'cancelada')->count() }}</span>
                                        <span class="badge bg-info">Parcialmente Devuelta: {{ $ventas->where('estado', 'parcialmente_devuelta')->count() }}</span>
                                    </div>
                                </div>
                            </div>
                        <div class="text-muted small">
                            {{ $ventas->count() }} ventas registradas
                        </div>
                    </div>
                </div>
            </div>
            <!-- Lista de Ventas -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    @if($ventas->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0">Ticket</th>
                                        <th class="border-0">Fecha</th>
                                        <th class="border-0 text-end">Subtotal</th>
                                        <th class="border-0 text-end">IVA</th>
                                        <th class="border-0 text-end">ISR</th>
                                        <th class="border-0 text-end">Total</th>
                                        <th class="border-0 text-center">Estado</th>
                                        <th class="border-0 text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($ventas as $venta)
                                    <tr>
                                        <td class="fw-semibold">{{ $venta->ticket }}</td>
                                        <td>{{ $venta->created_at->format('d/m/Y H:i') }}</td>
                                       <td class="text-end">
                                            ${{ number_format($venta->subtotal_visible, 2) }}
                                        </td>

                                        <td class="text-end">
                                            ${{ number_format($venta->iva_visible, 2) }}
                                        </td>

                                        <td class="text-end">
                                            ${{ number_format($venta->isr_visible, 2) }}
                                        </td>

                                        <td class="text-end fw-bold text-success">
                                            ${{ number_format($venta->total_visible, 2) }}
                                        </td>

                                        <td class="text-center">
                                            @switch($venta->estado)
                                                @case('completada')
                                                    <span class="badge bg-success rounded-pill">Completada</span>
                                                    @break
                                                @case('parcialmente_devuelta')
                                                   <span class="badge bg-info rounded-pill">Parcialmente Devuelto</span>
                                                    @break
                                                @case('totalmente_devuelta')
                                                    <span class="badge bg-warning rounded-pill">Totalmente Devuelto</span>
                                                    @break
                                                @case('cancelada')
                                                    <span class="badge bg-danger rounded-pill">Cancelada</span>
                                                    @break
                                                @default
                                                    <span class="badge bg-secondary rounded-pill">{{ $venta->estado }}</span>
                                            @endswitch
                                            
                                            <!-- Si está cancelada, muestra fecha y motivo en tooltip -->
                                            @if($venta->estado == 'cancelada')
                                                <i class="fas fa-info-circle ms-1 text-muted" 
                                                data-bs-toggle="tooltip" 
                                                title="Cancelada el {{ $venta->updated_at?->format('d/m/Y H:i') }}: {{ $venta->estado }}"></i>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <!-- Botón para ver/descargar PDF - Siempre visible -->
                                                <a href="{{ route('ventas.pdf', $venta->id) }}" 
                                                class="btn btn-outline-primary" 
                                                title="Ver PDF"
                                                target="_blank">
                                                    <i class="fas fa-file-pdf"></i>
                                                </a>
                                                
                                                <!-- Botón para reimprimir - Solo si NO está cancelada -->
                                                @if($venta->estado != 'cancelada' && $venta->estado != 'totalmente_devuelta' && $venta->estado != 'parcialmente_devuelta')
                                                    <form action="{{ route('ventas.reimprimir', $venta->id) }}" 
                                                        method="POST" 
                                                        class="d-inline">
                                                        @csrf
                                                        <button type="submit" 
                                                                class="btn btn-outline-warning" 
                                                                title="Reimprimir ticket"
                                                                onclick="return confirm('¿Reimprimir ticket {{ $venta->ticket }}?')">
                                                            <i class="fas fa-print"></i>
                                                        </button>
                                                    </form>
                                                @endif

                                                <!-- Botón para ver historial de devoluciones -->
                                                <button class="btn btn-outline-info" 
                                                        title="Ver devoluciones"
                                                        onclick="mostrarHistorialDevoluciones({{ $venta->id }}, '{{ $venta->ticket }}', '{{ $venta->cliente->nombre }}')">
                                                    <i class="fas fa-history"></i>
                                                </button>

                                                <!-- Botón para devolver productos - Solo si NO está cancelada -->
                                                @if($venta->estado != 'cancelada' && $venta->estado != 'totalmente_devuelta' && $notaAbono->estado === 'activa')
                                                     <button class="btn btn-outline-warning" 
                                                            title="Devolver productos"
                                                            onclick="mostrarModalDevolucion({{ $venta->id }}, '{{ $venta->ticket }}', '{{ $venta->cliente->nombre }}')">
                                                        <i class="fas fa-undo"></i>
                                                    </button>
                                                @endif

                                               
                                                
                                                <!-- Botón para cancelar venta - Solo si NO está cancelada -->
                                                @if($venta->estado != 'cancelada' && $venta->estado != 'totalmente_devuelta'  && $notaAbono->estado === 'activa')
                                                    <button class="btn btn-outline-danger" 
                                                            title="Cancelar venta"
                                                            onclick="mostrarModalCancelacion({{ $venta->id }}, '{{ $venta->ticket }}')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                @else
                                                    <!-- Si ya está cancelada, muestra botón deshabilitado o información -->
                                                    <button class="btn btn-outline-secondary" disabled title="Venta ya cancelada">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <!-- FOOTER CON TOTALES ACUMULADOS -->
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="2" class="text-end">TOTALES ACUMULADOS:</th>
                                        <th class="text-end">${{ number_format($notaAbono->subtotal_acumulado, 2) }}</th>
                                        <th class="text-end">${{ number_format($notaAbono->iva_calculado, 2) }}</th>
                                        <th class="text-end">${{ number_format($notaAbono->isr_calculado, 2) }}</th>
                                        <th class="text-end text-success">${{ number_format($notaAbono->total_con_impuestos, 2) }}</th>
                                        <th colspan="2"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                       <!-- PAGINACIÓN -->
@if($ventas->hasPages())
<div class="card-footer bg-white border-top">
    <div class="d-flex justify-content-between align-items-center">
        <div class="text-muted small">
            <span class="fw-semibold text-dark">{{ $ventas->firstItem() }}-{{ $ventas->lastItem() }}</span>
            de <span class="fw-semibold text-primary">{{ $ventas->total() }}</span> ventas
        </div>
        
        <div class="d-flex align-items-center gap-2">
            @if (!$ventas->onFirstPage())
                <a href="{{ $ventas->previousPageUrl() }}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-chevron-left"></i>
                </a>
            @endif

            <span class="mx-2 text-muted small">
                Página {{ $ventas->currentPage() }} de {{ $ventas->lastPage() }}
            </span>

            @if ($ventas->hasMorePages())
                <a href="{{ $ventas->nextPageUrl() }}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-chevron-right"></i>
                </a>
            @endif
        </div>
    </div>
</div>
@endif

                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay ventas registradas</h5>
                            <p class="text-muted">Realiza la primera venta usando el botón "Realizar Venta"</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Resumen de Impuestos y Total -->
          @if($ventas->count() > 0)
<div class="card mt-4 border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <h6 class="mb-0 fw-semibold text-dark">
            <i class="fas fa-percentage text-primary me-2"></i>
            Desglose de Impuestos Acumulados
        </h6>
    </div>

    <div class="card-body">
        <div class="row align-items-stretch">
            
            <!-- Bloque de subtotales e impuestos -->
            <div class="col-md-8">
                <div class="row">
                    
                    <!-- Subtotal -->
                    <div class="col-md-6 mb-3">
                        <small class="text-muted d-block">Subtotal acumulado</small>
                        <span class="fw-semibold fs-5">
                            ${{ number_format($notaAbono->subtotal_acumulado, 2) }}
                        </span>
                    </div>

                    <!-- IVA -->
                    <div class="col-md-6 mb-3">
                        <small class="text-muted d-block">
                            IVA ({{ $notaAbono->cliente->regimen->iva }}%) acumulado
                        </small>
                        <span class="fw-semibold fs-5 text-success">
                            ${{ number_format($notaAbono->iva_calculado, 2) }}
                        </span>
                    </div>

                    <!-- ISR -->
                    <div class="col-md-6 mb-3">
                        <small class="text-muted d-block">
                            ISR ({{ $notaAbono->cliente->regimen->isr }}%) acumulado
                        </small>
                        <span class="fw-semibold fs-5 text-info">
                            ${{ number_format($notaAbono->isr_calculado, 2) }}
                        </span>
                    </div>

                </div>
            </div>

            <!-- Total general -->
            <div class="col-md-4">
                <div class="h-100 border-start ps-4 d-flex align-items-center">
                    <div>
                        <small class="text-muted d-block">
                            Total general con impuestos
                        </small>
                        <span class="fw-bold fs-3 text-success">
                            ${{ number_format($notaAbono->total_con_impuestos, 2) }}
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endif

        </div>
    </div>
</div>
<!-- Modal para Realizar Venta -->
 <div class="modal fade" id="modalRealizarVenta" tabindex="-1" aria-hidden="true" 
     data-cliente-id="{{ $notaAbono->cliente->id }}" 
     data-nota-abono-id="{{ $notaAbono->id ?? '' }}"
     data-saldo-actual="{{ $notaAbono->saldo_actual ?? 0 }}" 
     data-bs-backdrop="static" 
     data-bs-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom">
                <h5 class="modal-title fw-semibold">
                    <i class="fas fa-cash-register text-primary me-2"></i>Realizar Venta
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Responsable de la Venta -->
                <div class="mb-4">
                    <label class="form-label fw-semibold">Responsable de la venta <span class="text-danger">*</span></label>
                    <div class="position-relative">
                        <input type="text" class="form-control" id="responsableVenta" 
                            placeholder="Escribe el nombre del responsable..." required>
                        
                        <!-- Indicador visual de estado -->
                        <div class="valid-feedback">
                            <i class="fas fa-check-circle me-1"></i>Responsable válido
                        </div>
                        <div class="invalid-feedback">
                            Por favor selecciona o registra un responsable
                        </div>

                        <input type="hidden" id="responsable_id" name="responsable_id">
                        
                        <div id="sugerenciasResponsable" 
                            class="dropdown-menu w-100" 
                            style="display: none; max-height: 200px; overflow-y: auto;">
                        </div>
                    </div>

                    <div class="form-text">
                        <span id="estadoResponsable" class="text-muted">Ingresa el nombre de quien recibe los productos</span>
                    </div>
                    <div id="botonRegistrarResponsable" class="mt-2" style="display: none;">
                        <button type="button" class="btn btn-sm btn-outline-success" id="btnRegistrarResponsable">
                            <i class="fas fa-user-plus me-1"></i>Registrar nuevo responsable
                        </button>
                    </div>
                </div>

                <!-- Búsqueda de Productos -->
                <div class="mb-4">
                    <label class="form-label fw-semibold">Buscar producto</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="buscarProducto" 
                               placeholder="Ingresa nombre o código del producto...">
                        <button class="btn btn-outline-primary" type="button" id="btnBuscar">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>

                <!-- Resultados de Búsqueda -->
                <div id="resultadosBusqueda" class="mb-4" style="display: none;">
                    <h6 class="fw-semibold mb-3">Productos encontrados</h6>
                    <div class="list-group" id="listaProductos"></div>
                </div>

                <!-- Productos Seleccionados -->
                <div class="mb-4">
                    <h6 class="fw-semibold mb-3">Productos en la venta</h6>
                    <div id="listaProductosSeleccionados">
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-cart-plus fa-2x mb-2"></i>
                            <p>No hay productos agregados</p>
                        </div>
                    </div>
                </div>
                
                <!-- Saldo oculto para JS -->
                <input type="hidden" id="saldoActualCliente" value="{{ $notaAbono->saldo_actual ?? 0 }}">
                
                <!-- ============================================== -->
                <!-- SECCIÓN DE PAGO MIXTO COMPLETO (NUEVO) -->
                <!-- ============================================== -->
                <div id="opcionPagoMixto" class="mt-3 p-3 border border-danger rounded bg-light shadow-sm" style="display: none;">
                    <h6 class="fw-semibold mb-2 text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>Saldo Insuficiente
                    </h6>
                    <p class="mb-2 small text-muted">
                        El saldo disponible no cubre el total de la compra. 
                        <strong>Diferencia: <span id="diferenciaPago">$0.00</span></strong>
                    </p>
                    
                    
             
               <div class="mb-2" id="contenedorCheckPagoMixto">
                <div class="form-check p-2 rounded border border-danger bg-warning-subtle">
                    <input class="form-check-input" type="checkbox" id="habilitarPagoMixto">
                    <label class="form-check-label fw-semibold" for="habilitarPagoMixto">
                        <i class="fas fa-money-bill-wave me-1"></i>
                        Completar pago con efectivo / transferencia
                    </label>
                </div>

                <small class="text-danger fw-semibold d-block mt-1" id="mensajePagoMixto">
                    <i class="fas fa-arrow-up me-1"></i>
                    Seleccione esta opción para continuar con la venta
                </small>
            </div>


                    
                    <div id="detallePagoMixto" class="mt-2" style="display: none;">
                        <div class="row g-2">
                            <!-- Pago con saldo -->
                            <div class="col-md-6">
                                <label class="form-label small">Pago con saldo</label>
                                <input type="text" class="form-control form-control-sm" 
                                       id="pagoSaldo" readonly>
                            </div>
                            
                            <!-- Monto a pagar -->
                            <div class="col-md-6">
                                <label class="form-label small">Monto a pagar *</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" 
                                           id="pagoEfectivo" min="0.01" step="0.01"
                                           placeholder="0.00" required>
                                </div>
                            </div>
                            
                            <!-- Tipo de pago -->
                            <div class="col-md-6">
                                <label class="form-label small">Tipo de pago *</label>
                                <select class="form-select form-select-sm" id="tipoPagoCierre" required>
                                    <option value="">Seleccionar...</option>
                                    <!-- Opciones dinámicas desde backend -->
                                    @if(isset($tipoPagos) && count($tipoPagos) > 0)
                                    
                                        @foreach($tipoPagos as $tipo)
                                            <option value="{{ $tipo->id }}" 
                                                    data-es-transferencia="{{ $tipo->nombre == 'Transferencia' ? '1' : '0' }}">
                                                {{ $tipo->tipoPago }}
                                            </option>
                                        @endforeach
                                    @else
                                        <!-- Valores por defecto si no vienen del backend -->
                                        <option value="1" data-es-transferencia="0">Efectivo</option>
                                        <option value="2" data-es-transferencia="1">Transferencia</option>
                                        <option value="3" data-es-transferencia="0">Tarjeta</option>
                                    @endif
                                </select>
                            </div>
                            
                            <!-- Referencia (mostrar solo si es transferencia) -->
                            <div class="col-md-6" id="referenciaContainer" style="display: none;">
                                <label class="form-label small">Referencia *</label>
                                <input type="text" class="form-control form-control-sm" 
                                       id="referenciaPago"
                                       placeholder="Ej: Transferencia #12345">
                            </div>
                            
                            <!-- Observaciones -->
                            <div class="col-12 mt-2">
                                <label class="form-label small">Observaciones del pago</label>
                                <textarea class="form-control form-control-sm" 
                                          id="observacionesCierre" 
                                          rows="2"
                                          placeholder="Ej: Cliente pagó diferencia en efectivo, recibo #001..."></textarea>
                            </div>
                        </div>
                        
                        <!-- Advertencia de cierre -->
                        <div class="alert alert-warning mt-2 mb-0 py-2 small" id="advertenciaCierre" style="display: none;">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            <strong>¡ATENCIÓN!</strong> Esta venta <strong>cerrará la nota de abono</strong> porque el saldo se agotará completamente.
                        </div>
                    </div>
                </div>
                <!-- ============================================== -->
                
                <!-- Desglose de Impuestos y Total -->
                <div class="border rounded p-3 bg-light">
                    <!-- Información del régimen -->
                    <div class="row mb-2">
                        <div class="col-12">
                            <small class="text-muted">
                                <i class="fas fa-user-tag me-1"></i>
                                Régimen: <span id="regimenNombre" class="fw-semibold">General</span>
                                <span id="infoTipoCliente" class="ms-2 badge bg-info">Persona Física</span>
                            </small>
                        </div>
                    </div>
                    
                    <!-- Precio con IVA (para referencia) -->
                    <div class="row mb-2" id="rowTotalConIVA">
                        <div class="col-6">
                            <span class="fw-semibold">
                                <small>Total con IVA:</small>
                            </span>
                        </div>
                        <div class="col-6 text-end">
                            <span id="modalTotalConIVA" class="fw-semibold">
                                <small>$0.00</small>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Subtotal (precio sin IVA) -->
                    <div class="row mb-2">
                        <div class="col-6">
                            <span class="fw-semibold">
                                Subtotal (sin IVA):
                                <br>
                                <small class="text-muted" id="infoSubtotal"></small>
                            </span>
                        </div>
                        <div class="col-6 text-end">
                            <span id="modalSubtotal" class="fw-semibold">$0.00</span>
                        </div>
                    </div>
                    
                    <!-- IVA Calculado (Dinámico según régimen) -->
                    <div class="row mb-2">
                        <div class="col-6">
                            <span class="fw-semibold text-danger">
                                IVA (<span id="tasaIvaPorcentaje">0%</span>):
                                <br>
                                <small class="text-muted" id="infoIVA">Incluido en el precio</small>
                            </span>
                        </div>
                        <div class="col-6 text-end">
                            <span id="modalIva" class="fw-semibold text-danger">$0.00</span>
                        </div>
                    </div>
                    
                    <!-- ISR Calculado (Solo para personas morales) -->
                    <div class="row mb-2" id="rowISR">
                        <div class="col-6">
                            <span class="fw-semibold text-warning">
                                ISR (<span id="tasaIsrPorcentaje">0%</span>):
                                <br>
                                <small class="text-muted" id="infoISR">Se resta del total</small>
                            </span>
                        </div>
                        <div class="col-6 text-end">
                            <span id="modalIsr" class="fw-semibold text-warning">$0.00</span>
                            <span id="badgeRestaISR" class="badge bg-danger ms-1">-</span>
                        </div>
                    </div>
                    
                    <!-- Línea separadora -->
                    <hr class="my-2">
                    
                    <!-- Total final -->
                    <div class="row">
                        <div class="col-6">
                            <span class="fw-bold fs-6">
                                TOTAL A PAGAR:
                                <br>
                                <small class="text-muted" id="formulaTotal"></small>
                            </span>
                        </div>
                        <div class="col-6 text-end">
                            <span id="modalTotal" class="fw-bold fs-5 text-success">$0.00</span>
                        </div>
                    </div>
                    
                    <!-- Saldos del cliente -->
                    <div class="row mt-3 pt-2 border-top">
                        <div class="col-6">
                            <small class="text-muted">
                                <i class="fas fa-wallet me-1"></i>
                                Saldo antes:
                            </small>
                        </div>
                        <div class="col-6 text-end">
                            <small id="modalSaldoAntes" class="fw-semibold">$0.00</small>
                        </div>
                        <div class="col-6 mt-1">
                            <small class="text-muted">
                                <i class="fas fa-wallet me-1"></i>
                                Saldo después:
                            </small>
                        </div>
                        <div class="col-6 text-end mt-1">
                            <small id="modalSaldoDespues" class="fw-semibold text-danger">$0.00</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnConfirmarVentaModal" disabled>
                    <i class="fas fa-check me-2"></i>Confirmar Venta
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Modal de Confirmación de Venta -->
<div class="modal fade" id="modalConfirmarVenta" tabindex="-1" aria-hidden="true"
     data-bs-backdrop="static" 
     data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-semibold">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirmar Venta
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <i class="fas fa-cash-register fa-3x text-warning mb-3"></i>
                    <h5 class="fw-semibold">¿Estás seguro de realizar esta venta?</h5>
                    <p class="text-muted">Esta acción registrará la venta y descontará el monto del saldo disponible.</p>
                </div>
                
                <div class="row">
                    <!-- Columna izquierda - Resumen financiero -->
                    <div class="col-md-12">
                        <!-- Resumen de la venta -->
                        <div class="card border-warning h-100">
                            <div class="card-header bg-warning bg-opacity-10 py-2">
                                <h6 class="mb-0 fw-semibold"><i class="fas fa-chart-bar me-2"></i>Resumen Financiero</h6>
                            </div>
                            <div class="card-body">
                                <div class="row small mb-2">
                                    <div class="col-6">
                                        <strong>Responsable:</strong>
                                    </div>
                                    <div class="col-6 text-end">
                                        <span id="confirmarResponsableNombre" class="fw-semibold">-</span>
                                    </div>
                                </div>
                                <div class="row small mb-2">
                                    <div class="col-6">
                                        <strong>Total productos:</strong>
                                    </div>
                                    <div class="col-6 text-end">
                                        <span id="confirmarTotalProductos">0</span> items
                                    </div>
                                </div>
                                <div class="row small mb-2">
                                    <div class="col-6">
                                        <strong>Subtotal:</strong>
                                    </div>
                                    <div class="col-6 text-end">
                                        <span id="confirmarSubtotal">0.00</span>
                                    </div>
                                </div>
                                <div class="row small mb-2">
                                    <div class="col-6">
                                        <strong>IVA (<span id="confirmarTasaIva">0%</span>):</strong>
                                    </div>
                                    <div class="col-6 text-end">
                                        <span id="confirmarIva">0.00</span>
                                    </div>
                                </div>
                                <div class="row small mb-2">
                                    <div class="col-6">
                                        <strong>ISR (<span id="confirmarTasaIsr">0%</span>):</strong>
                                    </div>
                                    <div class="col-6 text-end">
                                        <span id="confirmarIsr">0.00</span>
                                    </div>
                                </div>
                                <hr class="my-3">
                                <div class="row fw-bold mb-2">
                                    <div class="col-6">
                                        <strong>TOTAL VENTA:</strong>
                                    </div>
                                    <div class="col-6 text-end text-success">
                                        <span id="confirmarTotal">0.00</span>
                                    </div>
                                </div>
                                <div class="row small mb-1">
                                    <div class="col-6">
                                        <strong>Saldo actual:</strong>
                                    </div>
                                    <div class="col-6 text-end">
                                        <span id="confirmarSaldoActual">0.00</span>
                                    </div>
                                </div>
                                <div class="row small mb-1">
                                    <div class="col-6">
                                        <strong>Saldo después:</strong>
                                    </div>
                                    <div class="col-6 text-end">
                                        <span id="confirmarSaldoDespues">0.00</span>
                                    </div>
                                </div>
                                <div class="row small mt-2">
                                    <div class="col-12">
                                        <div class="alert alert-info py-2 mb-0">
                                            <i class="fas fa-info-circle me-1"></i>
                                            <small>Régimen: <span id="confirmarRegimenNombre">No especificado</span></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    
                    
                </div>

                <!-- Advertencia de saldo (se mostrará dinámicamente si es necesario) -->
                <div id="confirmarAdvertenciaSaldo" class="mt-3" style="display: none;">
                    <!-- Aquí se mostrarán advertencias de saldo insuficiente -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancelar
                </button>
                <button type="button" class="btn btn-warning" id="btnConfirmarVentaFinal">
                    <i class="fas fa-check me-1"></i> Sí, Confirmar Venta
                </button>
            </div>
        </div>
    </div>
</div>


<!-- Modal de confirmación de cancelación (agrégalo al final de tu vista) -->
<div class="modal fade" id="modalCancelacion" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle"></i> Cancelar Venta
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-circle"></i>
                    <strong>Advertencia:</strong> Esta acción no se puede deshacer.
                </div>
                
                <p id="textoTicket"></p>
                <p class="text-danger"><strong>Se restaurará el stock y se devolverá el saldo al cliente.</strong></p>
                
                <div class="mb-3">
                    <label for="motivoCancelacion" class="form-label">
                        <i class="fas fa-comment"></i> Motivo de cancelación *
                    </label>
                    <select class="form-select" id="motivoCancelacion" required>
                        <option value="">Seleccione un motivo</option>
                        <option value="Error en productos">Error en productos</option>
                        <option value="Cliente arrepentido">Cliente arrepentido</option>
                        <option value="Error en precio">Error en precio</option>
                        <option value="Problema con el stock">Problema con el stock</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="observacionesCancelacion" class="form-label">
                        <i class="fas fa-sticky-note"></i> Observaciones adicionales
                    </label>
                    <textarea class="form-control" 
                              id="observacionesCancelacion" 
                              rows="3" 
                              placeholder="Detalles adicionales de la cancelación..."></textarea>
                </div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="confirmarCancelacion" required>
                    <label class="form-check-label" for="confirmarCancelacion">
                        Confirmo que deseo cancelar esta venta
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
                <button type="button" class="btn btn-danger" onclick="confirmarCancelacion()">
                    <i class="fas fa-ban"></i> Sí, Cancelar Venta
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Devolución -->
<div class="modal fade" id="modalDevolucion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title">
                    <i class="fas fa-undo me-2"></i> Devolver Productos
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="formDevolucion" onsubmit="return false;">
                <div class="modal-body">
                    <!-- Información de la venta -->
                    <div class="alert alert-info mb-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-info-circle fa-lg me-2"></i>
                            <div>
                                <h6 class="mb-1">Devolución de productos</h6>
                                <p class="mb-0 small" id="infoVentaDevolucion">
                                    Seleccione los productos a devolver y la cantidad.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Ticket y Cliente -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="card border-primary">
                                <div class="card-body py-2">
                                    <small class="text-muted">Ticket</small>
                                    <h6 class="mb-0 fw-bold text-primary" id="ticketDevolucion"></h6>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-info">
                                <div class="card-body py-2">
                                    <small class="text-muted">Cliente</small>
                                    <h6 class="mb-0" id="clienteDevolucion"></h6>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Motivo de devolución -->
                    <div class="mb-3">
                        <label for="motivoDevolucion" class="form-label fw-semibold">
                            <i class="fas fa-comment me-1"></i> Motivo de devolución *
                        </label>
                        
                        <select class="form-select" id="motivoDevolucion" required>
                        <option value="">Seleccione un motivo</option>
                        <option value="producto_defectuoso">Producto defectuoso</option>
                        <option value="no_corresponde_pedido">No corresponde al pedido</option>
                        <option value="cliente_arrepentido">Cliente arrepentido</option>
                        <option value="error_cantidad">Error en cantidad</option>
                        <option value="cambio_producto">Cambio por otro producto</option>
                        <option value="otro">Otro</option>
                    </select>
                    </div>
                    
                    <!-- Observaciones -->
                    <div class="mb-3">
                        <label for="observacionesDevolucion" class="form-label fw-semibold">
                            <i class="fas fa-sticky-note me-1"></i> Observaciones adicionales
                        </label>
                        <textarea class="form-control" id="observacionesDevolucion" rows="2" 
                                  placeholder="Detalles adicionales de la devolución..."></textarea>
                    </div>
                    
                    <!-- Lista de productos -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-semibold mb-0">
                                <i class="fas fa-boxes me-1"></i> Productos de la venta
                            </h6>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="selectAllProducts">
                                <label class="form-check-label small" for="selectAllProducts">
                                    Seleccionar todos
                                </label>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover table-sm" id="tablaProductosDevolucion">
                                <thead class="table-light">
                                    <tr>
                                        <th width="40px"></th>
                                        <th>Producto</th>
                                        <th class="text-center">Precio</th>
                                        <th class="text-center">Comprado</th>
                                        <th class="text-center">Ya devuelto</th>
                                        <th class="text-center">Disponible</th>
                                        <th class="text-center">Cantidad a devolver</th>
                                    </tr>
                                </thead>
                                <tbody id="listaProductosDevolucion">
                                    <!-- Los productos se cargarán aquí -->
                                </tbody>
                                <tfoot id="resumenDevolucion" class="table-light" style="display: none;">
                                    <tr>
                                        <td colspan="5" class="text-end fw-bold">Total a devolver:</td>
                                        <td class="text-center fw-bold" id="totalProductosDevolver">0</td>
                                        <td class="text-end fw-bold text-success" id="montoTotalDevolver">$0.00</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <div class="text-muted small mt-2">
                            <i class="fas fa-info-circle me-1"></i>
                            Solo se pueden devolver productos que no hayan sido devueltos completamente.
                        </div>
                    </div>
                    
                    <!-- Confirmación -->
                    <div class="alert alert-warning mb-0">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirmarDevolucion" required>
                            <label class="form-check-label" for="confirmarDevolucion">
                                Confirmo que los productos seleccionados serán devueltos al inventario y el 
                                saldo correspondiente será restituido al cliente.
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-warning" id="btnProcesarDevolucion" onclick="procesarDevolucion()" disabled>
                        <i class="fas fa-check me-1"></i> Procesar Devolución
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Modal para ver historial de devoluciones -->
<!-- Modal para ver historial de devoluciones -->
<div class="modal fade" id="modalHistorialDevoluciones" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="fas fa-history me-2"></i> Historial de Devoluciones
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body">
                <!-- Información de la venta -->
                <div class="alert alert-info mb-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-receipt fa-lg me-2"></i>
                        <div>
                            <h6 class="mb-1" id="tituloHistorialDevolucion"></h6>
                            <p class="mb-0 small" id="subtituloHistorialDevolucion"></p>
                        </div>
                    </div>
                </div>
                
                <!-- Resumen de devoluciones -->
                <div class="row mb-3" id="resumenDevoluciones" style="display: none;">
                    <div class="col-md-3">
                        <div class="card border-primary">
                            <div class="card-body py-2 text-center">
                                <small class="text-muted">Total devoluciones</small>
                                <h5 class="mb-0 fw-bold text-primary" id="totalDevoluciones">0</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-success">
                            <div class="card-body py-2 text-center">
                                <small class="text-muted">Productos devueltos</small>
                                <h5 class="mb-0 fw-bold text-success" id="totalProductosDevueltos">0</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-warning">
                            <div class="card-body py-2 text-center">
                                <small class="text-muted">Monto total devuelto</small>
                                <h5 class="mb-0 fw-bold text-warning" id="montoTotalDevuelto">$0.00</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-info">
                            <div class="card-body py-2 text-center">
                                <small class="text-muted">Última devolución</small>
                                <h6 class="mb-0" id="ultimaDevolucion">N/A</h6>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de devoluciones -->
                <div class="mb-3">
                    <h6 class="fw-semibold mb-3 border-bottom pb-2">
                        <i class="fas fa-list me-1"></i> Detalle de devoluciones
                    </h6>
                    
                    <div id="listaDevolucionesContainer">
                        <!-- Mensaje cuando no hay devoluciones -->
                        <div id="sinDevoluciones" class="text-center py-5">
                            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay devoluciones registradas</h5>
                            <p class="text-muted small">Esta venta no tiene productos devueltos</p>
                        </div>
                        
                        <!-- Tabla de devoluciones -->
                        <div class="table-responsive" id="tablaDevoluciones" style="display: none;">
                            <table class="table table-hover table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Folio</th>
                                        <th>Fecha</th>
                                        <th>Productos</th>
                                        <th>Motivo</th>
                                        <th class="text-end">Monto</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="cuerpoTablaDevoluciones">
                                    <!-- Las devoluciones se cargarán aquí -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Detalle de productos devueltos -->
                <div class="mb-3" id="detalleProductosContainer" style="display: none;">
                    <h6 class="fw-semibold mb-3 border-bottom pb-2">
                        <i class="fas fa-box me-1"></i> Productos devueltos
                    </h6>
                    
                    <div class="table-responsive">
                        <table class="table table-sm" id="tablaProductosDevueltos">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-center">Cantidad devuelta</th>
                                    <th class="text-center">Precio unitario</th>
                                    <th class="text-end">Subtotal</th>
                                    <th class="text-end">IVA</th>
                                    <th class="text-end">ISR</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody id="cuerpoProductosDevueltos">
                                <!-- Los productos se cargarán aquí -->
                            </tbody>
                            <tfoot class="table-light" id="totalProductosDevueltos" style="display: none;">
                                <tr>
                                    <td colspan="3" class="text-end fw-bold">Totales:</td>
                                    <td class="text-end fw-bold" id="totalSubtotal">$0.00</td>
                                    <td class="text-end fw-bold" id="totalIva">$0.00</td>
                                    <td class="text-end fw-bold" id="totalIsr">$0.00</td>
                                    <td class="text-end fw-bold text-success" id="totalGeneral">$0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                
                <!-- Resumen por producto -->
                <div class="mb-3" id="resumenPorProductoContainer" style="display: none;">
                    <h6 class="fw-semibold mb-3 border-bottom pb-2">
                        <i class="fas fa-chart-bar me-1"></i> Resumen por producto
                    </h6>
                    
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-center">Comprado</th>
                                    <th class="text-center">Devuelto</th>
                                    <th class="text-center">Disponible</th>
                                    <th class="text-center">Porcentaje</th>
                                    <th class="text-center">Estado</th>
                                </tr>
                            </thead>
                            <tbody id="cuerpoResumenProducto">
                                <!-- El resumen se cargará aquí -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cerrar
                </button>
                <button type="button" class="btn btn-info" id="btnImprimirHistorial" style="display: none;">
                    <i class="fas fa-print me-1"></i> Imprimir Historial
                </button>
            </div>
        </div>
    </div>
</div>
@endsection