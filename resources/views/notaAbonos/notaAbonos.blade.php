@extends('layouts.nota_abonos.app-master')

@section('content')
<div class="container py-4">
    <!-- Header -->
    <div class="header mb-4">
        <h1 class="text-center"><i class="fas fa-users-cog me-2"></i>Gestión de Abonos y Regímenes</h1>
    </div>
    

    <!-- Versión con diseño de tarjeta -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center">
                <!-- Total de Clientes -->
                <div class="d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 rounded-circle p-3 me-3">
                        <i class="fas fa-users text-primary fa-lg"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-muted">Clientes Registrados</h6>
                        <h3 class="mb-0 fw-bold text-primary">{{ $clientes->total() }}</h3>
                    </div>
                </div>

                <!-- Botones -->
                <div class="d-flex gap-3">
                    <button class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#modalCliente">
                        <i class="fas fa-user-plus me-2"></i> Nuevo Cliente
                    </button>
                    <button class="btn btn-success d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#modalRegimen">
                        <i class="fas fa-file-invoice-dollar me-2"></i> Nuevo Régimen
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!--Cuadro de busqueda -->
    <!-- Barra de Búsqueda y Herramientas -->
    <div class="card shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <!-- Cuadro de búsqueda -->
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" id="busquedaClientes" class="form-control border-start-0" 
                            placeholder="Buscar clientes por nombre, teléfono" 
                            aria-label="Buscar clientes">
                        <button class="btn btn-outline-secondary" type="button" id="btnLimpiarBusqueda">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Clientes -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i>Lista de Clientes</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="tablaClientes">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Nombre</th>
                            <th>Teléfono</th>
                            <th>Correo</th>
                            <th>Régimen</th>
                            <th>Opciones</th>
                            <th>Abonos</th>
                        </tr>
                    </thead>
                    <tbody id="recibosBody">
                       <!-- Aquí se cargarán los clientes dinámicamente -->
                    @include('notaAbonos.notaAbonosPartials', ['clientes' => $clientes])
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
             <!-- Paginación -->
            <div id="paginacion" class="d-flex justify-content-between align-items-center mt-4">
    <div class="text-muted small">
        <i class="fas fa-list-alt me-1 text-primary"></i>
        Mostrando <strong>{{ $clientes->firstItem() }}</strong> - 
        <strong>{{ $clientes->lastItem() }}</strong> de 
        <strong class="text-primary">{{ $clientes->total() }}</strong> registros
    </div>
        <nav aria-label="Paginación de clientes">
            <ul class="pagination pagination-sm mb-0">
                {{-- Previous Page Link --}}
                @if ($clientes->onFirstPage())
                    <li class="page-item disabled">
                        <span class="page-link">
                            <i class="fas fa-chevron-left fa-xs"></i>
                        </span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $clientes->previousPageUrl() }}">
                            <i class="fas fa-chevron-left fa-xs"></i>
                        </a>
                    </li>
                @endif

                {{-- Pagination Elements --}}
                @foreach ($clientes->getUrlRange(1, $clientes->lastPage()) as $page => $url)
                    @if ($page == $clientes->currentPage())
                        <li class="page-item active">
                            <span class="page-link">{{ $page }}</span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                        </li>
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($clientes->hasMorePages())
                    <li class="page-item">
                        <a class="page-link" href="{{ $clientes->nextPageUrl() }}">
                            <i class="fas fa-chevron-right fa-xs"></i>
                        </a>
                    </li>
                @else
                    <li class="page-item disabled">
                        <span class="page-link">
                            <i class="fas fa-chevron-right fa-xs"></i>
                        </span>
                    </li>
                @endif
            </ul>
        </nav>
    </div>
        </div>
    </div>
</div>

<!-- Modal Cliente -->
<div class="modal fade" id="modalCliente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Registrar Nuevo Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formCliente">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombre completo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nombre" id="nombreCliente" required placeholder="Ej. Juan Pérez López">
                            <div id="nombreError" class="text-danger small mt-1" style="display: none;"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Correo electrónico</label>
                            <input type="email" class="form-control" name="correo" placeholder="ejemplo@correo.com">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Teléfono <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="telefono" placeholder="(000) 000-0000" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">RFC</label>
                            <input type="text" class="form-control" name="rfc" placeholder="Ej. XAXX010101000">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Régimen fiscal <span class="text-danger">*</span></label>
                            <select name="regimen_id" class="form-select" required>
                                <option value="">Seleccionar régimen</option>
                                @foreach($regimenes as $regimen)
                                    <option value="{{ $regimen->id }}">{{ $regimen->nombre }} (IVA: {{ $regimen->iva }}%, ISR: {{ $regimen->isr }}%)</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Dirección</label>
                            <textarea class="form-control" name="direccion" placeholder="direccion"></textarea>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observaciones</label>
                        <textarea class="form-control" name="observaciones" rows="3" placeholder="Notas adicionales sobre el cliente"></textarea>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i>Guardar Cliente
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Regimen -->
<div class="modal fade" id="modalRegimen" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-invoice-dollar me-2"></i>Registrar Nuevo Régimen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formRegimen">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Nombre del régimen <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nombre" required placeholder="Ej. Régimen General de Ley">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">IVA (%) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.01" class="form-control" name="iva" required placeholder="0.00" min="0" max="100">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ISR (%) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.01" class="form-control" name="isr" required placeholder="0.00" min="0" max="100">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-check-circle me-2"></i>Guardar Régimen
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Abonos (Ejemplo) -->
<div class="modal fade" id="modalAbonos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-money-bill-wave me-2"></i>Abonos del Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="contenidoAbonos">
                    <!-- Aquí se cargará el contenido de abonos dinámicamente -->
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal para Ver Cliente - Versión Mejorada -->
<div class="modal fade" id="modalVerCliente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title"><i class="fas fa-user-tie me-2"></i>Detalles del Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Columna Izquierda -->
                    <div class="col-md-6">
                        <div class="info-item">
                            <span class="info-label"><i class="fas fa-user me-2"></i>Nombre:</span>
                            <span id="clienteNombre" class="info-value">Cargando...</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label"><i class="fas fa-phone me-2"></i>Teléfono:</span>
                            <span id="clienteTelefono" class="info-value">Cargando...</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label"><i class="fas fa-envelope me-2"></i>Correo:</span>
                            <span id="clienteCorreo" class="info-value">Cargando...</span>
                        </div>
                    </div>
                    
                    <!-- Columna Derecha -->
                    <div class="col-md-6">
                        <div class="info-item">
                            <span class="info-label"><i class="fas fa-id-card me-2"></i>RFC:</span>
                            <span id="clienteRfc" class="info-value">Cargando...</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label"><i class="fas fa-map-marker-alt me-2"></i>Dirección:</span>
                            <span id="clienteDireccion" class="info-value">Cargando...</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label"><i class="fas fa-file-invoice-dollar me-2"></i>Régimen:</span>
                            <span id="clienteRegimen" class="info-value">Cargando...</span>
                        </div>
                    </div>
                </div>

                <!-- Sección de Saldo Global -->
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white py-2">
                                <h6 class="mb-0"><i class="fas fa-wallet me-2"></i>Información de Saldo</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-item">
                                            <span class="info-label"><strong>Saldo Global:</strong></span>
                                            <span id="clienteSaldoGlobal" class="info-value fs-5 text-success fw-bold">$0.00</span>
                                        </div>
                                    </div>       
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Observaciones -->
                <div class="info-item-full mt-3">
                    <span class="info-label"><i class="fas fa-clipboard-list me-2"></i>Observaciones:</span>
                    <span id="clienteObservaciones" class="info-value">Cargando...</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Modal para Editar Cliente -->
<div class="modal fade" id="modalEditarCliente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarCliente">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="edit_cliente_id" name="id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombre completo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
                            <div id="edit_nombreError" class="text-danger small mt-1" style="display: none;"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Correo electrónico</label>
                            <input type="email" class="form-control" id="edit_correo" name="correo">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Teléfono <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_telefono" name="telefono" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">RFC</label>
                            <input type="text" class="form-control" id="edit_rfc" name="rfc">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Régimen fiscal <span class="text-danger">*</span></label>
                            <select name="regimen_id" id="edit_regimen_id" class="form-select" required>
                                <option value="">Seleccionar régimen</option>
                                @foreach($regimenes as $regimen)
                                    <option value="{{ $regimen->id }}">{{ $regimen->nombre }} (IVA: {{ $regimen->iva }}%, ISR: {{ $regimen->isr }}%)</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Dirección</label>
                            <textarea class="form-control" id="edit_direccion" name="direccion" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observaciones</label>
                        <textarea class="form-control" id="edit_observaciones" name="observaciones" rows="3"></textarea>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i>Actualizar Cliente
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection