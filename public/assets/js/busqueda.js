document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById('search');
    const searchResults = document.getElementById('searchResults');
    let timeoutId;

    searchInput.addEventListener('input', function() {
        const inputValue = searchInput.value.trim();

        clearTimeout(timeoutId);

        timeoutId = setTimeout(function() {
            if (inputValue !== '') {
                realizarBusqueda(inputValue);
            } else {
                searchResults.innerHTML = '';
                searchResults.style.display = 'none';
            }
        }, 300);
    });

    
    function realizarBusqueda(searchTerm) {
        fetch(`/home/buscar?term=${searchTerm}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error al buscar clientes');
                }
                return response.json();
            })
            .then(data => {
                searchResults.innerHTML = '';
                if (data.hasOwnProperty('error')) {
                    mostrarError(data.error);
                } else if (data.length > 0) {
                    data.forEach(cliente => {
                        const li = document.createElement('li');
                        li.textContent = cliente.nombre;
                        li.addEventListener('click', () => seleccionarCliente(cliente.id));
                        searchResults.appendChild(li);
                    });
                    searchResults.style.display = 'block';
                } else {
                    mostrarError('No se encontraron clientes con el término de búsqueda.');
                }
            })
            .catch(error => {
                console.error('Error en la búsqueda:', error);
                mostrarError('No se encontraron clientes con el término de búsqueda.');
            });
    }
    
    function mostrarError(mensaje) {
        searchResults.innerHTML = `<li>${mensaje}</li>`;
        searchResults.style.display = 'block';
    }
    

    function seleccionarCliente(clienteId) {
        fetch(`/home/seleccionarCliente/${clienteId}`)
            .then(response => response.text())
            .then(data => {
                document.getElementById('cliente-info').innerHTML = data;
                searchResults.style.display = 'none';
            });
    }

    
});

//modals de alerta de mensaje
document.addEventListener('DOMContentLoaded', function() {
    var successAlertModal = document.getElementById('success-alert-modal');
    var errorAlertModal = document.getElementById('error-alert');

    var successProgressBar = document.getElementById('success-progress-bar');
    var errorProgressBar = document.getElementById('error-progress-bar');

    if (successAlertModal && successProgressBar) {
        successAlertModal.style.display = 'block';
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

    if (errorAlertModal && errorProgressBar) {
        errorAlertModal.style.display = 'block';
        setTimeout(function () {
            errorProgressBar.style.width = '100%';
        }, 10); // Retraso para permitir la renderización inicial

        setTimeout(function () {
            errorAlertModal.classList.add('hidden');
            setTimeout(function () {
                errorAlertModal.style.display = 'none';
            }, 500); // 0.5 segundos para la transición de opacidad
        }, 2000); // 2 segundos para que la barra se llene
    }
});

//validar datos formulario
function convertirAMayusculas(input) {
    input.value = input.value.toUpperCase();
}

function validarModelo(input) {
    // Permitir letras, números, espacios y guiones
    input.value = input.value.replace(/[^a-zA-Z0-9\s\-]/g, '');
    convertirAMayusculas(input);
}


// Añadir el evento oninput para los campos de nueva marca que ya existen en el DOM
document.querySelectorAll('.nueva-marca-input').forEach(function(input) {
    input.addEventListener('input', function() {
        validarNuevaMarca(this);
    });
});

function filtrarCaracteres(input) {
    var valor = input.value;
    // Reemplazar caracteres no permitidos con una cadena válida
    var nuevoValor = valor.replace(/[^\w\s,\-\nñÑ.$]/gi, '');
    // Actualizar el valor del campo
    input.value = nuevoValor;
    convertirAMayusculas(input);
}

function filtraraccesorios(input){
    var valor = input.value;
    // Reemplazar caracteres no permitidos con una cadena válida
    var nuevoValor = valor.replace(/[^a-zA-Z0-9,\s\n.\-]/g, '');
    // Actualizar el valor del campo
    input.value = nuevoValor.toUpperCase();  // Convertir a mayúsculas
}

function validarNuevaMarca(input) {
      // Permitir solo letras, números y espacios
      input.value = input.value.replace(/[^a-zA-Z0-9\s]/g, '');
      convertirAMayusculas(input);
    var nuevaMarcaNombre = input.value.trim();
    var errorDiv = input.nextElementSibling;
    var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
   

    if (nuevaMarcaNombre) {
        fetch('/home/validarMarca', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ marca: nuevaMarcaNombre })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.exists) {
                errorDiv.textContent = 'La marca "' + nuevaMarcaNombre + '" ya existe Seleccionelo En Marcas Por Favor.';
                errorDiv.style.color = 'red';
               
            } else {
                errorDiv.textContent = '';
        
            }
        })
        .catch(error => {
            console.error('There was a problem with the fetch operation:', error);
            errorDiv.textContent = 'Error de red. Inténtelo de nuevo más tarde.';
            errorDiv.style.color = 'red';
           
        });
    } else {
        errorDiv.textContent = '';
        
    }
}
