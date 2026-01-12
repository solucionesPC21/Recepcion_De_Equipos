<form id="formRegistrarMarca" action="{{ route('marcas.store') }}" method="post">
    @csrf
    <div id="modalRegistrarMarca" class="modal static">
        <div class="modal-content">
            <span id="cerrarModal" class="close">&times;</span>
            <!-- Contenido del modal aquí -->
            <h1 class="titulo">Registrar Marcas</h1>
            <br>
            <div class="row">
                <div class="col-md-6">
                    <label for="marca">Nombre Marca</label>
                    <input type="text" name="marca" id="nombre" class="form-control">
                </div>
            </div>
            <br>
            <input type="submit" value="Guardar Marca" class="btn btn-primary submit">
        </div>
    </div>
</form>
