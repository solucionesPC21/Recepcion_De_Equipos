@extends('layouts.cotizacion.app-master')
@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <!-- Formulario de Cotización -->
        <div class="col-lg-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Nueva Cotización</h5>
                    <a href="{{ route('cotizaciones.historial') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-history me-1"></i> Ver Historial de Cotizaciones
                    </a>
                </div>
                <div class="card-body">
                    <form action="" method="POST" id="cotizacionForm">
                        @csrf
                        <!-- Información del Cliente -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Cliente *</label>
                                <input type="text" class="form-control" id="cliente" placeholder="Ingresar Cliente" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Tipo de Cliente *</label>
                                <select class="form-select" id="tipoCliente" required>
                                    <option value="" disabled selected>Selecciona una opción</option>
                                    <option value="persona_fisica">Persona Física</option>
                                    <option value="publico_general">Público General</option>
                                    <option value="persona_moral">Persona Moral</option>
                                </select>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Persona Moral aplica ISR 1.25%
                                </small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Válido hasta *</label>
                                <input type="date" class="form-control" name="valido_hasta" required>
                            </div>
                            
                            <!-- Campos opcionales del cliente -->
                            <div class="col-md-6 mt-3">
                                <label class="form-label">Dirección <small class="text-muted">(Opcional)</small></label>
                                <input type="text" class="form-control" id="direccionCliente" placeholder="Ingresar dirección del cliente">
                            </div>
                            <div class="col-md-6 mt-3">
                                <label class="form-label">Teléfono <small class="text-muted">(Opcional)</small></label>
                                <input type="text" class="form-control" id="telefonoCliente" placeholder="Ingresar teléfono del cliente">
                            </div>
                            
                            <!-- Configuración de Transporte y Descuento -->
                            <div class="col-12 mt-3">
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0"><i class="fas fa-cogs me-2"></i>Configuración General</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <!-- Configuración de Transporte -->
                                            <div class="col-md-6 border-end">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold text-primary">
                                                        <i class="fas fa-truck me-1"></i>Aplicar Transporte
                                                    </label>
                                                    <select class="form-select" id="transporteGeneral">
                                                        <option value="no">No aplicar transporte</option>
                                                        <option value="si">Aplicar transporte automático</option>
                                                    </select>
                                                </div>
                                                <div class="alert alert-info mb-0">
                                                    <small>
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        Cuando está activado, se aplicará automáticamente $12 de transporte a todos los productos con precio costo mayor a $30
                                                    </small>
                                                </div>
                                            </div>

                                            <!-- Configuración de Descuento -->
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold text-warning">
                                                        <i class="fas fa-tag me-1"></i>Descuento sobre Productos
                                                    </label>
                                                    <div class="input-group">
                                                        <input type="number" class="form-control" id="descuentoPorcentaje" 
                                                            min="0" max="100" step="0.1" placeholder="0.0">
                                                        <span class="input-group-text">%</span>
                                                        <button class="btn btn-success" type="button" id="btnAplicarDescuento" onclick="aplicarDescuento()">
                                                            <i class="fas fa-check me-1"></i> Aplicar
                                                        </button>
                                                        <button class="btn btn-outline-secondary" type="button" id="btnQuitarDescuento" 
                                                                onclick="quitarDescuento()" style="display: none;">
                                                            <i class="fas fa-times me-1"></i> Quitar
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="alert alert-warning mb-0">
                                                    <small>
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        El descuento se aplica al precio sin IVA de cada producto individualmente
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- El resto del HTML se mantiene igual -->
                        <!-- Formulario para agregar productos manualmente -->
 <div class="card mb-4">
    <div class="card-header bg-secondary text-white">
        <h6 class="mb-0"><i class="fas fa-cube me-2"></i>Agregar Producto</h6>
    </div>
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <!-- Columna 1: Clave -->
            <div class="col-md-2">
                <label for="claveProducto" class="form-label">Clave</label>
                <input type="text" class="form-control" id="claveProducto" 
                       placeholder="LAP-001">
            </div>
            
            <!-- Columna 2: Nombre -->
            <div class="col-md-3">
                <label for="nombreProducto" class="form-label">Producto *</label>
                <input type="text" class="form-control" id="nombreProducto" 
                       placeholder="Nombre del producto" required>
            </div>
            
            <!-- Columna 3: Cantidad -->
            <div class="col-md-1">
                <label for="cantidadProducto" class="form-label">Cant *</label>
                <input type="number" class="form-control" id="cantidadProducto" 
                       value="1" min="1" required>
            </div>
            
            <!-- Columna 4: Precio -->
            <div class="col-md-2">
                <label for="precioProducto" class="form-label">Costo ($) *</label>
                <input type="number" class="form-control" id="precioProducto" 
                       step="0.01" min="0" placeholder="0.00" required>
            </div>
            
            <!-- Columna 5: Switch Sin Ganancia -->
            <div class="col-md-2">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="sinGanancia">
                    <label class="form-check-label" for="sinGanancia">
                        <strong>Sin Ganancia</strong>
                    </label>
                </div>
                <small class="text-muted">Precio final = Costo</small>
            </div>
            
            <!-- Columna 6: Precios calculados -->
            <div class="col-md-2">
                <div class="border-start ps-3">
                    <small class="text-muted d-block">Sin IVA:</small>
                    <span class="fw-bold text-primary" id="precioSinIvaProducto">$0.00</span>
                    
                    <small class="text-muted d-block mt-1">Final:</small>
                    <span class="fw-bold text-success" id="precioFinalProducto">$0.00</span>
                </div>
            </div>
            
            <!-- Columna 7: Botón -->
            <div class="col-md-2">
                <button type="button" class="btn btn-primary w-100 h-100" 
                        id="btnAgregarProducto">
                    <i class="fas fa-plus me-1"></i> Agregar
                </button>
            </div>
        </div>
        
        <!-- Información adicional debajo -->
        <div class="row mt-3">
            <div class="col-12">
                <div id="errorMessages" class="alert alert-danger" style="display: none;"></div>
                <div class="alert alert-info py-2">
                    <small>
                        <i class="fas fa-truck me-1"></i>
                        <strong>Transporte:</strong> $12 si costo > $30 | 
                        <i class="fas fa-calculator ms-2 me-1"></i>
                        <strong>Fórmula:</strong> (Costo + IVA + Transporte) / (1 - % Ganancia) + IVA final
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
                        <!-- Lista de Productos -->
                        <div class="table-responsive mb-4">
                            <table class="table table-hover" id="tablaProductos">
                                <thead class="table-light">
                                  <tr>
                                        
                                        <th width="8%" class="text-center">Clave</th>
                                        <th width="30%">Producto / Descripción</th>
                                        <th width="6%" class="text-center">Cantidad</th>
                                        <th width="10%" class="text-center">Precio Costo</th>
                                        <th width="8%" class="text-center">Transporte</th>
                                        <th width="8%" class="text-center">% Ganancia</th>
                                        <th width="10%" class="text-center">Precio Sin IVA</th>
                                        <th width="10%" class="text-center">Precio Final</th>
                                        <th width="10%" class="text-center">Subtotal</th>
                                        <th width="5%" class="text-center">Mover</th>
                                        <th width="5%" class="text-center">Eliminar</th>
                                    </tr>
                                </thead>
                                <tbody id="productosCotizacion">
                                    <!-- Los productos se agregarán aquí dinámicamente -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Resumen de Totales -->
                        <div class="row">
                            <div class="col-md-6 offset-md-6">
                                <div class="card border-0 bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title fw-bold mb-3">Resumen de la Cotización</h6>
                                        <div class="row mb-2">
                                            <div class="col-6">
                                                <strong>Subtotal:</strong>
                                            </div>
                                            <div class="col-6 text-end">
                                                <span id="subtotal">$0.00</span>
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-6">
                                                <strong>IVA 8% Final:</strong>
                                            </div>
                                            <div class="col-6 text-end">
                                                <span id="ivaFinalTotal" class="text-info">$0.00</span>
                                            </div>
                                        </div>
                                        <div class="row mb-2" id="isrRow" style="display: none;">
                                            <div class="col-6">
                                                <strong>ISR 1.25%:</strong>
                                            </div>
                                            <div class="col-6 text-end">
                                                <span id="isrTotal" class="text-warning">$0.00</span>
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-6">
                                                <strong>Total Transporte:</strong>
                                            </div>
                                            <div class="col-6 text-end">
                                                <span id="totalTransporte">$0.00</span>
                                            </div>
                                        </div>

                                        <!-- NUEVO: Acumulador de Descuentos -->
                                        <div class="row mb-2" id="totalDescuentoRow" style="display: none;">
                                            <div class="col-6">
                                                <strong>Total Descuento (<span id="porcentajeDescuentoTotal">0%</span>):</strong>
                                            </div>
                                            <div class="col-6 text-end">
                                                <span id="totalDescuento" class="text-danger">-$0.00</span>
                                            </div>
                                        </div>
                                        <div class="row mb-2 pt-2 border-top">
                                            <div class="col-6">
                                                <strong>Total General:</strong>
                                            </div>
                                            <div class="col-6 text-end">
                                                <span id="total" class="fw-bold text-success fs-5">$0.00</span>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-12">
                                                <small class="text-muted" id="infoImpuestos">
                                                    <!-- Información de impuestos se mostrará aquí -->
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <button type="button" class="btn btn-outline-success" id="btnAgregarMas">
                                        <i class="fas fa-plus-circle me-1"></i> Agregar Más Productos
                                    </button>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-success" onclick="generarYGuardarPDF()">
                                            <i class="fas fa-file-pdf me-1"></i> Generar PDF
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" id="generarPdfUrl" value="{{ route('cotizaciones.generar-pdf') }}">
</div>
@endsection