<script>
// Función para ocultar los mensajes de éxito y error
    function ocultarMensajes() {
        var successAlert = document.getElementById('success-alert-modal');
        var errorAlert = document.getElementById('error-alert');

        if (successAlert) {
            successAlert.style.display = 'none';
        }

        if (errorAlert) {
            errorAlert.style.display = 'none';
        }
    }

    // Detectar si el usuario está regresando atrás o adelante
    window.onpageshow = function(event) {
        if (event.persisted || (window.performance && window.performance.navigation.type === 2)) {
            // Ocultar los mensajes de éxito y error
            ocultarMensajes();
        }
    };

    // Detectar si el usuario navega hacia adelante
    window.onpopstate = function() {
        // Ocultar los mensajes de éxito y error
        ocultarMensajes();
    };

    function duplicarCampos() {
    var botonDuplicar = document.getElementById('duplicarCampo');
    var divEquipo = document.querySelector('.equipo-info1');
    var clonEquipo = divEquipo.cloneNode(true);

    // Resetear los valores de los campos de entrada
    var inputs = clonEquipo.querySelectorAll('input, textarea, select');
    inputs.forEach(function(input) {
        input.value = '';
    });

    // Ocultar y resetear el campo de nueva marca
    var nuevaMarcaInputs = clonEquipo.querySelectorAll('.nueva-marca');
    nuevaMarcaInputs.forEach(function(input) {
        input.style.display = 'none';
        var nestedInput = input.querySelector('input');
        if (nestedInput) {
            nestedInput.value = '';
        }
    });

    // Inserta el clon antes del botón de duplicar
    botonDuplicar.parentNode.insertBefore(clonEquipo, botonDuplicar);

    // Insertar saltos de línea después del div del equipo original
    for (var i = 0; i < 3; i++) {
        var br = document.createElement('br');
        botonDuplicar.parentNode.insertBefore(br, botonDuplicar);
    }

    // Agregar botón de eliminación
    var botonEliminar = document.createElement('button');
    botonEliminar.textContent = 'Eliminar';
    botonEliminar.className = 'btn btn-danger mt-3';
    botonEliminar.addEventListener('click', function() {
        clonEquipo.parentNode.removeChild(clonEquipo);
        for (var i = 0; i < 2; i++) {
            clonEquipo.previousSibling.parentNode.removeChild(clonEquipo.previousSibling);
        }
        botonEliminar.parentNode.removeChild(botonEliminar);

        // Validar nuevamente al eliminar
        validarFormulario(); // Asegúrate de implementar esta función si aún no está definida
    });
    clonEquipo.appendChild(botonEliminar);

    // Asignar eventos de cambio para cada nuevo campo de marca
    var nuevosSelects = clonEquipo.querySelectorAll('select[name="marca[]"]');
    nuevosSelects.forEach(function(select) {
        select.addEventListener('change', function() {
            toggleNuevaMarca(this); // Llamar a la función toggleNuevaMarca para mostrar/ocultar el campo de nueva marca
            validarFormulario(); // Validar el formulario al cambiar cualquier campo relevante
        });
    });

    // Validar el formulario al duplicar campos
    validarFormulario(); // Asegúrate de implementar esta función si aún no está definida
}

//
function validarFormulario() {
    var nuevosSelects = document.querySelectorAll('select[name="marca[]"]');
    var enviarButton = document.querySelector('.enviar'); // Selecciona el botón de enviar
    var marcasExistentes = new Set(); // Usaremos un conjunto para almacenar marcas ya validadas

    // Iterar sobre todos los selects de marca
    nuevosSelects.forEach(function(select) {
        var nuevaMarcaInput = select.closest('.equipo-info1').querySelector('.nueva-marca');
        var nuevaMarcaNombre = nuevaMarcaInput.querySelector('input').value.trim();

        if (select.value === 'nueva_marca' && nuevaMarcaNombre) {
            // Verificar si la nueva marca ya está en el conjunto de marcas validadas
            if (marcasExistentes.has(nuevaMarcaNombre)) {
                // Marca duplicada encontrada, deshabilitar el botón de enviar
                enviarButton.disabled = true;
                return; // Salir de la función si encontramos una duplicación
            }

            // Añadir la nueva marca al conjunto de marcas validadas
            marcasExistentes.add(nuevaMarcaNombre);
        }
    });

    // Si no se encontraron marcas duplicadas, habilitar el botón de enviar
    enviarButton.disabled = false;
}

function convertirAMayusculas(input) {
    input.value = input.value.toUpperCase();
}

function toggleNuevaMarca(select) {
    var nuevaMarcaInput = select.closest('.equipo-info1').querySelector('.nueva-marca');
    if (select.value === 'nueva_marca') {
        nuevaMarcaInput.style.display = 'block';
    } else {
        nuevaMarcaInput.style.display = 'none';
        nuevaMarcaInput.querySelector('input').value = '';
    }
}



</script>

@if(session('cliente') && is_array(session('cliente')))

    <form id="formGenerarRecibo" action="{{ url('/home/registroEquipoCliente') }}" method="post">   
        @csrf
        <div class="container">
            <!-- Mensajes de redireccionamiento -->
            @if (session('success'))
                <div id="success-alert-modal" class="modal-alert" style="display: none;">
                    <div class="modal-alert-content alert alert-success alert-dismissible fade-out custom-alert" role="alert">
                        <span>{{ session('success') }}</span>
                        <div class="progress-bar" id="success-progress-bar"></div>
                    </div>
                </div>
            @endif

            @if ($errors->any())
        <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

            <!-- Fin de mensajes -->

            <div class="equipo-info">
                <h3>INFORMACIÓN DEL CLIENTE</h3>
                <div class="row">
                    <div class="col">
                        <h4><strong>Nombre:</strong> {{ session('cliente')['nombre'] ?? '' }}</h4>
                    </div>
                    <div class="col">
                        <h4><strong>Teléfono:</strong> {{ session('cliente')['telefono'] ?? '' }}</h4>
                    </div>
                    <div class="col">
                        <h4><strong>RFC: </strong>{{ session('cliente')['rfc'] ?? '' }}</h4>
                    </div>
                    <div class="col">
                        <h4><strong>Colonia:</strong> 
                            {{ session('cliente')['nombre_colonia'] ?? 'Sin colonia registrada' }}
                        </h4>
                    </div>

                </div>
                <input type="hidden" name="nombre_cliente" value="{{ session('cliente')['nombre'] ?? '' }}">
            </div>
            <br><br>
            <div class="equipo-info mt-4">
                <h3>INFORMACIÓN DEL EQUIPO</h3>
            </div>
            <!-- Div original con campos del equipo -->
            <div class="equipo-info1">
                <div class="row">
                    <!-- Primera Columna -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="tipo_equipo" class="form-label">Tipo De Equipo</label>
                                <select class="form-select" id="tipo_equipo" name="tipo_equipo[]" aria-label="Default select example" required aria-required="true">
                                     <option value="" disabled selected style="display: none;">Selecciona una opción</option>
                                        @foreach($equipos as $equipo)
                                     <option value="{{ $equipo->id }}">{{ $equipo->equipo }}</option>
                                        @endforeach
                                </select>          
                        </div>
                    <div class="form-group mt-3" style="width: 300px;">
                        <label for="marca" class="form-label">Marcas</label>
                        <select class="form-select" id="marca" name="marca[]" aria-label="Default select example" onchange="toggleNuevaMarca(this)" required>
                            <option value="" disabled selected style="display: none;">Selecciona una opción</option>
                            @foreach($marcas as $marca)
                            <option value="{{ $marca->id }}">{{ $marca->marca }}</option>
                            @endforeach
                            <option value="nueva_marca">Agregar nueva marca</option>
                        </select>
                        
                    </div>
                    <div class="form-group mt-3 nueva-marca" style="display:none;width: 300px;">
                        <label for="nueva_marca" class="form-label">Nueva Marca</label>
                        <input type="text" class="form-control nueva-marca-input" name="nueva_marca[]" placeholder="Ingrese una nueva marca"
                            oninput="validarNuevaMarca(this)" pattern="^[a-zA-Z0-9\s]*$" title="Solo se permiten letras, números y espacios">
                        <div class="nueva-marca-error"></div> <!-- Contenedor para el mensaje de error -->
                    </div>
                </div>
                <!-- Segunda Columna -->
                <div class="col-md-4">
                <div class="form-group">
                    <label for="modelo" class="form-label">Modelo</label>
                    <input type="text" class="form-control" name="modelo[]" id="modelo" placeholder="Ingrese el modelo" oninput="validarModelo(this)" required>
                </div>
                <div class="form-group mt-3">
                    <label for="ns" class="form-label">Numero De Serie</label>
                    <input type="text" class="form-control" name="ns[]" id="ns" placeholder="Ingrese el numero de serie" oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                </div>
             </div>
            <!-- Tercera Columna -->
            <div class="col-md-4">
            <div class="form-group">
                <label for="falla" class="form-label">Falla</label>
                <textarea class="form-control" name="falla[]" id="falla" rows="5" placeholder="Describe la falla" required></textarea>
            </div>
            <div class="form-group mt-3">
                <label for="accesorios" class="form-label">Accesorios</label>
                <textarea class="form-control accesorios" name="accesorios[]" id="accesorios" rows="5" placeholder="Lista de accesorios incluidos" oninput="filtraraccesorios(this)"></textarea>
                </div>
            </div>
        </div>
    </div>               

            <button type="button" id="duplicarCampo" class="btn btn-primary mt-3" onclick="duplicarCampos()">Agregar Nuevo Equipo</button>

            <!-- Botón para enviar el formulario -->
            <button type="submit" class="enviar" style="margin-top: 40px;" onclick="this.disabled=true; this.innerHTML='Generando...'; this.form.submit();">Generar Recibo</button>


        </div>
    </form>

    {{-- Eliminar los datos del cliente de la sesión --}}
    @php
        session()->forget('cliente');
    @endphp
    
    @elseif(isset($mensajeError))
        {{-- Mostrar mensaje de error --}}
        <div class="alert alert-danger" role="alert" style="width:30%;">{{ $mensajeError }}</div>
    @endif

