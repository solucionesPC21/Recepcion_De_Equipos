function ocultarMensajes() {
    var successAlert = document.getElementById('success-alert-modal');
    var errorAlert= document.getElementById('error-alert-modal');

    if (successAlert) {
        successAlert.style.display = 'none';
    }
    if(errorAlert){
        errorAlert.style.display='none';
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

document.addEventListener('DOMContentLoaded', function () {
    // Agregar event listener al contenedor de la tabla

    var cerrarModalBtn = document.getElementById('cerrarModal');
    if (cerrarModalBtn) {
        cerrarModalBtn.addEventListener('click', function () {
            var modal = document.getElementById('modalEditarCliente');
            modal.style.display = 'none';
        });
    } else {
        console.error('Botón de cerrar modal no encontrado');
    }

    window.addEventListener('click', function (event) {
        var modal = document.getElementById('modalEditarCliente');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    });

    
});

function buscarRecibos() {
    var searchTerm = document.getElementById('searchInput').value;
    var url = '/buscarCliente';

    $.ajax({
        url: url,
        type: 'GET',
        data: { search: searchTerm },
        success: function(response) {
            $('#recibosBody').html(response.recibosBodyHtml);
            // No necesitas reasignar event listeners aquí porque estamos usando delegación de eventos
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log('Error en la conexión con el controlador:', textStatus, errorThrown);
        }
    });
}

    function editarCliente(event, clienteId) {
        event.preventDefault();
        console.log('Editando cliente con ID:', clienteId);
    
        var modal = document.getElementById('modalEditarCliente');
        if (modal.style.display === 'block') {
            console.log('Cerrando modal de edición anterior');
            modal.style.display = 'none';
        }
    
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '/clientes/' + clienteId + '/edit', true);
        xhr.onload = function () {
            if (xhr.status === 200) {
                console.log('Solicitud AJAX exitosa');
                var cliente = JSON.parse(xhr.responseText);
                console.log('Datos del cliente:', cliente);
    
                // Asignar valores a los campos del formulario
                document.getElementById('nombre').value = cliente.nombre;
                document.getElementById('telefono').value = cliente.telefono;
                document.getElementById('telefono2').value = cliente.telefono2;
                document.getElementById('rfc').value = cliente.rfc;
                document.getElementById('colonia').value = cliente.colonia ? cliente.colonia.colonia : 'Sin colonia';
    
                // Modificar la acción del formulario
                document.getElementById('formularioEditarCliente').action = '/clientes/' + cliente.id;
                console.log('Acción del formulario:', document.getElementById('formularioEditarCliente').action);
    
                modal.style.display = 'block';
            } else {
                console.error('Error en la solicitud AJAX. Estado:', xhr.status);
            }
        };
        xhr.onerror = function () {
            console.error('Error de red al realizar la solicitud AJAX');
        };
        xhr.send();
    }
    


    document.addEventListener('DOMContentLoaded', function() {
        var successAlertModal = document.getElementById('success-alert-modal');
        var errorAlertModal = document.getElementById('error-alert-modal');
        var errorAlert = document.getElementById('error-alert');
    
        var successProgressBar = document.getElementById('success-progress-bar');
        var errorProgressBarModal = document.getElementById('error-progress-bar');
        var errorProgressBarAlert = document.getElementById('error-progress-bar');
    
        if (successAlertModal && successProgressBar) {
            setTimeout(function () {
                successProgressBar.style.width = '100%';
            }, 10); // Retraso para permitir la renderización inicial
    
            setTimeout(function () {
                successAlertModal.classList.add('hidden');
                setTimeout(function () {
                    successAlertModal.style.display = 'none';
                }, 500); // 0.5 segundos para la transición de opacidad
            }, 2000); // 2 segundos para que la barra se llene
        }
    
        if (errorAlertModal && errorProgressBarModal) {
            setTimeout(function () {
                errorProgressBarModal.style.width = '100%';
            }, 10); // Retraso para permitir la renderización inicial
    
            setTimeout(function () {
                errorAlertModal.classList.add('hidden');
                setTimeout(function () {
                    errorAlertModal.style.display = 'none';
                }, 500); // 0.5 segundos para la transición de opacidad
            }, 2000); // 2 segundos para que la barra se llene
        }
    
        if (errorAlert && errorProgressBarAlert) {
            setTimeout(function () {
                errorProgressBarAlert.style.width = '100%';
            }, 10); // Retraso para permitir la renderización inicial
    
            setTimeout(function () {
                errorAlert.classList.add('hidden');
                setTimeout(function () {
                    errorAlert.style.display = 'none';
                }, 500); // 0.5 segundos para la transición de opacidad
            }, 2000); // 2 segundos para que la barra se llene
        }
    });
    