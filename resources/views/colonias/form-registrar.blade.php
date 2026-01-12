<form id="formRegistrarColonia" action="{{ route('colonias.store') }}" method="post">
    @csrf
    <div id="modalRegistrarColonia" class="modal static">
        <div class="modal-content">
            <span id="cerrarModal" class="close">&times;</span>
            <!-- Contenido del modal aquí -->
            <h1 class="titulo">Registrar Colonias</h1>
            <br>
            <div class="row">
                <div class="col-md-6">
                    <label for="colonia">Nombre Colonia</label>
                    <input type="text" name="colonia" id="colonia" class="form-control" required>
                </div>
            </div>
            <br>
            <input type="submit" value="Guardar Colonia" class="btn btn-primary submit">
        </div>
    </div>
</form>
