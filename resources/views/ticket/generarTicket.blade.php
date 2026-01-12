<div id="modalContainer">
    <!-- The Modal -->
    <div id="myModal" class="modal">
        <!-- Modal content -->
        <div class="modal-content">
            <span class="close">×</span> 
            <h2>Generar Ticket de Pago</h2>

            <form id="ticketForm" action="{{ route('conceptos.guardar') }}" method="post">
                @csrf
                <input type="hidden" name="recibos_id" id="recibos_id" value="">
                
                <!-- Contenedor de conceptos -->
                <div id="conceptoContainer">
                    <div class="concepto-group">
                        <div class="form-group" style="position: relative;">
                            <label for="concepto">Concepto:</label>
                            <input type="text" name="concepto[]" class="concepto-input form-control" required>
                            <div class="concepto-error invalid-feedback">Solo se permiten letras, números y espacios.</div>
                            <div class="suggestions-container" style="position: absolute; width: 100%; z-index: 1000;"></div>
                            <input type="hidden" name="concepto_id[]" class="concepto-id">
                        </div>

                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label for="cantidad">Cantidad:</label>
                                <input type="number" name="cantidad[]" class="cantidad-input form-control" min="1" value="1" required>
                            </div>
                            
                            <div class="col-md-4 form-group">
                                <label for="precio_unitario">Precio Unitario:</label>
                                <input type="text" class="precio-input form-control" name="precio_unitario[]" placeholder="0.00" required>
                            </div>
                            
                            <div class="col-md-4 form-group">
                                <label for="total">Total:</label>
                                <input class="total form-control" type="text" name="total[]" readonly value="$0.00">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="categoria">Categoría:</label>
                            <select name="categoria[]" class="categoria-select form-control" required>
                                <option value="" disabled selected>Seleccione una Categoría</option>
                                @foreach($categorias as $categoria)
                                    <option value="{{ $categoria->id }}">{{ $categoria->categoria}}</option>
                                @endforeach 
                            </select>
                        </div>
                    </div>
                </div>

                <button type="button" id="agregarConcepto" class="btn btn-primary mb-3">
                    <i class="fas fa-plus"></i> Agregar Concepto
                </button>
                
                <!-- Total General -->
                <div class="row mb-3">
                    <div class="col-md-6 offset-md-6">
                        <div class="input-group">
                            <span class="input-group-text">Total General:</span>
                            <input class="form-control font-weight-bold" type="text" name="total_general" id="total_general" readonly value="$0.00">
                        </div>
                    </div>
                </div>

                <!-- Tipo de Pago -->
                <div class="form-group">
                    <label for="tipo_pago">Tipo de Pago:</label>
                    <select name="tipo_pago" id="tipo_pago" class="form-control" required>
                        <option value="" selected disabled>Selecciona una opción</option>
                        @foreach($pagos as $pago)
                            <option value="{{ $pago->id }}">{{ $pago->tipoPago}}</option>
                        @endforeach 
                    </select>
                </div>

                <!-- Botones de acción -->
                <div class="d-flex justify-content-between mt-4">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-ticket-alt"></i> Generar Ticket
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Estilos para el modal y sugerencias -->
<style>
    /* Estilos del modal */
    .modal {
        display: none;
        position: fixed;
        z-index: 1050;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.4);
    }

    .modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        max-width: 800px;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }

    .close:hover {
        color: black;
    }

    /* Estilos para las sugerencias */
    .suggestions-container {
        display: none;
        max-height: 200px;
        overflow-y: auto;
        background-color: white;
        border: 1px solid #ced4da;
        border-top: none;
        border-radius: 0 0 4px 4px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .suggestion-item {
        padding: 8px 12px;
        cursor: pointer;
        border-bottom: 1px solid #eee;
    }

    .suggestion-item:hover {
        background-color: #f8f9fa;
    }

    .suggestion-item.disabled {
        color: #6c757d;
        cursor: not-allowed;
        background-color: #f8f9fa;
    }

    /* Badges para el inventario */
    .badge {
        font-size: 0.75em;
        margin-left: 8px;
        padding: 3px 6px;
        border-radius: 3px;
    }

    .bg-success { background-color: #28a745; color: white; }
    .bg-danger { background-color: #dc3545; color: white; }
    .bg-info { background-color: #17a2b8; color: white; }

    /* Estilos para los conceptos */
    .concepto-group {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
        border: 1px solid #dee2e6;
    }

    /* Estilo para el botón de eliminar */
    .eliminar-concepto {
        margin-top: 10px;
        width: 100%;
    }

    /* Estilo para los totales */
    .total {
        font-weight: bold;
        text-align: right;
        background-color: #e9ecef;
    }

    /* Responsividad */
    @media (max-width: 768px) {
        .modal-content {
            width: 95%;
            margin: 10% auto;
        }
        
        .row > div {
            margin-bottom: 10px;
        }
    }
</style>
