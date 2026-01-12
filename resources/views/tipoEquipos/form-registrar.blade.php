<form id="formRegistrarTipoEquipo" action="{{ route('tipo_equipos.store') }}" method="post">
    @csrf
    <div id="modalRegistrarTipoEquipo" class="modal static">
        <div class="modal-content">
            <span id="cerrarModal" class="close">&times;</span>
            <!-- Contenido del modal aquí -->
            <h1 class="titulo">Registrar Tipo De Equipos</h1>
            <br>
            <div class="row">
                <div class="col-md-6">
                    <label for="equipo">Nombre Tipo De Equipo</label>
                    <input type="text" name="equipo" id="nombre" class="form-control">
                </div>
            </div>
            <br>
            <input type="submit" value="Guardar Tipo De Equipo" class="btn btn-primary submit">
        </div>
    </div>
</form>