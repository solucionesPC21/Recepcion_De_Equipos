<form id="formEditarTipoEquipo" method="post">
    @csrf
    {{ method_field('PATCH') }}

    <div id="modalEditarTipoEquipo" class="modal static">
        <div class="modal-content">
            <span id="cerrarModalEditar" class="close">&times;</span>
            <!-- Contenido del modal aquí -->
            <h1 class="titulo">Editar Tipo De Equipo</h1>
            <br>
            <div class="row">
                <div class="col-md-6">
                    <label for="equipo">Tipo De Equipo</label>
                    <input type="text" name="equipo" id="tipo_equipo" class="form-control">
                </div>
            </div>
            <br>
            <input type="submit" value="Guardar" class="btn btn-primary">
        </div>
    </div>
</form>