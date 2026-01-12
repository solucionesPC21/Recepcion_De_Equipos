// Almacenar referencia al elemento select
var elementoSelect = document.getElementById("colonias");

// Función para poblar las opciones en el elemento select
function poblarOpciones(opciones) {
    // Limpiar opciones existentes
    elementoSelect.innerHTML = "";

    opciones.forEach(function(opcion) {
        var elementoOpcion = document.createElement("option");
        elementoOpcion.textContent = opcion.label; // Usar la propiedad 'label' para el texto de la opción
        elementoOpcion.value = opcion.value; // Usar la propiedad 'value' para el valor de la opción
        elementoSelect.appendChild(elementoOpcion);
    });
}

// Función para actualizar opciones según el valor de entrada
document.getElementById("colonia").addEventListener("input", function() {
    var valorInput = this.value.toLowerCase();
    // Realizar una solicitud AJAX al servidor para obtener las opciones filtradas
    fetch('/home/buscarColonia?term=' + encodeURIComponent(valorInput))
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la solicitud: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.length > 0) {
                // Llamar a la función poblarOpciones solo si hay datos
                poblarOpciones(data);
            } else {
                // Limpiar las opciones si no hay datos
                limpiarOpciones();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Mostrar mensaje de error al usuario
            elementoSelect.innerHTML = '<option value="">Error al cargar las opciones</option>';
        });
});

// Función para limpiar las opciones en el elemento select
function limpiarOpciones() {
    // Limpiar opciones existentes
    elementoSelect.innerHTML = "";
}
