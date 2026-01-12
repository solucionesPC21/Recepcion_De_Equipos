<form id="formRegistrarUser" action="/register" method="POST">
    @csrf
    <div id="modalRegistrarUser" class="modal static">
        <div class="modal-content">
            <span id="cerrarModal" class="close">&times;</span>
            <h1 class="titulo">Registrar Usuarios</h1>

            <div class="row">
                <div class="col-md-6">
                    <label for="nombre">Nombre Completo</label>
                    <input type="text" name="nombre" id="nombre" class="form-control">
                </div>
                <div class="col-md-6">
                    <label for="username">UserName</label>   
                    <input type="text" name="usuario" id="user" class="form-control">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" class="form-control">
                </div>
                <div class="col-md-6">
                    <label for="password_confirmation">Repetir Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control">
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <label for="role_id">Rol del Usuario</label>
                    <select name="role_id" id="role_id" class="form-control">
                        <option value="1">Administrador</option>
                        <option value="2" selected>Usuario</option>
                    </select>
                </div>
            </div>
            <div id="error-message" class="error-message">Las contraseñas no coinciden</div>
            <br>
            <input type="submit" value="Guardar Usuario" class="btn btn-primary submit" disabled>
        </div>
    </div>
</form>