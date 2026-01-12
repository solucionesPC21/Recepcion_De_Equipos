document.addEventListener('DOMContentLoaded', function () {
    var registrarMarcaBtn = document.getElementById('registrarMarcaBtn');
    var modalRegistro = document.getElementById('modalRegistrarMarca');
    var cerrarModalBtn = document.getElementById('cerrarModal');
    var cerrarModalEditarBtn = document.getElementById('cerrarModalEditar');
    var modalEditarMarca = document.getElementById('modalEditarMarca');

    registrarMarcaBtn.addEventListener('click', function () {
        modalRegistro.style.display = 'block';
    });

    cerrarModalBtn.addEventListener('click', function () {
        limpiarInputs('modalRegistrarMarca'); // Llamar a la función para limpiar los inputs
        modalRegistro.style.display = 'none';
    });

    cerrarModalEditarBtn.addEventListener('click', function () {
        limpiarInputs('modalEditarMarca'); // Llamar a la función para limpiar los inputs
        modalEditarMarca.style.display = 'none';
    });

    window.addEventListener('click', function (event) {
        if (event.target == modalRegistro) {
            limpiarInputs('modalRegistrarMarca'); // Llamar a la función para limpiar los inputs
            modalRegistro.style.display = 'none';
        }
        if (event.target == modalEditarMarca) {
            limpiarInputs('modalEditarMarca'); // Llamar a la función para limpiar los inputs
            modalEditarMarca.style.display = 'none';
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

function editarMarca(event, marcaId) {
    event.preventDefault();

    
    var modalRegistro = document.getElementById('modalRegistrarMarca');
   
    
    if (modalRegistro.style.display === 'block') {
    
        limpiarInputs('modalRegistrarMarca'); // Llamar a la función para limpiar los inputs
        modalRegistro.style.display = 'none';
    }

    var xhr = new XMLHttpRequest();
    xhr.open('GET', '/marcas/' + marcaId + '/edit', true);
    xhr.onload = function () { 
        if (xhr.status === 200) {
        
            var marca = JSON.parse(xhr.responseText);
            document.getElementById('marca').value = marca.marca;
            document.getElementById('formEditarMarca').action = '/marcas/' + marca.id;
            var modalEditarMarca = document.getElementById('modalEditarMarca');
            modalEditarMarca.style.display = 'block';
        } else {
            console.error('Error en la solicitud AJAX. Estado:', xhr.status);
        }
    };
    xhr.onerror = function () {
        console.error('Error de red al realizar la solicitud AJAX');
    };
    xhr.send();
}
