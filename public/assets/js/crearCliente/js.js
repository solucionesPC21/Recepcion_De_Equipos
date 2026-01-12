document.addEventListener('DOMContentLoaded', function () {

    var registrarClienteBtn = document.getElementById('registrarClienteBtn');
    console.log('Botón de registrar cliente encontrado:', registrarClienteBtn);

    var modal = document.getElementById('modalRegistrarCliente');
    console.log('Modal de registrar cliente encontrado:', modal);

    registrarClienteBtn.addEventListener('click', function () {
        console.log('Se hizo clic en el botón de registrar cliente');
        modal.style.display = 'block';
        console.log('Modal de registrar cliente mostrado');
    });

    // Cerrar modal al hacer clic en el botón de cerrar
    var cerrarModalBtn = document.getElementById('cerrarModal');
    cerrarModalBtn.addEventListener('click', function () {
        modal.style.display = 'none';
        limpiarInputs('modalRegistrarCliente'); // Llamar a la función para limpiar los inputs y el select
    });

    // Obtener todos los campos de texto y el select dentro del modal de registrar cliente
    var camposTexto = modal.querySelectorAll('input[type="text"]');
    var select = modal.querySelector('select');
    
    // Iterar sobre los campos de texto y agregar evento de escucha de entrada
    camposTexto.forEach(function(campo) {
        campo.addEventListener('input', function() {
            // Convertir el valor del campo a mayúsculas
            this.value = this.value.toUpperCase();
        });
    });

    // Limpiar el select al cerrar el modal
    function limpiarInputs(modalId) {
        var modal = document.getElementById(modalId);
        var camposTexto = modal.querySelectorAll('input[type="text"]');
        var select = modal.querySelector('select');

        camposTexto.forEach(function(campo) {
            campo.value = '';
        });

        // Resetea el select a su opción por defecto
        select.selectedIndex = 0;
    }
});



$(document).ready(function() {
    $('#formRegistrarCliente').on('submit', function(e) {
        e.preventDefault();
        $('.text-danger').html(''); // Limpiar mensajes de error anteriores

        $.ajax({
            url: $(this).attr('action'),
            method: $(this).attr('method'),
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    window.location.href = response.redirect_url; // Redirigir a la URL proporcionada
                }
            },
            error: function(response) {
                var errors = response.responseJSON.errors;
                if (errors && errors.nombre) {
                    $('#error-nombre').html(errors.nombre[0]);
                }
                if (errors && errors.telefono) {
                    $('#error-telefono').html(errors.telefono[0]);
                }
                // Añade manejo de errores para otros campos según sea necesario
                if (errors && errors.telefono2) {
                    $('#error-telefono2').html(errors.telefono2[0]);
                }
                if (errors && errors.rfc) {
                    $('#error-rfc').html(errors.rfc[0]);
                }
            }
        });
    });
});
