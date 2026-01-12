@extends('layouts.abonos.app-master')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="display-4"><i class="fas fa-hand-holding-usd text-primary"></i> Gestión de Abonos</h1>
        </div>
        <div class="col-md-4 text-right">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevaVentaModal">
                <i class="fas fa-plus"></i> Nueva Venta a Abonos
            </button>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h3 class="card-title mb-0"><i class="fas fa-list"></i> Historial de Ventas a Abonos</h3>
                </div>
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" 
                               id="searchInput" 
                               class="form-control" 
                               placeholder="Buscar por cliente...">
                        <select id="estadoFilter" class="form-select" style="max-width: 200px;">
                            <option value="">Todos los estados</option>
                            <option value="1">Pendiente</option>
                            <option value="2">Pagado</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="ventasTable">
                    <thead class="thead-light">
                        <tr>
                            <th width="5%">ID</th>
                            <th width="20%">Cliente</th>
                            <th width="8%">Teléfono</th>
                            <th width="8%" class="text-center">Detalles</th>
                            <th width="10%">Total</th>
                            <th width="10%">Saldo</th>
                            <th width="6%">Fecha</th>
                            <th width="8%" class="text-center">Estado</th>
                            <th width="25%" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="recibosBody">
                        @foreach($ventas as $venta)
                        <tr data-venta-id="{{ $venta->id }}">
                            <td class="text-center">{{ $venta->id }}</td>
                            <td>{{ $venta->cliente->nombre }}</td>
                            <td>{{ $venta->cliente->telefono }}</td>
                            <td class="text-center">
                                <a href="{{ route('ventas-abonos.pdf', $venta->id) }}" 
                                   target="_blank" 
                                   class="btn btn-sm btn-outline-primary"
                                   title="Ver PDF de venta">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </a>
                            </td>
                            <td class="text-right font-weight-bold">
                                <span id="total-display-{{ $venta->id }}">${{ number_format($venta->total, 2) }}</span>
                                @if(auth()->user()->isAdmin())
                                <button class="btn btn-sm btn-outline-secondary ms-1 btn-editar-total" 
                                        data-venta-id="{{ $venta->id }}"
                                        data-total-actual="{{ $venta->total }}"
                                        data-cliente="{{ $venta->cliente->nombre }}"
                                        data-saldo-restante="{{ $venta->saldo_restante }}"
                                        title="Editar total">
                                    <i class="fas fa-edit"></i>
                                </button>
                                @endif
                            </td>
                            <td class="text-right">
                                <span class="px-2 py-1 rounded text-white saldo-display 
                                      bg-{{ $venta->saldo_restante > 0 ? 'warning' : 'success' }}" 
                                      id="saldo-display-{{ $venta->id }}">
                                    ${{ number_format($venta->saldo_restante, 2) }}
                                </span>
                            </td>
                            <td class="text-center">{{ $venta->fecha_venta->format('d/m/Y H:i') }}</td>
                            <td class="text-center">
                                <span class="px-2 py-1 rounded text-white 
                                      bg-{{ $venta->estado->id == 1 ? 'danger' : ($venta->estado->id == 2 ? 'success' : 'secondary') }}">
                                    {{ $venta->estado->id == 1 ? 'Pendiente' : ($venta->estado->id == 2 ? 'Pagado' : $venta->estado->nombre) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    @if($venta->saldo_restante > 0)
                                    <button class="btn btn-sm btn-primary btn-abonar" 
                                            data-venta-id="{{ $venta->id }}"
                                            data-saldo="{{ $venta->saldo_restante }}"
                                            data-cliente="{{ $venta->cliente->nombre }}"
                                            data-productos="{{ $venta->detalles->map(function($item) {
                                                return $item->cantidad.'x '.$item->concepto->nombre;
                                            })->implode(', ') }}"
                                            title="Registrar abono">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </button>
                                    @endif
                                    
                                    <button class="btn btn-sm btn-info btn-historial" 
                                            data-venta-id="{{ $venta->id }}"
                                            title="Ver historial de abonos">
                                        <i class="fas fa-history"></i>
                                    </button>
                                    
                                    
                                    @if($venta->estado->id != 2)
                                    <button class="btn btn-sm btn-warning btn-editar-productos" 
                                            data-venta-id="{{ $venta->id }}"
                                            data-cliente="{{ $venta->cliente->nombre }}"
                                            title="Editar productos">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    @endif
                                    @if(auth()->user()->isAdmin())
                                    <button class="btn btn-sm btn-danger btn-eliminar-venta" 
                                            data-venta-id="{{ $venta->id }}"
                                            data-cliente="{{ $venta->cliente->nombre }}"
                                            data-total="${{ number_format($venta->total, 2) }}"
                                            title="Eliminar venta">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="d-flex justify-content-center mt-4" id="paginacion">
                    {{ $ventas->appends(request()->query())->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ==============================================
MODALES
============================================== -->

<!-- Modal: Nueva Venta -->
<div class="modal fade" id="nuevaVentaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-cart-plus"></i> Nueva Venta a Abonos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="nuevaVentaForm">
                    @csrf
                    
                    <!-- Cliente -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Cliente <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" 
                                   class="form-control" 
                                   id="buscarCliente" 
                                   placeholder="Buscar cliente existente..."
                                   autocomplete="off">
                            <input type="hidden" id="cliente_id">
                            <button class="btn btn-outline-primary" type="button" id="btnNuevoCliente">
                                <i class="fas fa-user-plus"></i> Nuevo
                            </button>
                        </div>
                        <div id="resultadosClientes" class="mt-2 d-none">
                            <div class="list-group" id="listaClientes"></div>
                        </div>
                        <div id="cliente-seleccionado" class="mt-2 d-none">
                            <div class="alert alert-success py-2 mb-0">
                                <i class="fas fa-check-circle"></i>
                                <strong>Cliente seleccionado:</strong>
                                <span id="nombre-cliente"></span>
                                <button type="button" class="btn btn-sm btn-link float-end p-0" id="cambiar-cliente">
                                    <i class="fas fa-times"></i> Cambiar
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Productos -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Productos <span class="text-danger">*</span></label>
                        
                        <!-- Buscador de productos -->
                        <div class="input-group mb-2">
                            <input type="text" 
                                   class="form-control buscar-producto-venta" 
                                   placeholder="Buscar producto en inventario..."
                                   autocomplete="off">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                        </div>
                        <div class="list-group resultados-productos-venta d-none mb-3"></div>
                        
                        <!-- Lista de productos -->
                        <div id="productos-container-venta">
                            <div class="row producto-item-venta mb-2 align-items-center">
                                <input type="hidden" class="id-concepto" name="productos[0][id_concepto]">
                                
                                <div class="col-md-5">
                                    <input type="text" 
                                           class="form-control producto-nombre" 
                                           name="productos[0][nombre]" 
                                           placeholder="Nombre del producto" 
                                           required>
                                </div>
                                <div class="col-md-2">
                                    <input type="number" 
                                           class="form-control producto-precio" 
                                           name="productos[0][precio]" 
                                           min="0" 
                                           step="0.01" 
                                           placeholder="Precio" 
                                           required>
                                </div>
                                <div class="col-md-2">
                                    <input type="number" 
                                           class="form-control cantidad" 
                                           name="productos[0][cantidad]" 
                                           min="1" 
                                           value="1" 
                                           placeholder="Cant" 
                                           required>
                                </div>
                                <div class="col-md-2">
                                    <input type="text" 
                                           class="form-control subtotal text-end" 
                                           value="$0.00" 
                                           readonly>
                                </div>
                                <div class="col-md-1">
                                    <button type="button" 
                                            class="btn btn-danger btn-remove-producto-venta" 
                                            disabled>
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="btn-add-producto-venta">
                            <i class="fas fa-plus"></i> Agregar otro producto
                        </button>
                    </div>
                    
                    <!-- Abono inicial -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Abono Inicial <small class="text-muted">(Opcional)</small></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" 
                                   class="form-control" 
                                   id="abono_inicial" 
                                   name="abono_inicial" 
                                   step="0.01" 
                                   min="0" 
                                   placeholder="0.00">
                        </div>
                    </div>
                    
                    <!-- Método de pago -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Método de Pago <span class="text-danger">*</span></label>
                        <select class="form-select" id="tipo_pago_id" name="tipo_pago_id" required>
                            <option value="">Seleccionar método de pago</option>
                            @foreach($tiposPago as $tipo)
                            <option value="{{ $tipo->id }}">{{ $tipo->tipoPago }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Total -->
                    <div class="mb-3 border-top pt-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Total de la venta:</h5>
                            <h3 class="mb-0 text-primary" id="total-venta">$0.00</h3>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="btnGuardarVenta">
                    <i class="fas fa-save"></i> Guardar Venta
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Nuevo Cliente -->
<div class="modal fade" id="nuevoClienteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-user-plus"></i> Nuevo Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="nuevoClienteForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="text" class="form-control" name="telefono" placeholder="Opcional">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarCliente">
                    <i class="fas fa-save"></i> Guardar Cliente
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Registrar Abono -->
<div class="modal fade" id="abonoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-money-bill-wave"></i> Registrar Abono</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="abonoForm">
                    @csrf
                    <input type="hidden" name="venta_id" id="venta_id_abono">
                    
                    <div class="mb-3">
                        <label class="form-label">Cliente</label>
                        <input type="text" class="form-control" id="abono_cliente" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Productos</label>
                        <textarea class="form-control" id="abono_productos" rows="2" readonly></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Saldo Restante</label>
                        <input type="text" class="form-control bg-light" id="abono_saldo" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Monto a Abonar <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" 
                                   class="form-control" 
                                   name="monto" 
                                   id="abono_monto" 
                                   step="0.01" 
                                   min="0.01" 
                                   placeholder="0.00" 
                                   required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Método de Pago <span class="text-danger">*</span></label>
                        <select class="form-select" name="tipo_pago_id1" required>
                            <option value="">Seleccionar método de pago</option>
                            @foreach($tiposPago as $tipo)
                            <option value="{{ $tipo->id }}">{{ $tipo->tipoPago }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnRegistrarAbono">
                    <i class="fas fa-check"></i> Registrar Abono
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Historial de Abonos -->
<div class="modal fade" id="historialModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-history"></i> Historial de Abonos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h5 id="historialCliente" class="mb-1"></h5>
                        <p class="text-muted mb-0" id="historialProductos"></p>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="mb-2">
                            <strong>Total Venta:</strong> 
                            <span id="historialTotal" class="fw-bold"></span>
                        </div>
                        <div>
                            <strong>Saldo Pendiente:</strong> 
                            <span id="historialSaldo" class="fw-bold"></span>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">#</th>
                                <th width="20%">Fecha</th>
                                <th width="15%">Monto</th>
                                <th width="15%">Método</th>
                                <th width="15%">Comprobante</th>
                                <th width="30%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="historialBody">
                            <!-- Cargado dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Editar Total -->
<div class="modal fade" id="editarTotalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Editar Total de Venta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editarTotalForm">
                    @csrf
                    <input type="hidden" id="editar_total_venta_id">
                    <input type="hidden" id="editar_total_saldo_restante">
                    
                    <div class="mb-3">
                        <label class="form-label">Cliente</label>
                        <input type="text" class="form-control" id="editar_total_cliente" readonly>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Total Actual</label>
                            <input type="text" class="form-control bg-light" id="editar_total_actual" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Saldo Actual</label>
                            <input type="text" class="form-control bg-light" id="editar_total_saldo_actual" readonly>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Nuevo Total <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" 
                                   class="form-control" 
                                   id="editar_total_nuevo" 
                                   step="0.01" 
                                   min="0" 
                                   required>
                        </div>
                        <small class="form-text text-muted">El saldo se ajustará automáticamente.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Nuevo Saldo Calculado</label>
                        <input type="text" class="form-control bg-light" id="editar_total_nuevo_saldo" readonly>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-info text-white" id="btnActualizarTotal">
                    <i class="fas fa-save"></i> Actualizar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Editar Productos -->
<div class="modal fade" id="editarProductosModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-boxes"></i> Editar Productos de Venta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editarProductosForm">
                    @csrf
                    <input type="hidden" id="editar_productos_venta_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Cliente</label>
                        <input type="text" class="form-control" id="editar_productos_cliente" readonly>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Total Actual</label>
                            <input type="text" class="form-control bg-light" id="editar_productos_total_actual" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Total Abonado</label>
                            <input type="text" class="form-control bg-light" id="editar_productos_total_abonado" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Saldo Actual</label>
                            <input type="text" class="form-control bg-light" id="editar_productos_saldo_actual" readonly>
                        </div>
                    </div>
                    
                    <!-- Productos -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Productos <span class="text-danger">*</span></label>
                        
                        <!-- Buscador -->
                        <div class="input-group mb-2">
                            <input type="text" 
                                   class="form-control buscar-producto-editar" 
                                   placeholder="Buscar producto en inventario..."
                                   autocomplete="off">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                        </div>
                        <div class="list-group resultados-productos-editar d-none mb-3"></div>
                        
                        <!-- Lista de productos -->
                        <div id="productos-editar-container">
                            <!-- Cargado dinámicamente -->
                        </div>
                        
                        <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="btn-add-producto-editar">
                            <i class="fas fa-plus"></i> Agregar producto manual
                        </button>
                    </div>
                    
                    <!-- Resumen -->
                    <div class="alert alert-info">
                        <h6 class="alert-heading mb-3">Resumen de cambios:</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Nuevo Total:</strong><br>
                                <span id="resumen-nuevo-total" class="fw-bold fs-5">$0.00</span>
                            </div>
                            <div class="col-md-4">
                                <strong>Nuevo Saldo:</strong><br>
                                <span id="resumen-nuevo-saldo" class="fw-bold fs-5">$0.00</span>
                            </div>
                            <div class="col-md-4">
                                <strong>Estado:</strong><br>
                                <span id="resumen-nuevo-estado" class="fw-bold fs-5 text-danger">Pendiente</span>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning text-white" id="btnActualizarProductos">
                    <i class="fas fa-save"></i> Actualizar Productos
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Variable global para verificar si es admin
    window.isAdmin = {{ auth()->user()->isAdmin() ? 'true' : 'false' }};
    
    // Variable global para manejar la búsqueda
    let timeoutBusqueda = null;
</script>
@endpush