<form id="formEditarUser" method="post">
    @csrf
    {{ method_field('PATCH') }}

    <div id="modalEditarUser" class="modal static">
        <div class="modal-content">
            <span id="cerrarModalEditar" class="close">&times;</span>
            <h1 class="titulo">Editar Usuario</h1>
        
            <div class="row">
                <div class="col-md-6">
                    <label for="nombre">Nombre</label>
                    <input type="text" name="nombre" id="nombre1" class="form-control">
                </div>
                <div class="col-md-6">
                    <label for="username">UserName</label>   
                    <input type="text" name="usuario" id="user1" class="form-control">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password1" class="form-control">
                </div>
                <div class="col-md-6">
                    <label for="password_confirmation">Repetir Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation1" class="form-control">
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <label for="role_id1">Rol del Usuario</label>
                    <select name="role_id" id="role_id1" class="form-control">
                        <option value="1">Administrador</option>
                        <option value="2">Usuario</option>
                    </select>
                </div>
            </div>
            <div id="error-message-edit" class="error-message">Las contraseñas no coinciden</div>
            <br>
            <input type="submit" value="Guardar" class="btn btn-primary submit">
        </div>
    </div>
</form>