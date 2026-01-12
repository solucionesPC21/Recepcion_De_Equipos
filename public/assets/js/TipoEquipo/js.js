document.addEventListener('DOMContentLoaded', function () {
    var registrarTipoEquipoBtn = document.getElementById('registrarTipoEquipoBtn');
    var modalRegistro = document.getElementById('modalRegistrarTipoEquipo');
    var cerrarModalBtn = document.getElementById('cerrarModal');
    var cerrarModalEditarBtn = document.getElementById('cerrarModalEditar');
    var modalEditarTipoEquipo = document.getElementById('modalEditarTipoEquipo');

    registrarTipoEquipoBtn.addEventListener('click', function () {
        modalRegistro.style.display = 'block';
    });

    cerrarModalBtn.addEventListener('click', function () {
        limpiarInputs('modalRegistrarTipoEquipo'); // Llamar a la función para limpiar los inputs
        modalRegistro.style.display = 'none';
    });

    cerrarModalEditarBtn.addEventListener('click', function () {
        limpiarInputs('modalEditarTipoEquipo'); // Llamar a la función para limpiar los inputs
        modalEditarTipoEquipo.style.display = 'none';
    });

    window.addEventListener('click', function (event) {
        if (event.target == modalRegistro) {
            limpiarInputs('modalRegistrarTipoEquipo'); // Llamar a la función para limpiar los inputs
            modalRegistro.style.display = 'none';
        }
        if (event.target == modalEditarTipoEquipo) {
            limpiarInputs('modalEditarTipoEquipo'); // Llamar a la función para limpiar los inputs
            modalEditarTipoEquipo.style.display = 'none';
        }
    });
});

var camposTexto = document.querySelectorAll('input[type="text"]');

camposTexto.forEach(function(campo) {
    campo.addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
});

function limpiarInputs(modalId) {
    var modal = document.getElementById(modalId);
    var inputs = modal.querySelectorAll('input[type="text"]');
    inputs.forEach(function(input) {
        input.value = '';
    });
}

function editarTipoEquipo(event, tipoEquipoId) {
    event.preventDefault();
    var modalRegistro = document.getElementById('modalRegistrarTipoEquipo');
    
    if (modalRegistro.style.display === 'block') {
      
        limpiarInputs('modalRegistrarTipoEquipo'); // Llamar a la función para limpiar los inputs
        modalRegistro.style.display = 'none';
    }

    var xhr = new XMLHttpRequest();
    xhr.open('GET', '/tipo_equipos/' + tipoEquipoId + '/edit', true);
    xhr.onload = function () { 
        if (xhr.status === 200) {
        
            var tipo_equipo = JSON.parse(xhr.responseText);
            document.getElementById('tipo_equipo').value = tipo_equipo.equipo;
            document.getElementById('formEditarTipoEquipo').action = '/tipo_equipos/' + tipo_equipo.id;
            var modalEditarTipoEquipo = document.getElementById('modalEditarTipoEquipo');
            modalEditarTipoEquipo.style.display = 'block';
        } else {
            console.error('Error en la solicitud AJAX. Estado:', xhr.status);
        }
    };
    xhr.onerror = function () {
        console.error('Error de red al realizar la solicitud AJAX');
    };
    xhr.send();
}
