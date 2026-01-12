<form id="formEditarMarca" method="post">
    @csrf
    {{ method_field('PATCH') }}

    <div id="modalEditarMarca" class="modal static">
        <div class="modal-content">
            <span id="cerrarModalEditar" class="close">&times;</span>
            <!-- Contenido del modal aquí -->
            <h1 class="titulo">Editar Marca</h1>
            <div class="row">
                <div class="col-md-6">
                    <label for="marca">Marca</label>
                    <input type="text" name="marca" id="marca" class="form-control">
                </div>
            </div>
            <br>
            <input type="submit" value="Guardar" class="btn btn-primary">
        </div>
    </div>
</form>

