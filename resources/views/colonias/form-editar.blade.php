<form id="formEditarColonia" method="post">
    @csrf
    {{ method_field('PATCH') }}

    <div id="modalEditarColonia" class="modal static">
        <div class="modal-content">
            <span id="cerrarModalEditar" class="close">&times;</span>
            <!-- Contenido del modal aquí -->
            <h1 class="titulo">Editar Colonia</h1>
            <div class="row">
                <div class="col-md-6">
                    <label for="nombre">Colonia</label>
                    <input type="text" name="colonia" id="nombre" class="form-control">
                </div>
            </div>
            <br>
            <input type="submit" value="Guardar" class="btn btn-primary">
        </div>
    </div>
</form>

