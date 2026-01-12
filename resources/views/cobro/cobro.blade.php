@extends('layouts.cobro.app-master')
@section('content')
@include('cobro.registroCliente')

<form id="checkoutForm" action="/ventas/realizar-cobro" method="POST">
    @csrf
    <input type="hidden" name="client_name" id="clientNameHidden">
    <input type="hidden" name="client_phone" id="clientPhoneHidden">
    <input type="hidden" name="payment_method" id="paymentMethodHidden">
    
    <div class="container-fluid">
        <div class="row">
            <!-- Panel de Cliente -->
           <!-- Cambiar el panel de cliente para hacerlo opcional -->
            <div class="col-md-4">
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user me-2"></i> Información del Cliente (Opcional)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="search-container">
                            <input type="text" name="search" id="search" placeholder="Buscar cliente por nombre">
                            <div class="dropdown1">
                                <ul id="searchResults" class="dropdown-menu1 hidden"></ul>
                            </div>
                        </div>
                        <br>
                        
                        <div class="mb-3">
                            <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#registerClientModal">
                                <i class="fas fa-user-plus me-2"></i> Registrar Nuevo Cliente
                            </button>
                        </div>
                        <div id="clientInfo" class="d-none">
                            <h6 class="mt-3">Cliente Seleccionado:</h6>
                            <p><strong>Nombre:</strong> <span id="clientName"></span></p>
                            <p><strong>Teléfono:</strong> <span id="clientPhone"></span></p>
                            <button type="button" class="btn btn-danger w-100" id="clearClient">
                                <i class="fas fa-times me-2"></i> Cambiar Cliente
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel de Productos -->
            <div class="col-md-8">
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-boxes me-2"></i> Productos Disponibles
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <input type="text" class="form-control" placeholder="Buscar producto por nombre o código..." id="searchProduct">
                            </div>
                        </div>
                        <div class="row d-flex justify-content-start" id="productList"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel de Cobro -->
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-cash-register me-2"></i> Punto de Venta
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="cartTable">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Modelo</th>
                                        <th>Cantidad</th>
                                        <th>P. Unitario</th>
                                        <th>Total</th>
                                        <th>Eliminar</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            <h6>Resumen de la Venta</h6>
                            <div class="d-flex justify-content-between">
                                <span>Total:</span>
                                <span id="total">$0.00</span>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="button" class="btn btn-danger w-100 mb-2" id="clearCart">
                                <i class="fas fa-trash me-2"></i> Borrar Todos Los Productos De La Compra
                            </button>
                            <button type="button" class="btn btn-success w-100" id="checkout">
                                <i class="fas fa-credit-card me-2"></i> Cobrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmación de Cobro -->
    <div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="checkoutModalLabel">Confirmar Cobro</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
        <div class="modal-body">
            <p>¿Estás seguro de realizar el cobro?</p>
            <p><strong>Total a cobrar:</strong> <span id="modalTotal">$0.00</span></p>
            <div class="mt-3">
                <label for="paymentMethodSelect" class="form-label"><strong>Método de pago:</strong></label>
                <select class="form-select" id="paymentMethodSelect" name="payment_method">
                    <option value="" selected disabled>Seleccione</option>
                    <option value="Efectivo">Efectivo</option>
                    <option value="Transferencia">Transferencia</option>
                    <!-- Puedes agregar más métodos de pago si es necesario -->
                </select>
            </div>
        </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="confirmCheckout" disabled>Cobrar</button>
                </div>  
            </div>
        </div>
    </div>
</form>

@endsection