@extends('layouts.nota_abonos.app-master')
@section('content')

    @if($cliente)
        <div class="card mt-4 depth-3">
            <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-0"><i class="fas fa-file-invoice-dollar me-2"></i>Notas De Abono</h3>
                    <p class="mb-0 opacity-75">{{ $cliente->nombre }}</p>
                </div>
                <div class="text-end">
                    <span class="badge bg-light text-dark me-2">Cliente #{{ $cliente->id }}</span>
                    <span class="badge bg-accent text-dark">{{ $notasAbonoActivas->count() }} Notas Activas</span>
                </div>
            </div>
            <div class="card-body">
                
                <!-- Botón para crear nueva nota de abono -->
                <div class="text-center mb-4">
                    <button type="button" class="btn btn-primary btn-lg px-4" data-bs-toggle="modal" data-bs-target="#modalCrearAbono">
                        <i class="fas fa-plus-circle me-2"></i>Crear Nueva Nota de Abono
                    </button>
                    <p class="text-muted mt-2 mb-0">Puedes tener múltiples notas de abono activas simultáneamente</p>
                </div>

                <!-- Filtros de fecha -->
              <!-- Filtros de fecha - Mostrar siempre que haya notas -->
                @if($notasAbono->count() > 0)
                <div class="card filter-card depth-2">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-calendar me-2"></i>Filtrar por Fecha de Apertura</h5>
                    </div>
                    <div class="card-body">
                        <form id="formFiltroFechas">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label for="fecha_desde" class="form-label">Desde</label>
                                    <input type="date" class="form-control" id="fecha_desde" name="fecha_desde">
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="fecha_hasta" class="form-label">Hasta</label>
                                    <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta">
                                </div>

                                <div class="col-md-3">
                                    <label for="filtro_estado" class="form-label">Estado</label>
                                    <select class="form-select" id="filtro_estado" name="estado">
                                        <option value="">Todos los estados</option>
                                        <option value="activa">Activas</option>
                                        <option value="finalizada">Finalizadas</option>
                                        <option value="saldo_favor">Saldo a Favor</option>
                                        <option value="saldo_deuda">Saldo en Deuda</option>
                                        <option value="cancelada">Canceladas</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="button" class="btn btn-outline-primary w-100 me-2" onclick="NotasAbonoApp.aplicarFiltrosNotas()">
                                        <i class="fas fa-search me-1"></i> Buscar
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="NotasAbonoApp.limpiarFiltrosNotas()" title="Limpiar filtros">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                @endif

                @if($notasAbono->count() > 0)
                <!-- ============================================== -->
                <!-- DISTRIBUCIÓN POR ESTADO (AGREGADO AQUÍ) -->
                <!-- ============================================== -->
                <div class="card mt-4 border-info">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-chart-pie me-2"></i>Distribución por Estado
                            </h6>
                        </div>

                        <div class="card-body">
                            <div class="row text-center row-cols-1 row-cols-md-3">

                                <div class="col mb-3">
                                    <div class="stats-card border-success">
                                        <div class="stats-number">
                                            {{ $resumenEstados['activa']['count'] ?? 0 }}
                                        </div>
                                        <div class="stats-label">Activas</div>
                                        <small class="text-success">
                                            ${{ number_format($resumenEstados['activa']['total_saldo'] ?? 0, 2) }}
                                        </small>
                                    </div>
                                </div>

                                <div class="col mb-3">
                                    <div class="stats-card border-primary">
                                        <div class="stats-number">
                                            {{ $resumenEstados['finalizada']['count'] ?? 0 }}
                                        </div>
                                        <div class="stats-label">Finalizadas</div>
                                        <small class="text-primary">
                                            ${{ number_format($resumenEstados['finalizada']['total_saldo'] ?? 0, 2) }}
                                        </small>
                                    </div>
                                </div>

                                <div class="col mb-3">
                                    <div class="stats-card border-dark">
                                        <div class="stats-number">
                                            {{
                                                ($resumenEstados['activa']['count'] ?? 0) +
                                                ($resumenEstados['finalizada']['count'] ?? 0) +
                                                ($resumenEstados['cancelada']['count'] ?? 0)
                                            }}
                                        </div>
                                        <div class="stats-label">Total Notas</div>
                                        <small class="text-dark">
                                            ${{
                                                number_format(
                                                    ($resumenEstados['activa']['total_abono'] ?? 0) +
                                                    ($resumenEstados['finalizada']['total_abono'] ?? 0) +
                                                    ($resumenEstados['cancelada']['total_abono'] ?? 0),
                                                    2
                                                )
                                            }}
                                        </small>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>


                <!-- LISTA DE NOTAS DE ABONO -->
                <div class="row mt-4" id="contenedor-notas">
                    @foreach($notasAbono as $notaAbono)
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 border-primary shadow-sm {{ $notaAbono->estado === 'finalizada' ? 'border-secondary' : '' }}">
                            <div class="card-header {{ $notaAbono->estado === 'finalizada' ? 'bg-secondary text-white' : 'bg-primary text-white' }} d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">
                                        <i class="fas fa-file-invoice-dollar me-2"></i>{{ $notaAbono->folio }}
                                    </h6>
                                    <small class="opacity-75">
                                        {{ \Carbon\Carbon::parse($notaAbono->created_at)->format('d/m/Y H:i') }}
                                    </small>
                                </div>
                                <span class="badge {{ 
                                    $notaAbono->estado === 'activa' ? 'bg-success' : 
                                    ($notaAbono->estado === 'finalizada' ? 'bg-secondary' : 
                                    ($notaAbono->estado === 'saldo_favor' ? 'bg-success' : 
                                    ($notaAbono->estado === 'saldo_deuda' ? 'bg-danger' : 'bg-warning'))) 
                                }}">
                                    {{ strtoupper($notaAbono->estado) }}
                                </span>
                            </div>
                            <div class="card-body">
                                <!-- Información del cliente -->
                                <div class="mb-3">
                                    <strong>Cliente:</strong><br>
                                    <span class="text-dark">{{ $notaAbono->cliente->nombre }}</span>
                                </div>

                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="info-item">
                                            <span class="info-label">Abono Inicial</span>
                                            <span class="info-value text-success fw-bold fs-5">
                                                ${{ number_format($notaAbono->abono_inicial, 2) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-item">
                                            <span class="info-label">Saldo Actual</span>
                                            <span class="info-value {{ 
                                                $notaAbono->estado === 'activa' && $notaAbono->saldo_actual > 0 ? 'text-primary' : 
                                                ($notaAbono->estado === 'saldo_deuda' ? 'text-danger' : 
                                                ($notaAbono->estado === 'saldo_favor' ? 'text-success' : 'text-warning')) 
                                            }} fw-bold fs-5">
                                                @if($notaAbono->estado === 'saldo_deuda')
                                                    -${{ number_format(abs($notaAbono->saldo_actual), 2) }}
                                                @else
                                                    ${{ number_format($notaAbono->saldo_actual, 2) }}
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row g-2 mt-2">
                                    <div class="col-6">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            {{ \Carbon\Carbon::parse($notaAbono->fecha_apertura)->format('d/m/Y') }}
                                        </small>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            {{ \Carbon\Carbon::parse($notaAbono->fecha_apertura)->diffInDays(now()) }} días
                                        </small>
                                    </div>
                                </div>
                                
                                @if($notaAbono->observaciones)
                                <div class="mt-3 p-2 bg-light rounded">
                                    <span class="info-label">Observaciones</span>
                                    <p class="info-value mb-0 small">{{ $notaAbono->observaciones }}</p>
                                </div>
                                @endif
                                
                                @if($notaAbono->estado === 'cancelada' && $notaAbono->motivo_cancelacion)
                                <div class="mt-3 p-2 bg-light rounded border border-danger">
                                    <span class="info-label text-danger">Motivo de cancelación:</span>
                                    <p class="info-value mb-0 small text-danger">{{ $notaAbono->motivo_cancelacion }}</p>
                                </div>
                                @endif
                            </div>
                            <div class="card-footer bg-transparent">
                                <div class="d-grid gap-2">
                                    <div class="btn-group w-100" role="group">
                                        @if($notaAbono->estado === 'activa')
                                        <a href="{{ route('administrar-notas-abono.administrar', $notaAbono->id) }}" 
                                        class="btn btn-success btn-sm flex-fill">
                                            <i class="fas fa-cog me-1"></i> Administrar
                                        </a>
                                        
                                        <button type="button" 
                                                class="btn btn-accent btn-sm"
                                                onclick="NotasAbonoApp.editarNotaAbono({{ $notaAbono->id }})" 
                                                data-bs-toggle="tooltip"
                                                title="Editar información de la nota">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        @endif
                                        @if($notaAbono->estado === 'finalizada')
                                        <a href="{{ route('administrar-notas-abono.administrar', $notaAbono->id) }}" 
                                        class="btn btn-success btn-sm flex-fill">
                                            <i class="fas fa-eye me-1"></i> Visualizar Ventas
                                        </a>
                                         @endif
                                    </div>
                                    
                                    <!-- Botón de historial siempre visible -->
                                   <button type="button" 
                                            class="btn btn-info btn-sm"
                                            onclick="NotasAbonoApp.verHistorialAjustes({{ $notaAbono->id }})"  
                                            data-bs-toggle="tooltip"
                                            title="Ver historial de movimientos">
                                        <i class="fas fa-history me-1"></i> Historial
                                    </button>

                                    @if($notaAbono->estado === 'activa' && $notaAbono->saldo_actual == 0)
                                    <button type="button" 
                                            class="btn btn-warning btn-sm"
                                            onclick="NotasAbonoApp.finalizarNotaAbono({{ $notaAbono->id }}, '{{ $notaAbono->folio }}')">
                                        <i class="fas fa-lock me-1"></i> Finalizar Nota
                                    </button>
                                    @elseif($notaAbono->estado === 'activa')
                                    <small class="text-muted text-center">
                                        <i class="fas fa-info-circle me-1"></i>Saldo debe ser $0 para finalizar
                                    </small>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- PAGINACIÓN PARA FILTROS AJAX (oculta inicialmente) -->
                <div id="paginacionNotasAjax" class="mt-4" style="display: none;">
                    <!-- La paginación se cargará aquí cuando uses filtros AJAX -->
                </div>

                <!-- PAGINACIÓN DE NOTAS -->
                @if($notasAbono->hasPages())
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <!-- Información de paginación -->
                                    <div class="text-muted small">
                                        Mostrando {{ $notasAbono->firstItem() }} a {{ $notasAbono->lastItem() }} 
                                        de {{ $notasAbono->total() }} notas
                                    </div>
                                    
                                    <!-- Controles de paginación -->
                                    <nav aria-label="Paginación de notas">
                                        <ul class="pagination pagination-sm mb-0">
                                            <!-- Botón Anterior -->
                                            @if($notasAbono->onFirstPage())
                                                <li class="page-item disabled">
                                                    <span class="page-link">&laquo; Anterior</span>
                                                </li>
                                            @else
                                                <li class="page-item">
                                                    <a class="page-link" href="{{ $notasAbono->previousPageUrl() }}">&laquo; Anterior</a>
                                                </li>
                                            @endif

                                            <!-- Números de página -->
                                            @foreach($notasAbono->getUrlRange(1, $notasAbono->lastPage()) as $page => $url)
                                                @if($page == $notasAbono->currentPage())
                                                    <li class="page-item active">
                                                        <span class="page-link">{{ $page }}</span>
                                                    </li>
                                                @else
                                                    <li class="page-item">
                                                        <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                                    </li>
                                                @endif
                                            @endforeach

                                            <!-- Botón Siguiente -->
                                            @if($notasAbono->hasMorePages())
                                                <li class="page-item">
                                                    <a class="page-link" href="{{ $notasAbono->nextPageUrl() }}">Siguiente &raquo;</a>
                                                </li>
                                            @else
                                                <li class="page-item disabled">
                                                    <span class="page-link">Siguiente &raquo;</span>
                                                </li>
                                            @endif
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- RESUMEN DE SALDOS (ANTIGUO - RECOMIENDO MANTENERLO) -->
                <div class="card mt-4 border-warning">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Resumen de Saldos</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <div class="stats-number">{{ $notasAbono->count() }}</div>
                                    <div class="stats-label">Total Notas</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <div class="stats-number text-success">${{ number_format($notasAbono->sum('abono_inicial'), 2) }}</div>
                                    <div class="stats-label">Total Abonado</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <div class="stats-number text-primary">${{ number_format($notasAbono->where('estado', 'activa')->sum('saldo_actual'), 2) }}</div>
                                    <div class="stats-label">Saldo Disponible</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <div class="stats-number text-warning">${{ number_format($notasAbono->sum('abono_inicial') - $notasAbono->where('estado', 'activa')->sum('saldo_actual'), 2) }}</div>
                                    <div class="stats-label">Total Utilizado</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            @else
                <!-- NO HAY NOTAS -->
                <div class="text-center py-5">
                    <i class="fas fa-file-invoice-dollar fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">No hay notas de abono</h4>
                    <p class="text-muted">No se encontraron notas de abono con los filtros aplicados.</p>
                    <button type="button" class="btn btn-outline-primary" onclick="NotasAbonoApp.limpiarFiltros()">
                        <i class="fas fa-times me-1"></i> Limpiar Filtros
                    </button>
                </div>
            @endif

            </div>
        </div>

        <!-- Los modales siguen igual... -->
        
        <!-- Modal para crear abono -->
        <div class="modal fade" id="modalCrearAbono" tabindex="-1" aria-labelledby="modalCrearAbonoLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content depth-4">
                    <div class="modal-header bg-gradient-primary text-white">
                        <h5 class="modal-title" id="modalCrearAbonoLabel">
                            <i class="fas fa-file-invoice-dollar me-2"></i>Crear Nueva Nota de Abono
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <h6 class="mb-2"><i class="fas fa-info-circle me-2"></i>Nueva nota de abono para:</h6>
                            <p class="mb-1"><strong>Cliente:</strong> {{ $cliente->nombre }}</p>
                            <p class="mb-1"><strong>Notas Activas Actuales:</strong> {{ $notasAbonoActivas->count() }}</p>
                            <p class="mb-0"><strong>Saldo Total Disponible:</strong> ${{ number_format($notasAbonoActivas->sum('saldo_actual'), 2) }}</p>
                        </div>

                        <form id="formCrearAbono">
                            @csrf
                            <input type="hidden" name="cliente_id" value="{{ $cliente->id }}">
                            
                            <div class="mb-3">
                                <label for="monto_abono" class="form-label">
                                    <strong>Monto del Abono *</strong>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" 
                                           class="form-control" 
                                           id="monto_abono" 
                                           name="monto_abono" 
                                           step="0.01" 
                                           min="0.01"
                                           placeholder="0.00"
                                           required>
                                </div>
                                <div id="montoFeedback" class="form-text"></div>
                            </div>

                            <div class="mb-3">
                                <label for="fecha_abono" class="form-label">
                                    <strong>Fecha del Abono</strong>
                                </label>
                                <input type="date" 
                                       class="form-control" 
                                       id="fecha_abono" 
                                       name="fecha_abono"
                                       value="{{ date('Y-m-d') }}">
                            </div>

                            <div class="mb-3">
                                <label for="observaciones" class="form-label">
                                    <strong>Concepto u Observaciones</strong>
                                </label>
                                <textarea class="form-control" 
                                          id="observaciones" 
                                          name="observaciones" 
                                          rows="3" 
                                          placeholder="Ej: Abono para compras de ferretería, Abono para materiales de construcción, Abono para proyecto especial..."></textarea>
                                <div class="form-text">Describe el propósito de este abono específico (opcional)</div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </button>
                        <button type="button" class="btn btn-primary" onclick="NotasAbonoApp.guardarAbono()">  <!-- Cambiado -->
                            <i class="fas fa-save me-1"></i> Crear Nota de Abono
                        </button>
                    </div>
                </div>
            </div>
        </div>

      <!-- Modal para editar/ajustar abono -->
        <div class="modal fade" id="modalEditarAbono" tabindex="-1" aria-labelledby="modalEditarAbonoLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content depth-4">
                    <div class="modal-header bg-gradient-primary text-white">
                        <h5 class="modal-title" id="modalEditarAbonoLabel">
                            <i class="fas fa-edit me-2"></i>Gestionar Nota de Abono
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <h6 class="mb-2"><i class="fas fa-info-circle me-2"></i>Opciones de gestión</h6>
                            <p class="mb-0">Puedes ajustar el saldo actual sumando o restando montos, o editar la información básica.</p>
                        </div>

                        <form id="formEditarAbono">
                            @csrf
                            @method('PUT')
                            <input type="hidden" id="edit_nota_id" name="id">
                            
                            <!-- Información Actual -->
                            <div class="card bg-light mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información Actual</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <strong>Folio:</strong><br>
                                            <span id="edit_folio_actual" class="text-primary fw-bold">-</span>
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Abono Inicial:</strong><br>
                                            <span id="edit_abono_inicial_actual" class="text-success fw-bold">$0.00</span>
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Saldo Actual:</strong><br>
                                            <span id="edit_saldo_actual" class="text-primary fw-bold">$0.00</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tipo de Operación -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Tipo de Operación *</label>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="tipo_operacion" id="tipo_sumar" value="sumar" checked>
                                            <label class="form-check-label" for="tipo_sumar">
                                                <i class="fas fa-plus-circle text-success me-1"></i> Sumar Saldo
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="tipo_operacion" id="tipo_restar" value="restar">
                                            <label class="form-check-label" for="tipo_restar">
                                                <i class="fas fa-minus-circle text-danger me-1"></i> Restar Saldo
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="tipo_operacion" id="tipo_editar" value="editar">
                                            <label class="form-check-label" for="tipo_editar">
                                                <i class="fas fa-edit text-warning me-1"></i> Solo Editar Info
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Campos para Sumar/Restar -->
                            <div id="campo_ajuste_monto">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="monto_ajuste" class="form-label">
                                                <strong>Monto del Ajuste *</strong>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" 
                                                    class="form-control" 
                                                    id="monto_ajuste" 
                                                    name="monto_ajuste" 
                                                    step="0.01" 
                                                    min="0.01"
                                                    placeholder="0.00"
                                                    value="0.00">
                                            </div>
                                            <div class="form-text" id="ajuste_feedback"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="concepto_ajuste" class="form-label">
                                                <strong>Concepto del Ajuste *</strong>
                                            </label>
                                            <select class="form-select" id="concepto_ajuste" name="concepto_ajuste">
                                                <option value="">Seleccionar concepto...</option>
                                                <option value="abono_adicional">Abono Adicional del Cliente</option>
                                                <option value="correccion_error">Corrección por Error</option>
                                                <option value="ajuste_administrativo">Ajuste Administrativo</option>
                                                <option value="otros">Otros</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Campos para Editar Información (ocultos inicialmente) -->
                            <div id="campo_editar_info" style="display: none;">
                                <div class="mb-3">
                                    <label for="edit_abono_inicial" class="form-label">
                                        <strong>Abono Inicial *</strong>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" 
                                            class="form-control" 
                                            id="edit_abono_inicial" 
                                            name="abono_inicial" 
                                            step="0.01" 
                                            min="0.01"
                                            placeholder="0.00"
                                            required>
                                    </div>
                                </div>
                            </div>

                            <!-- Campos comunes -->
                            <div class="mb-3">
                                <label for="edit_fecha_abono" class="form-label">
                                    <strong>Fecha de la Operación</strong>
                                </label>
                                <input type="date" 
                                    class="form-control" 
                                    id="edit_fecha_abono" 
                                    name="fecha_abono"
                                    required>
                            </div>

                            <div class="mb-3">
                                <label for="edit_observaciones" class="form-label">
                                    <strong>Observaciones Detalladas</strong>
                                </label>
                                <textarea class="form-control" 
                                        id="edit_observaciones" 
                                        name="observaciones" 
                                        rows="3" 
                                        placeholder="Describe detalladamente la razón de este ajuste..."></textarea>
                                <div class="form-text">Ej: "Cliente agregó $1,200 adicionales en efectivo", "Corrección por error de digitación"</div>
                            </div>

                            <!-- Resumen del Nuevo Saldo -->
                            <div class="card border-warning">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0"><i class="fas fa-calculator me-2"></i>Resumen del Cambio</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-md-4">
                                            <strong>Saldo Actual:</strong><br>
                                            <span id="resumen_saldo_actual" class="text-primary fw-bold">$0.00</span>
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Monto Ajuste:</strong><br>
                                            <span id="resumen_monto_ajuste" class="text-success fw-bold">+$0.00</span>
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Nuevo Saldo:</strong><br>
                                            <span id="resumen_nuevo_saldo" class="text-success fw-bold">$0.00</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </button>
                        <button type="button" class="btn btn-primary" onclick="NotasAbonoApp.actualizarNotaAbono()">  
                            <i class="fas fa-save me-1"></i> Aplicar Cambios
                        </button>
                    </div>
                </div>
            </div>
        </div>

    <!-- Modal para Historial de Ajustes -->
    <div class="modal fade" id="modalHistorialAjustes" tabindex="-1" aria-labelledby="modalHistorialAjustesLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content depth-4">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title" id="modalHistorialAjustesLabel">
                        <i class="fas fa-history me-2"></i>Historial de Movimientos
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Información de la nota -->
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <strong>Folio:</strong><br>
                                    <span id="historial_folio" class="text-primary fw-bold">-</span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Cliente:</strong><br>
                                    <span id="historial_cliente" class="text-dark">-</span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Abono Inicial:</strong><br>
                                    <span id="historial_abono_inicial" class="text-success fw-bold">$0.00</span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Saldo Actual:</strong><br>
                                    <span id="historial_saldo_actual" class="text-primary fw-bold">$0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filtros -->
                   <!-- Filtros -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros de Movimientos</h6>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="NotasAbonoApp.limpiarFiltrosMovimientos()">
                                <i class="fas fa-times me-1"></i> Limpiar
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="filtro_tipo" class="form-label">Tipo de Movimiento</label>
                                    <select class="form-select" id="filtro_tipo">
                                        <option value="">Todos los tipos</option>
                                        <option value="abono">Abonos</option>
                                        <option value="compra">Compras</option>
                                        <option value="ajuste">Ajustes</option>
                                        <option value="cierre">Cierres</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="filtro_fecha_desde" class="form-label">Fecha Desde</label>
                                    <input type="date" class="form-control" id="filtro_fecha_desde">
                                </div>
                                <div class="col-md-4">
                                    <label for="filtro_fecha_hasta" class="form-label">Fecha Hasta</label>
                                    <input type="date" class="form-control" id="filtro_fecha_hasta">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de movimientos -->
                    <div class="table-responsive">
                        <table class="table table-hover" id="tablaHistorial">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha/Hora</th>
                                    <th>Tipo</th>
                                    <th>Concepto</th>
                                    <th>Monto</th>
                                    <th>Saldo Anterior</th>
                                    <th>Nuevo Saldo</th>
                                    <th>Usuario</th>
                                    <th>Observaciones</th>
                                </tr>
                            </thead>
                            <tbody id="cuerpoHistorial">
                                <!-- Los movimientos se cargarán aquí -->
                            </tbody>
                        </table>
                    </div>
                    <!-- Información de paginación -->
                    <!-- Después de la tabla -->
                    <div class="row align-items-center mt-3">
                        <div class="col-md-6">
                            <div id="infoPaginacionMovimientos" class="text-muted small">
                                <!-- Información de paginación -->
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div id="paginacionMovimientos" class="d-flex justify-content-end">
                                <!-- Controles de paginación -->
                            </div>
                        </div>
                    </div>

                    <!-- Resumen -->
                    <div class="card mt-4 border-info">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Resumen de Movimientos</h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-3">
                                    <div class="stats-card">
                                        <div class="stats-number" id="total_movimientos">0</div>
                                        <div class="stats-label">Total Movimientos</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stats-card">
                                        <div class="stats-number text-success" id="total_abonos">$0.00</div>
                                        <div class="stats-label">Total Abonado</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stats-card">
                                        <div class="stats-number text-danger" id="total_compras">$0.00</div>
                                        <div class="stats-label">Total en Compras</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stats-card">
                                        <div class="stats-number text-warning" id="total_ajustes">$0.00</div>
                                        <div class="stats-label">Total Ajustes</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cerrar
                    </button>
                    <button type="button" 
                            class="btn btn-primary"
                            onclick="NotasAbonoApp.exportarHistorial()"
                            data-bs-toggle="tooltip"
                            title="Generar estado de cuenta profesional en PDF">
                        <i class="fas fa-file-invoice me-1"></i> Estado de Cuenta PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    @else
        <div class="alert alert-warning mt-4 depth-2">
            <i class="fas fa-exclamation-triangle me-2"></i>No se ha seleccionado ningún cliente.
        </div>
    @endif

@endsection