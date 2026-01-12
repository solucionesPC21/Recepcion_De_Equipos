// Buscar Recibos (AHORA envía también fecha_inicio y fecha_fin)
function buscarRecibos(page = 1) {
    var searchTerm = document.getElementById('searchInput').value;
    var fechaInicio = document.getElementById('fecha_inicio') ? document.getElementById('fecha_inicio').value : '';
    var fechaFin = document.getElementById('fecha_fin') ? document.getElementById('fecha_fin').value : '';

    $.ajax({
        url: '/buscarPago?page=' + page,
        type: 'GET',
        data: { 
            search: searchTerm,
            fecha_inicio: fechaInicio,
            fecha_fin: fechaFin
        },
        success: function(response) {
            $('#recibosBody').html(response.recibosBodyHtml);
            $('#paginacion').html(response.paginationLinks);
        },
        error: function(jqXHR, textStatus, errorThrown) {
           
        }
    });
}

// Capturar clicks en los enlaces de paginación y hacer AJAX en lugar de recargar página
$(document).on('click', '#paginacion a', function(e) {
    e.preventDefault();
    var page = $(this).attr('href').split('page=')[1];
    buscarRecibos(page);
});


document.addEventListener('click', function (e) {
    if (!e.target.closest('.cancelar-btn')) return;

    const button = e.target.closest('.cancelar-btn');
    const ticketId = button.getAttribute('data-id');
    const estadoId = button.getAttribute('data-estado');
    const form = document.getElementById(`cancelarForm-${ticketId}`);
    
        if (estadoId != 3) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Solo se pueden cancelar pagos con estado "Por Cancelar"',
                confirmButtonColor: '#d33'
            });
            return;
        }

        Swal.fire({
            title: '¿Estás seguro?',
            text: "¡No podrás revertir esta acción!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, cancelar',
            cancelButtonText: 'No, volver'
        }).then((result) => {
            if (result.isConfirmed) {
                // Enviar el formulario por AJAX
                fetch(form.action, {
                    method: 'POST', // Laravel interpreta @method('PATCH') si envías _method
                    headers: {
                        'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        _method: 'PATCH' // Laravel necesita esto para PATCH
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: data.message,
                            confirmButtonColor: '#3085d6'
                        }).then(() => {
                            window.location.reload(); // Recargar o actualizar UI
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message,
                            confirmButtonColor: '#d33'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Ocurrió un error inesperado',
                        confirmButtonColor: '#d33'
                    });
                });
            }
        });
    });

//modal fechas corte dinamico
document.addEventListener('DOMContentLoaded', function() {
    // Event listener para el botón "Generar Corte Dinámico"
    var btnGenerarCorteDinamico = document.getElementById('btnGenerarCorteDinamico');
    var closeModalButtons = document.querySelectorAll('[data-dismiss="modal"]');
    var modalCorte = document.getElementById('modalCorte');

    if (btnGenerarCorteDinamico) {
        btnGenerarCorteDinamico.addEventListener('click', function() {
            modalCorte.classList.add('show'); // Agregar la clase 'show' para mostrar la modal
            modalCorte.setAttribute('aria-hidden', 'false');
            modalCorte.setAttribute('style', 'display: block');
            document.body.classList.add('modal-open'); // Agregar clase para indicar que hay una modal abierta
            var backdrop = document.createElement('div');
            backdrop.classList.add('modal-backdrop', 'fade', 'show');
            document.body.appendChild(backdrop); // Agregar el fondo oscuro
        });
    } else {
        console.error('No se encontró el botón con ID "btnGenerarCorteDinamico".');
    }

    // Event listener para el botón "Generar Corte" dentro de la modal
    var btnGenerarCorte = document.getElementById('btnGenerarCorte');
    if (btnGenerarCorte) {
        btnGenerarCorte.addEventListener('click', function() {
            // Obtener las fechas seleccionadas
            var fechaInicio = document.getElementById('fechaInicio').value;
            var fechaFin = document.getElementById('fechaFin').value;

            // Validar que las fechas no estén vacías
            if (fechaInicio && fechaFin) {
              

                // Aquí puedes agregar la lógica para generar el corte dinámico con las fechas seleccionadas

                // Cerrar la modal después de procesar las fechas
                modalCorte.classList.remove('show');
                modalCorte.setAttribute('aria-hidden', 'true');
                modalCorte.setAttribute('style', 'display: none');
                document.body.classList.remove('modal-open');
                var backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    document.body.removeChild(backdrop); // Remover el fondo oscuro de la modal
                }
            } else {
                alert('Por favor selecciona ambas fechas.');
            }
        });
    } else {
        console.error('No se encontró el botón con ID "btnGenerarCorte".');
    }

    // Event listener para cerrar la modal al hacer clic en el botón de cerrar (x) y en "Cancelar"
    closeModalButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            modalCorte.classList.remove('show');
            modalCorte.setAttribute('aria-hidden', 'true');
            modalCorte.setAttribute('style', 'display: none');
            document.body.classList.remove('modal-open');
            var backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                document.body.removeChild(backdrop); // Remover el fondo oscuro de la modal
            }
        });
    });
});
 
//cortes diarios
document.getElementById('generarCorteForm').addEventListener('submit', function(e) {
    e.preventDefault(); // Evita el envío tradicional
    
    Swal.fire({
        title: 'Generando Reporte',
        html: 'Por favor, espere...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
            
            // Enviar el formulario vía AJAX
            fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json'
                },
                body: new FormData(this)
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw err; });
                }
                return response.blob(); // Para manejar el PDF
            })
            .then(blob => {
                // Si hay PDF, abrirlo en nueva pestaña
                const pdfUrl = URL.createObjectURL(blob);
                window.open(pdfUrl, '_blank');
                Swal.close();
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'No hay registros en el rango de fechas seleccionado',
                    confirmButtonColor: '#d33'
                });
            });
        }
    });
});

