let reciboActual = null;

// Abrir modal de subir archivos
function abrirModalSubirArchivos(reciboId) {
    reciboActual = reciboId;
    $('#modalSubirArchivos').modal('show');
}

// Subir archivos por AJAX
function guardarArchivos() {
    var archivosInput = document.getElementById('inputArchivos');
    var archivos = archivosInput.files;

    if (archivos.length === 0) {
        alert("Debes seleccionar al menos un archivo.");
        return;
    }

    var formData = new FormData();

    for (let i = 0; i < archivos.length; i++) {
        formData.append('archivos[]', archivos[i]);
    }

    formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

    $.ajax({
    url: '/recibos/' + reciboActual + '/archivos',
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function(response) {
        Swal.fire({
            icon: 'success',
            title: '¡Archivos subidos!',
            text: 'Los archivos se han guardado correctamente.',
            timer: 3000,
            timerProgressBar: true,
            showConfirmButton: false
        }).then(() => {
            $('#modalSubirArchivos').modal('hide');
        });
    },
    error: function(xhr) {
        if (xhr.status === 422) {
            let errores = xhr.responseJSON.errors;
                let mensaje = "";

                for (let campo in errores) {
                    mensaje += errores[campo].join("<br>") + "<br>";
                }

                Swal.fire({
                    icon: "error",
                    title: "Error al subir archivos",
                    html: mensaje,
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false
                });

            } else {
                Swal.fire({
                    icon: "error",
                    title: "Error inesperado",
                    text: "No se pudieron subir los archivos",
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false
                });
            }
        }
    });

}

// Abrir modal para ver archivos (solo abre el modal)
function abrirModalVerArchivos(reciboId) {
    $('#listaArchivos').html('<li>Cargando archivos...</li>');

    $.ajax({
        url: '/recibos/' + reciboId + '/archivos',
        type: 'GET',
        success: function(archivos) {

            if (archivos.length === 0) {
                $('#listaArchivos').html('<li>No hay archivos subidos.</li>');
                $('#modalVerArchivos').modal('show');
                return;
            }

            let html = '';

            archivos.forEach(archivo => {
                html += `
                    <li class="mb-2">
                        <strong>${archivo.nombre}</strong>
                        <a href="/recibos/archivo/${archivo.id}/descargar" 
                           class="btn btn-sm btn-primary ms-2">
                            Descargar
                        </a>
                    </li>
                `;
            });

            $('#listaArchivos').html(html);
            $('#modalVerArchivos').modal('show');
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudieron cargar los archivos.',
            });
        }
    });
}

//
function validarArchivos(event) {
    const input = event.target;
    const files = input.files;

    const allowedTypes = [
        "application/pdf",
        "image/jpeg",
        "image/png",
        "image/jpg",
        "image/webp"
    ];

    for (let file of files) {
        if (!allowedTypes.includes(file.type)) {

            Swal.fire({
                icon: "error",
                title: "Archivo no permitido",
                text: "Solo puedes subir archivos PDF o imágenes (JPG, PNG, WEBP).",
            });

            input.value = ""; // limpia el input para evitar que se suba
            return;
        }
    }
}


// Abrir modal de nota
function abrirNotaModal(reciboId) {
    if (!reciboId) {
        console.error("El ID del recibo no se ha proporcionado correctamente.");
        return;
    }

    var url = '/recibos/nota/' + reciboId;

    $.ajax({
        url: url,
        type: 'GET',
        success: function(response) {
            document.getElementById('notaContent').innerText = response.nota || 'No hay nota disponible.';
            var modal = $('#notaModal');
            modal.attr('data-recibo-id', reciboId);
        
            modal.modal({
                backdrop: 'static',
                keyboard: false
            });
            modal.modal('show');
        },
        error: function() {
            alert('Error al obtener la nota.');
        }
    });
}

function cerrarNotaModal() {
    $('#notaModal').modal('hide');
}

// Asegúrate de que el backdrop se elimine y el estado del cuerpo se restablezca
$('#notaModal').on('hidden.bs.modal', function () {
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open');
    $('body').css('padding-right', '');

    // Restablecer contenido y estado del modal al cerrarse
    document.getElementById('notaContent').innerText = '';
    document.getElementById('notaInput').value = '';
    document.getElementById('notaInput').style.display = 'none';
    document.getElementById('guardarNotaButton').style.display = 'none';
    document.getElementById('editNotaButton').style.display = 'inline-block';
    document.getElementById('notaContent').style.display = 'block';
});

function habilitarEdicionNota() {
    var notaContent = document.getElementById('notaContent');
    var notaInput = document.getElementById('notaInput');
    var guardarNotaButton = document.getElementById('guardarNotaButton');
    var editNotaButton = document.getElementById('editNotaButton');

    notaInput.value = notaContent.innerText;
    notaInput.style.display = 'block';
    guardarNotaButton.style.display = 'inline-block';
    editNotaButton.style.display = 'none';
    notaContent.style.display = 'none';
}

function guardarNota() {
    var notaInput = document.getElementById('notaInput');
    if (!notaInput) {
        console.error('El elemento notaInput no se encontró.');
        return;
    }

    var reciboId = $('#notaModal').attr('data-recibo-id');
    

    if (!reciboId) {
        console.error('El ID del recibo no se ha proporcionado correctamente.');
        return;
    }

    $.ajax({
        url: '/recibos/agregarnota' + reciboId,
        type: 'GET',
        data: {
            id: reciboId,
            nota: notaInput.value,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
          success: function(response) {

            // Actualiza el contenido en pantalla
            document.getElementById('notaContent').innerText = notaInput.value;

            // Cierra modal
            cerrarNotaModal();

            // SweetAlert éxito
            Swal.fire({
                icon: 'success',
                title: '¡Nota guardada!',
                text: 'La nota se guardó correctamente.',
                timer: 3000,
                showConfirmButton: false
            });
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Hubo un problema al guardar la nota.',
                timer: 3000,
                showConfirmButton: false
            });
        }
    });
}
