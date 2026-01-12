@extends('layouts.cotizacionHistorial.app-master')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-lg border-0">
                <!-- Header Mejorado con Búsqueda Integrada -->
                <div class="card-header bg-gradient-primary text-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div>
                                <h4 class="mb-0 fw-bold">Historial de Cotizaciones</h4>
                                <small class="opacity-80">Gestión y consulta de todas sus cotizaciones</small>
                            </div>
                        </div>
                        <div class="flex-grow-1 mx-4" style="max-width: 400px;">
                            <!-- 🔍 BÚSQUEDA MEJORADA -->
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fas fa-search text-primary"></i>
                                </span>
                                <input type="text" 
                                       id="searchInput" 
                                       class="form-control border-start-0" 
                                       placeholder="Buscar por nombre del cliente..." 
                                       onkeyup="buscarRecibos()"
                                       style="border-left: none;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Body Mejorado -->
                <div class="card-body p-0">
                    @if($cotizaciones->count() > 0)
                    <!-- Estadísticas Rápidas CORREGIDAS -->
                    <div class="bg-light py-3 px-4 border-bottom">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="d-flex align-items-center">
                                    <div>
                                        <h6 class="mb-0 fw-bold">{{ $cotizaciones->total() }}</h6>
                                        <small class="text-muted">Total de Cotizaciones</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center">
                                    <div>
                                        <h6 class="mb-0 fw-bold">{{ $cotizaciones->where('fecha_creacion', '>=', now()->subDays(7))->count() }}</h6>
                                        <small class="text-muted">Últimos 7 días</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center">
                                    <div>
                                        <h6 class="mb-0 fw-bold">
                                            @if($cotizaciones->count() > 0)
                                                {{ \Carbon\Carbon::parse($cotizaciones->first()->fecha_creacion)->format('d/m/Y') }}
                                            @else
                                                N/A
                                            @endif
                                        </h6>
                                        <small class="text-muted">Más Reciente</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Tabla Mejorada -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th class="ps-4" width="5%">
                                        <i class="fas fa-hashtag me-1"></i>#
                                    </th>
                                    <th width="25%">
                                        <i class="fas fa-user me-1"></i>Cliente
                                    </th>
                                    <th width="20%">
                                        <i class="fas fa-calendar me-1"></i>Fecha de Creación
                                    </th>
                                    <th width="20%">
                                        <i class="fas fa-clock me-1"></i>Hora
                                    </th>
                                    <th class="text-center pe-4" width="30%">
                                        <i class="fas fa-eye me-1"></i>Visualizar PDF
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="recibosBody">
                                @foreach($cotizaciones as $index => $cotizacion)
                                <tr class="border-bottom">
                                    <td class="ps-4 fw-bold text-primary">
                                        {{ $loop->iteration }}
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                                                <i class="fas fa-user text-primary"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold">{{ $cotizacion->nombre_cliente }}</h6>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2">
                                            <i class="fas fa-calendar me-1"></i>
                                            {{ \Carbon\Carbon::parse($cotizacion->fecha_creacion)->format('d/m/Y') }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-3 py-2">
                                            <i class="fas fa-clock me-1"></i>
                                            {{ \Carbon\Carbon::parse($cotizacion->fecha_creacion)->format('H:i') }}
                                        </span>
                                    </td>
                                    <td class="text-center pe-4">
                                        <div class="btn-group" role="group">
                                            @if($cotizacion->pdf_cliente)
                                            <button type="button" 
                                                    class="btn btn-outline-primary btn-sm px-3" 
                                                    onclick="verPDF({{ $cotizacion->id }}, 'cliente')"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    title="Abrir PDF para Cliente en nueva pestaña">
                                                <i class="fas fa-user-tie me-1"></i> Cliente
                                            </button>
                                            @endif
                                            
                                            @if($cotizacion->pdf_interno)
                                            <button type="button" 
                                                    class="btn btn-outline-info btn-sm px-3" 
                                                    onclick="verPDF({{ $cotizacion->id }}, 'interno')"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    title="Abrir PDF Interno en nueva pestaña">
                                                <i class="fas fa-building me-1"></i> Interno
                                            </button>
                                            @endif
                                            
                                             @if(auth()->user()->isAdmin())
                                            <button type="button" 
                                                    class="btn btn-outline-danger btn-sm px-3" 
                                                    onclick="eliminarCotizacion({{ $cotizacion->id }})"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    title="Eliminar Cotización">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                            @endif
                                            {{-- Agregar este botón después de los botones de PDF --}}
                                            <button type="button" 
                                                    class="btn btn-outline-warning btn-sm px-3" 
                                                    onclick="editarCotizacion({{ $cotizacion->id }})"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    title="Editar Cotización">
                                                <i class="fas fa-edit me-1"></i> Editar
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación Mejorada -->
                    <div class="d-flex justify-content-between align-items-center px-4 py-3 bg-light border-top">
                        <div class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Mostrando <strong>{{ $cotizaciones->firstItem() }} - {{ $cotizaciones->lastItem() }}</strong> de 
                            <strong>{{ $cotizaciones->total() }}</strong> registros
                        </div>
                        <nav aria-label="Page navigation">
                            {{ $cotizaciones->links('vendor.pagination.bootstrap-5') }}
                        </nav>
                    </div>
                    @else
                    <!-- Estado Vacío Mejorado -->
                    <div class="text-center py-5 my-4">
                        <div class="mb-4">
                            <i class="fas fa-file-pdf fa-5x text-primary opacity-25"></i>
                        </div>
                        <h3 class="text-muted fw-light mb-3">No hay cotizaciones registradas</h3>
                        <p class="text-muted mb-4">Comienza creando tu primera cotización para verla en el historial</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmación Mejorado -->
<div class="modal fade" id="modalConfirmarEliminar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-gradient-danger text-white">
                <div class="d-flex align-items-center">
                    <div class="bg-white bg-opacity-20 rounded-circle p-2 me-3">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h5 class="modal-title fw-bold">Confirmar Eliminación</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                <div class="text-center mb-3">
                    <i class="fas fa-trash-alt fa-3x text-danger opacity-50 mb-3"></i>
                </div>
                <h6 class="text-center fw-bold mb-2">¿Estás seguro de eliminar esta cotización?</h6>
                <p class="text-muted text-center mb-0">
                    Esta acción eliminará permanentemente la cotización y todos sus archivos PDF asociados. 
                    Esta acción no se puede deshacer.
                </p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i> Cancelar
                </button>
                <button type="button" class="btn btn-danger px-4" id="btnConfirmarEliminar">
                    <i class="fas fa-trash-alt me-2"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
@endsection