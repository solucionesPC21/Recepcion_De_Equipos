function buscarRecibos() {
    var searchTerm = document.getElementById('searchInput').value;
    var url = '/buscarUsuario';

    $.ajax({
        url: url,
        type: 'GET',
        data: { search: searchTerm },
        success: function(response) {
            $('#recibosBody').html(response.recibosBodyHtml);
        },
        error: function(jqXHR, textStatus, errorThrown) {
            
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    var registrarUserBtn = document.getElementById('registrarUserBtn');
    var modalRegistro = document.getElementById('modalRegistrarUser');
    var cerrarModalBtn = document.getElementById('cerrarModal');
    var cerrarModalEditarBtn = document.getElementById('cerrarModalEditar');
    var modalEditarUser = document.getElementById('modalEditarUser');
    var password = document.getElementById('password');
    var passwordConfirmation = document.getElementById('password_confirmation');
    var password1 = document.getElementById('password1');
    var passwordConfirmation1 = document.getElementById('password_confirmation1');
    var submitBtn = document.querySelector('.submit');
    var errorMessage = document.getElementById('error-message');
    var errorMessageEdit = document.getElementById('error-message-edit');

    function checkPasswords() {
        if (password.value === passwordConfirmation.value && password.value !== '') {
            errorMessage.style.display = 'none';
            submitBtn.disabled = false;
        } else {
            errorMessage.style.display = 'block';
            submitBtn.disabled = true;
        }
    }

    function checkPasswordsEdit() {
        if (password1.value === passwordConfirmation1.value && password1.value !== '') {
            errorMessageEdit.style.display = 'none';
            submitBtn.disabled = false;
        } else {
            errorMessageEdit.style.display = 'block';
            submitBtn.disabled = true;
        }
    }

    password.addEventListener('input', checkPasswords);
    passwordConfirmation.addEventListener('input', checkPasswords);
    password1.addEventListener('input', checkPasswordsEdit);
    passwordConfirmation1.addEventListener('input', checkPasswordsEdit);

    registrarUserBtn.addEventListener('click', function () {
        modalRegistro.style.display = 'block';
    });

    cerrarModalBtn.addEventListener('click', function () {
        limpiarInputs('modalRegistrarUser'); 
        modalRegistro.style.display = 'none';
    });

    cerrarModalEditarBtn.addEventListener('click', function () {
        limpiarInputs('modalEditarUser'); 
        modalEditarUser.style.display = 'none';
    });

    window.addEventListener('click', function (event) {
        if (event.target == modalRegistro) {
            limpiarInputs('modalRegistrarUser'); 
            modalRegistro.style.display = 'none';
        }
        if (event.target == modalEditarUser) {
            limpiarInputs('modalEditarUser'); 
            modalEditarUser.style.display = 'none';
        }
    });

    function limpiarInputs(modalId) {
        var modal = document.getElementById(modalId);
        var inputs = modal.querySelectorAll('input[type="text"], input[type="password"]');
        var selects = modal.querySelectorAll('select');

        inputs.forEach(function(input) {
            input.value = '';
        });

        selects.forEach(function(select) {
            select.selectedIndex = 0;
        });

        errorMessage.style.display = 'none';
        errorMessageEdit.style.display = 'none';
        submitBtn.disabled = true;
    }

    document.getElementById('formRegistrarUser').addEventListener('submit', function(event) {
        if (submitBtn.disabled) {
            event.preventDefault();
        }
    });

    
});
//editar usuario
function editarUser(event, userId) {
    event.preventDefault();
  
    
    var modalRegistro = document.getElementById('modalRegistrarUser');
  
    
    if (modalRegistro.style.display === 'block') {
       
        limpiarInputs('modalRegistrarUser');
        modalRegistro.style.display = 'none';
    }

    var xhr = new XMLHttpRequest();
    xhr.open('GET', '/users/' + userId + '/edit', true);
    xhr.onload = function () { 
        if (xhr.status === 200) {
         
            var user = JSON.parse(xhr.responseText);
          
            document.getElementById('nombre1').value = user.nombre;
            document.getElementById('user1').value = user.usuario;
            document.getElementById('role_id1').value = user.role_id; // Asignar el valor del rol
            document.getElementById('password1').value = '';
            document.getElementById('password_confirmation1').value = '';
            document.getElementById('formEditarUser').action = '/users/' + user.id;
          ;
            var modalEditarUser = document.getElementById('modalEditarUser');
        
            modalEditarUser.style.display = 'block';
        } else {
            console.error('Error en la solicitud AJAX. Estado:', xhr.status);
        }
    };
    xhr.onerror = function () {
        console.error('Error de red al realizar la solicitud AJAX');
    };
    xhr.send();
}

document.getElementById('formEditarUser').addEventListener('submit', function(event) {
    if (submitBtn.disabled) {
        event.preventDefault();
    }
});


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
