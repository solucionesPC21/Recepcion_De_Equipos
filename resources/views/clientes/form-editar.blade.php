<form id="formularioEditarCliente"  method="post">
@csrf
{{method_field('PATCH')}}

    <div id="modalEditarCliente" class="modal static">
        <div class="modal-content">
            <span id="cerrarModal" class="close">&times;</span>
            <!-- Contenido de la modal aquí -->
            <h1 class="titulo">Editar Clientes</h1>
            <div class="row">
                <div class="col-md-6">
                    <label for="nombre">Nombre</label>
                    <input type="text" name="nombre"  id="nombre" class="form-control">
                </div>
                <div class="col-md-6">
                    <label for="telefono">Telefono</label>
                    <input type="text" name="telefono"  id="telefono" class="form-control">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <label for="telefono2">Telefono 2</label>
                    <input type="text" name="telefono2"  id="telefono2" class="form-control">
                </div>
                <div class="col-md-6">
                    <label for="rfc">RFC</label>
                    <input type="text" name="rfc"  id="rfc" class="form-control">
                </div>
            </div>
            <div class="row">
            <div class="col-md-6">
                <label for="colonia">Buscar Dirección:</label>
                <select name="colonia" id="colonia" class="form-control" aria-describedby="colonias-desc">
                    <option selected>Selecciona una opción</option>
                    @foreach($colonias as $colonia)
                        <option value="{{ $colonia->colonia }}">{{ $colonia->colonia }}</option>
                    @endforeach 
                </select>
             </div>

            </div>
            <br>
            <input type="submit" value="Guardar" class="btn btn-primary">
        </div>
    </div>
</form>

