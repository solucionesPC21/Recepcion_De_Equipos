@extends('layouts.ticket.app-master')

@section('content')
<div class="container-fluid">
    <!-- Header Mejorado -->
    <div class="dashboard-header">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="dashboard-title">
                    <i class="fas fa-ticket-alt me-3"></i>
                    Sistema de Tickets
                </h1>
                <p class="dashboard-subtitle">Gestión y cobro de servicios técnicos</p>
            </div>
            <div class="col-md-6 text-end">
                <div class="stats-card">
                    <div class="stat-item">
                        <span class="stat-number">{{ $totalRecibos }}</span>
                        <span class="stat-label">Órdenes Totales</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Barra de Herramientas -->
    <div class="toolbar-card">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" 
                           id="searchInput" 
                           class="search-input" 
                           placeholder="Buscar por cliente,recibo"
                           onkeyup="buscarRecibos()">
                </div>
            </div>
        </div>
    </div>

    <!-- Panel de Selección Activa -->
    <div class="selection-panel" id="selectionPanel" style="display: none;">
        <div class="selection-content">
            <div class="selection-info">
                <div class="selected-client-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="selection-details">
                    <h5 id="selectedClientName"></h5>
                    <p class="text-muted mb-0" id="selectedReciboInfo"></p>
                </div>
            </div>
            <div class="selection-actions">
                <button class="btn btn-success btn-generate-ticket" onclick="generarTicketSeleccionado()">
                    <i class="fas fa-receipt me-2"></i>
                    Generar Ticket de Cobro
                </button>
                <button class="btn btn-outline-secondary" onclick="deseleccionarCliente()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Grid de Recibos -->
    <div class="recibos-grid" id="recibosGrid">
        @foreach($recibos as $recibo)
        <div class="recibo-card {{ $recibo->estado->id == 3 ? 'completado' : 'pendiente' }}" 
             id="recibo-{{ $recibo->id }}"
             onclick="seleccionarRecibo({{ $recibo->id }})">
            
            <!-- Header de la Tarjeta -->
            <div class="recibo-header">
                
                <div class="recibo-actions">
                    <button class="btn-action" onclick="event.stopPropagation(); abrirNotaModal({{ $recibo->id }})" 
                            title="Ver comentarios">
                        <i class="fas fa-comment"></i>
                    </button>
                   
                    <button class="btn-action" onclick="event.stopPropagation(); abrirCompletadoConfirmar({{ $recibo->id }})" 
                            title="Marcar completado">
                        <i class="fas fa-check-circle"></i>
                    </button>
                  
                    <!-- Ver archivos -->
                    <button class="btn-action" onclick="event.stopPropagation(); abrirModalSubirArchivos({{ $recibo->id }})"
                            title="Subir archivos">
                            <i class="fas fa-cloud-upload-alt fa-lg" style="color: #007bff;"></i>
                    </button>

                    <button class="btn-action" onclick="event.stopPropagation(); abrirModalVerArchivos({{ $recibo->id }})"
                                title="Ver archivos">
                            <i class="fas fa-folder-open fa-lg" style="color: #ffc107;"></i>
                    </button>
                </div>
            </div>

            <!-- Información del Cliente -->
            <div class="client-info">
                <div class="client-avatar">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="client-details">
                    <h6 class="client-name">
                        @if(isset($recibo->tipoEquipo[0]->cliente))
                            {{ $recibo->tipoEquipo[0]->cliente->nombre }}
                        @else
                            Cliente No Encontrado
                        @endif
                    </h6>
                    <p class="client-meta">Recibo #{{ $recibo->id }}</p>
                </div>
            </div>

            <!-- Línea de Tiempo -->
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <span class="timeline-date">{{ date('d M Y', strtotime($recibo->created_at)) }}</span>
                        <span class="timeline-label">Recibido</span>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-marker {{ $recibo->estado->id == 3 ? 'completed' : '' }}"></div>
                    <div class="timeline-content">
                        <span class="timeline-date">{{ date('d M Y', strtotime($recibo->fechaReparacion)) }}</span>
                        <span class="timeline-label">Reparado</span>
                    </div>
                </div>
            </div>

            <!-- Acciones Rápidas -->
            <div class="recibo-actions-footer">
                <button class="btn-quick-action" 
                        onclick="event.stopPropagation(); descargarNotaRecepcion({{ $recibo->id }})"
                        title="Descargar nota de recepción">
                    <i class="fas fa-file-pdf"></i>
                    PDF
                </button>
                <button class="btn-quick-action primary" 
                        onclick="event.stopPropagation(); confirmarGenerarTicket({{ $recibo->id }})"
                        title="Generar ticket de pago">
                    <i class="fas fa-receipt"></i>
                    Ticket
                </button>
            </div>

            <!-- Indicador de Selección -->
            <div class="selection-indicator">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Paginación Mejorada -->
    <div class="pagination-container">
        <nav aria-label="Paginación">
            <ul class="pagination justify-content-center">
                {{ $recibos->links() }}
            </ul>
        </nav>
    </div>

    <!-- Empty State -->
    <div class="empty-state" id="emptyState" style="display: none;">
        <div class="empty-state-icon">
            <i class="fas fa-search"></i>
        </div>
        <h4>No se encontraron resultados</h4>
        <p>Intenta con otros términos de búsqueda</p>
        <button class="btn btn-primary" onclick="limpiarBusqueda()">
            <i class="fas fa-refresh me-2"></i>
            Mostrar todos
        </button>
    </div>
</div>

<!-- Modal para Notas -->
<!-- Modal para Notas -->
<div class="modal fade" id="notaModal" tabindex="-1" aria-labelledby="notaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-sticky-note me-2"></i>
                    Notas del Recibo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="notes-container">
                    <div class="note-view" id="noteView">
                        <p id="notaContent" class="note-content"></p>
                    </div>
                    <div class="note-edit" id="noteEdit" style="display: none;">
                        <textarea id="notaInput" class="note-textarea form-control" placeholder="Escribe tus notas aquí..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>
                    Cerrar
                </button>
                <button type="button" class="btn btn-outline-primary" id="editNotaButton" onclick="habilitarEdicionNota()">
                    <i class="fas fa-edit me-2"></i>
                    Editar
                </button>
                <!-- EN TU MODAL - CORREGIR EL BOTÓN GUARDAR -->
            <!-- BOTÓN GUARDAR CORREGIDO -->
            <!-- ✅ BOTÓN GUARDAR CORREGIDO - AGREGAR ONCLICK -->
                <button type="button" class="btn btn-primary" id="guardarNotaButton" style="display: none;" onclick="guardarNota()">
                    <i class="fas fa-save me-2"></i>
                    Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para subir archivos -->
<div class="modal fade" id="modalSubirArchivos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Subir Archivos</h5>
                <!-- BOTÓN DE CERRAR (NECESARIO) -->
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">

                <input 
                    type="file" 
                    id="inputArchivos" 
                    multiple 
                    class="form-control"
                    onchange="validarArchivos(event)">
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button class="btn btn-primary" onclick="guardarArchivos()">Subir</button>
            </div>

        </div>
    </div>
</div>


<!-- Modal para ver archivos -->
<div class="modal fade" id="modalVerArchivos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Archivos del Recibo</h5>
                <!-- BOTÓN DE CERRAR -->
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">
                <ul id="listaArchivos"></ul>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>

        </div>
    </div>
</div>



@include('ticket.generarTicket')
@endsection
