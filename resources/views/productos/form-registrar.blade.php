<!-- Modal de Registrar Producto -->
<form id="formRegistrarProducto" action="{{ route('productos.store') }}" method="post">
    @csrf
    <div id="modalRegistrarProducto" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Registrar Producto</h5>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- Columna 1 -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nombre">Nombre Producto</label>
                                <input type="text" name="producto" id="nombre" class="form-control" required>
                                  <small id="producto-error" class="text-danger"></small>
                            </div>
                            <div class="form-group">
                                <label for="precio">Precio</label>
                                <input type="text" name="precio" id="precio" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="cantidad">Cantidad</label>
                                <input type="number" name="cantidad" id="cantidad" class="form-control" required>
                            </div>
                        </div>
                        <!-- Columna 2 -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="codigo_barra">Código de Barra</label>
                                <input type="text" name="codigo_barra" id="codigo_barra" class="form-control" required>
                                <small id="codigo-error" class="text-danger"></small>
                            </div>
                            <div class="form-group">
                                <label for="marca">Marca</label>
                                <input type="text" name="marca" id="marca" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="modelo">Modelo</label>
                                <input type="text" name="modelo" id="modelo" class="form-control">
                            </div>
                        </div>
                    </div>
                    <!-- Fila completa para la descripción -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="descripcion">Descripción</label>
                                <textarea name="descripcion" id="descripcion" class="form-control" rows="4"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" role="button">Cerrar</button>
                    <input type="submit" class="btn btn-primary" value="Guardar Producto" id="guardarProducto" disabled>
                </div>
            </div>
        </div>
    </div>
</form>