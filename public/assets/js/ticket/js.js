// Variables globales
let reciboSeleccionado = null;
let notaReciboId = null;
let notaOriginal = '';
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
// ===== SELECCIÓN VISUAL MEJORADA =====
function seleccionarRecibo(reciboId) {
    // Deseleccionar anterior
    document.querySelectorAll('.recibo-card.selected').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Seleccionar nuevo
    const reciboCard = document.getElementById(`recibo-${reciboId}`);
    if (reciboCard) {
        reciboCard.classList.add('selected');
        reciboSeleccionado = reciboId;
        
        // Mostrar panel de selección
        mostrarPanelSeleccion(reciboCard);
        
        // Scroll suave
        reciboCard.scrollIntoView({ 
            behavior: 'smooth', 
            block: 'center' 
        });
        
        // Efecto de confeti visual
        crearEfectoConfeti(reciboCard);
    }
}

function mostrarPanelSeleccion(reciboCard) {
    const clientName = reciboCard.querySelector('.client-name').textContent;
    const reciboInfo = reciboCard.querySelector('.client-meta').textContent;
    
    document.getElementById('selectedClientName').textContent = clientName;
    document.getElementById('selectedReciboInfo').textContent = reciboInfo;
    document.getElementById('selectionPanel').style.display = 'block';
    
    // Animación de entrada
    const panel = document.getElementById('selectionPanel');
    panel.style.animation = 'slideDown 0.3s ease';
}

function deseleccionarCliente() {
    reciboSeleccionado = null;
    document.querySelectorAll('.recibo-card.selected').forEach(card => {
        card.classList.remove('selected');
    });
    document.getElementById('selectionPanel').style.display = 'none';
}

// ===== BÚSQUEDA MEJORADA =====
// ===== BÚSQUEDA MEJORADA =====
function buscarRecibos() {
    const searchTerm = document.getElementById('searchInput').value;
    
  
    
    fetch(`/buscarTicket?search=${encodeURIComponent(searchTerm)}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
       
        
        // REEMPLAZAR TODO EL CONTENIDO DEL CONTENEDOR PRINCIPAL
        document.getElementById('recibosGrid').innerHTML = data.recibosGridHtml;
        
        // Manejar empty state
        const isEmpty = !document.querySelector('.recibo-card');
        const emptyState = document.getElementById('emptyState');
        
        if (emptyState) {
            emptyState.style.display = isEmpty ? 'block' : 'none';
        }
        
        // Ocultar panel de selección si no hay resultados
        if (isEmpty) {
            document.getElementById('selectionPanel').style.display = 'none';
        }
        
      
    })
    .catch(error => {
        console.error('❌ Error en búsqueda:', error);
        mostrarNotificacionSweet('error', 'Error en la búsqueda');
    });
}
//
//
// ===== FUNCIÓN PARA GENERAR TICKET DEL CLIENTE SELECCIONADO =====
function generarTicketSeleccionado() {
  
    
    if (!reciboSeleccionado) {
        console.error('❌ No hay ningún recibo seleccionado');
        mostrarNotificacionSweet('error', 'Por favor selecciona un cliente primero');
        return;
    }
    
    // Confirmar antes de generar el ticket
    Swal.fire({
        title: '¿Generar Ticket de Cobro?',
        html: `
            <div class="text-start">
                <p><strong>¿Estás seguro de generar el ticket para el cliente seleccionado?</strong></p>
                <p class="text-muted small" id="clienteSeleccionadoInfo"></p>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, generar ticket',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Abrir el modal de generar ticket
            document.getElementById('myModal').style.display = 'block';
            document.getElementById('recibos_id').value = reciboSeleccionado;
            
          
            
            // Opcional: Cerrar el panel de selección
            deseleccionarCliente();
        }
    });
}

// ===== FUNCIÓN PARA LIMPIAR BÚSQUEDA =====
function limpiarBusqueda() {
 
    document.getElementById('searchInput').value = '';
    buscarRecibos();
}
// Búsqueda con debounce para mejor performance
let searchTimeout;
function buscarRecibosDebounced() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        buscarRecibos();
    }, 500);
}

function descargarNotaRecepcion(reciboId) {
    const btn = event.target.closest('.btn-quick-action');
    const originalHTML = btn.innerHTML;
    
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Descargando...';
    btn.disabled = true;
    
    setTimeout(() => {
        window.open(`/recibos/pdf/${reciboId}`, '_blank');
        btn.innerHTML = originalHTML;
        btn.disabled = false;
        
        mostrarNotificacionSweet('success', 'PDF generado correctamente');
    }, 1000);
}

// ===== FUNCIONES DE NOTAS CORREGIDAS =====
// ===== FUNCIONES DE NOTAS COMPLETAMENTE CORREGIDAS =====
// ===== FUNCIONES DE NOTAS AJUSTADAS PARA RUTAS GET =====
function abrirNotaModal(reciboId) {
   
    
    if (!reciboId) {
        console.error("❌ El ID del recibo no se ha proporcionado correctamente.");
        return;
    }

    notaReciboId = reciboId;
    
    // 1. RESETEAR PRIMERO ANTES DE CUALQUIER COSA
    resetearModalNotas();
    
    // 2. MOSTRAR LOADING
    document.getElementById('notaContent').innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary mb-2"></div>
            <p>Cargando nota...</p>
        </div>
    `;

    // 3. CARGAR LA NOTA
    var url = '/recibos/nota/' + reciboId;
    
    $.ajax({
        url: url,
        type: 'GET',
        success: function(response) {
         
            
            // ✅ ACTUALIZAR Y LUEGO MOSTRAR
            document.getElementById('notaContent').innerText = response.nota || 'No hay nota disponible.';
            notaOriginal = response.nota || '';
            
            // ✅ VERIFICAR ESTADO ANTES DE MOSTRAR
         
            
            // 4. MOSTRAR EL MODAL
            mostrarModalNotas(reciboId);
        },
        error: function(xhr, status, error) {
            console.error('❌ Error al cargar nota:', error);
            document.getElementById('notaContent').innerText = 'Error al cargar la nota.';
            mostrarModalNotas(reciboId);
        }
    });
}
function mostrarModalNotas(reciboId) {
   
    
    var modal = $('#notaModal');
    
    // Configurar datos
    modal.attr('data-recibo-id', reciboId);
    
    // MOSTRAR EL MODAL CON CONFIGURACIÓN CORRECTA
    modal.modal({
        backdrop: true,    // Fondo interactivo
        keyboard: true,    // Permitir ESC
        show: true         // Mostrar inmediatamente
    });
    
    // Forzar que se muestre correctamente
    modal.modal('show');
    
 
}

function resetearModalNotas() {
  
    
    // ✅ FORZAR EL ESTADO INICIAL DE VISUALIZACIÓN
    document.getElementById('noteView').style.display = 'block';
    document.getElementById('noteEdit').style.display = 'none';
    document.getElementById('editNotaButton').style.display = 'inline-block';
    document.getElementById('guardarNotaButton').style.display = 'none';
    
    // ✅ LIMPIAR CAMPOS COMPLETAMENTE
    document.getElementById('notaInput').value = '';
    
    // ✅ OCULTAR EL TEXTAREA EXPLÍCITAMENTE
    document.getElementById('notaInput').style.display = 'none';
    
    // ✅ ASEGURAR QUE EL CONTENIDO ESTÉ VISIBLE
    document.getElementById('notaContent').style.display = 'block';
    
    notaOriginal = '';
    
   
}

function cerrarNotaModal() {
   
    
    // Cerrar correctamente
    $('#notaModal').modal('hide');
    
    // Limpiar el backdrop después de cerrar
    setTimeout(() => {
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open');
        $('body').css('padding-right', '');
    }, 300);
}

function habilitarEdicionNota() {
   
    
    var notaContent = document.getElementById('notaContent');
    var notaInput = document.getElementById('notaInput');
    var guardarNotaButton = document.getElementById('guardarNotaButton');
    var editNotaButton = document.getElementById('editNotaButton');

    // Guardar el contenido actual
    notaOriginal = notaContent.innerText;
    
    // Configurar edición
    notaInput.value = notaContent.innerText;
    notaInput.style.display = 'block';
    guardarNotaButton.style.display = 'inline-block';
    editNotaButton.style.display = 'none';
    notaContent.style.display = 'none';
    
    // Enfocar después de un pequeño delay
    setTimeout(() => {
        notaInput.focus();
        notaInput.select();
    }, 100);
}

function cancelarEdicionNota() {
  
    
    var notaContent = document.getElementById('notaContent');
    var notaInput = document.getElementById('notaInput');
    var guardarNotaButton = document.getElementById('guardarNotaButton');
    var editNotaButton = document.getElementById('editNotaButton');

    // Volver a vista normal
    notaInput.style.display = 'none';
    guardarNotaButton.style.display = 'none';
    editNotaButton.style.display = 'inline-block';
    notaContent.style.display = 'block';
}

function guardarNota() {
  
    
    var notaInput = document.getElementById('notaInput');
    if (!notaInput) {
        console.error('❌ El elemento notaInput no se encontró.');
        return;
    }

    var reciboId = $('#notaModal').attr('data-recibo-id');


    if (!reciboId) {
        console.error('❌ El ID del recibo no se ha proporcionado correctamente.');
        return;
    }

    // Validar si hay cambios
    if (notaInput.value === notaOriginal) {
      
        mostrarNotificacionSweet('info', 'No se detectaron cambios en la nota');
        cancelarEdicionNota();
        return;
    }

    // Mostrar loading en el botón
    const guardarBtn = document.getElementById('guardarNotaButton');
    const originalText = guardarBtn.innerHTML;
    guardarBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';
    guardarBtn.disabled = true;

  
    
    // ✅ CORREGIDO: USAR GET Y PARÁMETROS EN LA URL
    var url = '/recibos/agregarnota' + reciboId;
    var params = {
        id: reciboId,
        nota: notaInput.value,
        _token: $('meta[name="csrf-token"]').attr('content')
    };
    
   
    
    // ✅ USAR GET CON PARÁMETROS EN LA URL
    $.ajax({
        url: url,
        type: 'GET',
        data: params,
        success: function(response) {
         
            
            // Actualizar la vista
            document.getElementById('notaContent').innerText = notaInput.value;
            notaOriginal = notaInput.value;
            
            // Mostrar éxito y volver a vista normal
            mostrarNotificacionSweet('success', 'Nota guardada correctamente');
            cancelarEdicionNota();
        },
        error: function(xhr, status, error) {
            console.error('❌ Error al guardar la nota:', error);
            console.error('Detalles:', xhr.responseText);
            mostrarNotificacionSweet('error', 'Error al guardar la nota: ' + error);
        },
        complete: function() {
            // Restaurar el botón
            guardarBtn.innerHTML = originalText;
            guardarBtn.disabled = false;
        }
    });
}
// ===== NOTIFICACIONES CORREGIDAS =====
function mostrarNotificacionSweet(tipo, mensaje) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: tipo,
            title: tipo === 'success' ? 'Éxito' : 
                   tipo === 'error' ? 'Error' : 
                   tipo === 'info' ? 'Información' : 'Aviso',
            text: mensaje,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
    } else {
        alert(mensaje);
    }
}

function mostrarMensajeError(mensaje) {
    mostrarNotificacionSweet('error', mensaje);
}

// ===== COMPLETAR RECIBO =====
function abrirCompletadoConfirmar(idRecibo) {
    Swal.fire({
        title: '¿Marcar como COMPLETADO?',
        text: "Esta acción cambiará el estado del recibo a completado",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, completar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            completarRecibo(idRecibo);
        }
    });
}

function completarRecibo(idRecibo) {
    const token = document.querySelector('meta[name="csrf-token"]').content;
    
    Swal.fire({
        title: 'Procesando...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });
    
    fetch('/ticket/actualizarEstadoRecibo', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token
        },
        body: JSON.stringify({
            id_recibo: idRecibo,
            id_estado: 3
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: '¡Completado!',
                text: 'El recibo ha sido marcado como completado',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                window.location.reload();
            });
        } else {
            throw new Error(data.message || 'Error al actualizar el estado');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarNotificacionSweet('error', 'Error al completar el recibo: ' + error.message);
    });
}

// ===== EFECTO CONFETI =====
function crearEfectoConfeti(element) {
    element.style.transform = 'scale(1.02)';
    setTimeout(() => element.style.transform = 'scale(1)', 300);
}

// ===== INICIALIZACIÓN =====
document.addEventListener('DOMContentLoaded', function() {
  
    
    // Búsqueda
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', buscarRecibosDebounced);
    }
    
    // Event listeners para el modal de notas
   // Cuando el modal se muestra completamente
    $('#notaModal').on('shown.bs.modal', function () {    
        // Asegurar z-index
        $(this).css('z-index', '99999');
        $('.modal-backdrop').css('z-index', '99998');
        
        // Forzar que los botones sean clickeables
        $('#notaModal .btn').css({
            'pointer-events': 'auto',
            'cursor': 'pointer'
        });
    });
    
    // Cuando el modal se oculta
    $('#notaModal').on('hidden.bs.modal', function () {
       
        cancelarEdicionNota();
        
        // Limpiar completamente
        setTimeout(() => {
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open');
            $('body').css('padding-right', '');
        }, 150);
    });
    
    // Prevenir problemas de clics
    $('#notaModal').on('click', function(e) {
        e.stopPropagation();
    });
    
    // Asegurar que los botones del modal funcionen
    $(document).on('click', '#notaModal .btn', function(e) {
        e.stopPropagation();
      
    });


    // ===== INICIALIZACIÓN DE CONCEPTOS =====
    var conceptoContainer = document.getElementById('conceptoContainer');
    var agregarConceptoBtn = document.getElementById('agregarConcepto');
    var ticketForm = document.getElementById('ticketForm');

    // Configurar el primer grupo de concepto
    if (conceptoContainer && conceptoContainer.firstElementChild) {
        setupConceptoGroup(conceptoContainer.firstElementChild);
    }

    // Evento para agregar nuevo concepto
    if (agregarConceptoBtn) {
        agregarConceptoBtn.addEventListener('click', function() {
            if (!conceptoContainer.firstElementChild) return;
            
            var newConceptoGroup = conceptoContainer.firstElementChild.cloneNode(true);
            
            // Limpiar valores
            newConceptoGroup.querySelector('.concepto-input').value = '';
            newConceptoGroup.querySelector('.cantidad-input').value = '1';
            newConceptoGroup.querySelector('.precio-input').value = '';
            newConceptoGroup.querySelector('.precio-input').removeAttribute('readonly');
            newConceptoGroup.querySelector('.total').value = '$0.00';
            newConceptoGroup.querySelector('.categoria-select').value = '';
            
            // Limpiar sugerencias y errores
            newConceptoGroup.querySelector('.suggestions-container').innerHTML = '';
            newConceptoGroup.querySelector('.suggestions-container').style.display = 'none';
            limpiarErrorStock(newConceptoGroup.querySelector('.cantidad-input'));
            
            // Agregar botón de eliminar
            var deleteButton = document.createElement('button');
            deleteButton.textContent = 'Eliminar Concepto';
            deleteButton.className = 'eliminar-concepto btn btn-danger btn-sm';
            newConceptoGroup.appendChild(deleteButton);
            
            conceptoContainer.appendChild(newConceptoGroup);
            setupConceptoGroup(newConceptoGroup);
            newConceptoGroup.querySelector('.concepto-input').focus();
        });
    }

    // Evento para eliminar concepto
    if (conceptoContainer) {
        conceptoContainer.addEventListener('click', function(event) {
            if (event.target.classList.contains('eliminar-concepto')) {
                if (conceptoContainer.children.length > 1) {
                    event.target.closest('.concepto-group').remove();
                    calcularTotalGeneral();
                } else {
                    mostrarMensajeError('Debe haber al menos un concepto.');
                }
            }
        });
    }

    // Evento para calcular totales
    if (ticketForm) {
        ticketForm.addEventListener('input', function() {
            document.querySelectorAll('.concepto-group').forEach(group => {
                calcularTotalConcepto(group);
            });
            calcularTotalGeneral();
        });
    }

    // Evento para validar formulario
    if (ticketForm) {
        ticketForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            if (!validarInventario()) {
                return;
            }
            
            Swal.fire({
                title: '¿Generar Ticket de Pago?',
                html: `
                    <div class="text-start">
                        <p><strong>¿Estás seguro de generar el ticket?</strong></p>
                        <p class="text-muted small">Total: ${document.getElementById('total_general').value}</p>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, generar ticket',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    generarTicket();
                }
            });
        });
    }

    // Evento para cerrar modal de tickets
    var closeModal = document.querySelector('.close');
    if (closeModal) {
        closeModal.addEventListener('click', function() {
            document.getElementById('myModal').style.display = 'none';
            resetearModal();
        });
    }

    // Evento para cerrar modal al hacer clic fuera
    window.addEventListener('click', function(event) {
        if (event.target == document.getElementById('myModal')) {
            document.getElementById('myModal').style.display = 'none';
            resetearModal();
        }
    });

    // Evento para convertir a mayúsculas
    document.addEventListener('input', function(event) {
        if (event.target.nodeName === 'INPUT' && event.target.type === 'text') {
            event.target.value = event.target.value.toUpperCase();
        }
    });
});

// ===== FUNCIONES DE CONCEPTOS (MANTENIDAS) =====
function setupConceptoValidation(inputElement, errorElement) {
    inputElement.addEventListener('input', function() {
        errorElement.style.display = this.value.trim() === '' ? 'block' : 'none';
    });
}

function displaySuggestions(suggestions, container) {
    let suggestionHtml = '';
    
    if (suggestions.length > 0) {
        suggestions.forEach(suggestion => {
            const isInventoryItem = suggestion.id_categoria == 2;
            const isAvailable = isInventoryItem ? suggestion.cantidad > 0 : true;
            
            const displayText = `${suggestion.nombre} ${suggestion.marca ? '| ' + suggestion.marca : ''} ${suggestion.modelo ? '| ' + suggestion.modelo : ''} | $${suggestion.precio}`;
            
            suggestionHtml += `
                <div class="suggestion-item ${!isAvailable ? 'disabled text-danger' : ''}" 
                    data-id="${suggestion.id}"
                    data-nombre="${suggestion.nombre}"
                    data-precio="${suggestion.precio}"
                    data-categoria="${suggestion.id_categoria}"
                    data-cantidad="${suggestion.cantidad}"
                    data-marca="${suggestion.marca || ''}"
                    data-modelo="${suggestion.modelo || ''}">
                    ${displayText}
                    ${isInventoryItem ? 
                      (isAvailable ? 
                        `<span class="badge bg-success">Stock: ${suggestion.cantidad}</span>` : 
                        `<span class="badge bg-danger">AGOTADO</span>`) : 
                      `<span class="badge bg-info">Servicio</span>`}
                </div>
            `;
        });
    }
    
    container.innerHTML = suggestionHtml;
    container.style.display = 'block';
}

function mostrarErrorStock(inputElement, disponible) {
    inputElement.classList.add('is-invalid');
    const errorElement = inputElement.nextElementSibling || document.createElement('div');
    errorElement.className = 'invalid-feedback text-danger fw-bold';
    errorElement.textContent = `STOCK INSUFICIENTE (Disponible: ${disponible})`;
    inputElement.parentNode.appendChild(errorElement);
}

function limpiarErrorStock(inputElement) {
    inputElement.classList.remove('is-invalid');
    if (inputElement.nextElementSibling) {
        inputElement.nextElementSibling.textContent = '';
    }
}

function setupConceptoGroup(conceptoGroup) {
    const conceptoInput = conceptoGroup.querySelector('.concepto-input');
    const precioInput = conceptoGroup.querySelector('.precio-input');
    const categoriaSelect = conceptoGroup.querySelector('.categoria-select');
    const cantidadInput = conceptoGroup.querySelector('.cantidad-input');
    const totalInput = conceptoGroup.querySelector('.total');
    const conceptoError = conceptoGroup.querySelector('.concepto-error');
    const suggestionsContainer = conceptoGroup.querySelector('.suggestions-container');
    const conceptoIdInput = conceptoGroup.querySelector('.concepto-id') || createHiddenInput();

    function createHiddenInput() {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'concepto_id[]';
        input.className = 'concepto-id';
        conceptoGroup.appendChild(input);
        return input;
    }

    conceptoInput.addEventListener('input', function() {
        conceptoError.style.display = this.value.trim() === '' ? 'block' : 'none';
        this.value = this.value.toUpperCase();
        
        if (this.value.trim().length > 0 && !suggestionsContainer.querySelector('.suggestion-item:hover')) {
            conceptoIdInput.value = '';
            precioInput.removeAttribute('readonly');
            categoriaSelect.value = '';
        }
    });

    let searchTimeout;
    conceptoInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length >= 3 && !conceptoIdInput.value) {
            suggestionsContainer.innerHTML = '<div class="suggestion-item">Buscando...</div>';
            suggestionsContainer.style.display = 'block';
            
            searchTimeout = setTimeout(() => {
                fetch(`/buscarConcepto?query=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => displaySuggestions(data, suggestionsContainer))
                    .catch(() => {
                        suggestionsContainer.innerHTML = '<div class="suggestion-item">Error al cargar sugerencias</div>';
                    });
            }, 300);
        } else {
            suggestionsContainer.innerHTML = '';
            suggestionsContainer.style.display = 'none';
        }
    });

    suggestionsContainer.addEventListener('click', function(event) {
        const item = event.target.closest('.suggestion-item');
        if (!item || item.classList.contains('disabled')) return;
        
        const { id, nombre, precio, categoria, cantidad } = item.dataset;
        
        conceptoInput.value = nombre;
        precioInput.value = precio;
        categoriaSelect.value = categoria;
        conceptoIdInput.value = id;
        
        if (cantidadInput) {
            cantidadInput.dataset.disponible = categoria == 2 ? cantidad : '9999';
            cantidadInput.value = '1';
            cantidadInput.dispatchEvent(new Event('input'));
        }
        
        suggestionsContainer.innerHTML = '';
        suggestionsContainer.style.display = 'none';
    });

    precioInput.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9.]/g, '');
        
        if (this.value.includes('.')) {
            const parts = this.value.split('.');
            if (parts[1].length > 2) {
                this.value = parseFloat(this.value).toFixed(2);
            }
        }
        
        if (categoriaSelect.value == 2 && cantidadInput) {
            const cantidad = parseInt(cantidadInput.value) || 0;
            const disponible = parseInt(cantidadInput.dataset.disponible || 0);
            
            if (cantidad > disponible) {
                mostrarErrorStock(cantidadInput, disponible);
            } else {
                limpiarErrorStock(cantidadInput);
            }
        }
        
        calcularTotalConcepto(conceptoGroup);
        calcularTotalGeneral();
    });

    cantidadInput.addEventListener('input', function() {
        this.value = Math.max(1, parseInt(this.value) || 1);
        
        if (categoriaSelect.value == 2) {
            const cantidad = parseInt(this.value);
            const disponible = parseInt(this.dataset.disponible || 0);
            
            if (cantidad > disponible) {
                mostrarErrorStock(this, disponible);
            } else {
                limpiarErrorStock(this);
            }
        }
        
        calcularTotalConcepto(conceptoGroup);
        calcularTotalGeneral();
    });

    categoriaSelect.addEventListener('change', function() {
        if (this.value == 2 && !conceptoIdInput.value) {
            cantidadInput.dataset.disponible = '0';
        }
        calcularTotalConcepto(conceptoGroup);
    });

    document.addEventListener('click', function(event) {
        if (!conceptoGroup.contains(event.target) && 
            !event.target.closest('.suggestions-container')) {
            suggestionsContainer.style.display = 'none';
        }
    });
}

function calcularTotalConcepto(group) {
    const cantidad = parseFloat(group.querySelector('.cantidad-input').value) || 0;
    const precio = parseFloat(group.querySelector('.precio-input').value) || 0;
    const total = cantidad * precio;
    group.querySelector('.total').value = '$' + total.toFixed(2);
}

function calcularTotalGeneral() {
    let totalGeneral = 0;
    document.querySelectorAll('.concepto-group').forEach(group => {
        const totalText = group.querySelector('.total').value;
        const totalValue = parseFloat(totalText.replace('$', '')) || 0;
        totalGeneral += totalValue;
    });
    
    const totalGeneralInput = document.getElementById('total_general');
    if (totalGeneralInput) {
        totalGeneralInput.value = '$' + totalGeneral.toFixed(2);
    }
}

function validarInventario() {
    let isValid = true;
    let errorMessage = '';
    const conceptos = document.querySelectorAll('.concepto-group');
    
    conceptos.forEach(grupo => {
        const categoria = grupo.querySelector('.categoria-select').value;
        const cantidadInput = grupo.querySelector('.cantidad-input');
        const precioInput = grupo.querySelector('.precio-input');
        const conceptoInput = grupo.querySelector('.concepto-input');
        
        if (!conceptoInput.value.trim() || !precioInput.value.trim() || cantidadInput.value.trim() === '') {
            isValid = false;
            errorMessage = 'Todos los campos de concepto son obligatorios.';
            return;
        }
        
        if (categoria == 2) {
            const cantidad = parseInt(cantidadInput.value) || 0;
            const disponible = parseInt(cantidadInput.dataset.disponible || 0);
            
            if (cantidad > disponible) {
                mostrarErrorStock(cantidadInput, disponible);
                isValid = false;
                errorMessage = 'La cantidad solicitada para uno o más productos excede el stock disponible.';
                
                Swal.fire({
                    title: 'Stock Insuficiente',
                    html: `
                        <div class="text-start">
                            <p>Producto: <strong>${conceptoInput.value}</strong></p>
                            <p>Solicitado: <strong>${cantidad}</strong></p>
                            <p>Disponible: <strong>${disponible}</strong></p>
                        </div>
                    `,
                    icon: 'warning',
                    confirmButtonColor: '#ffc107'
                });
            }
        }
    });
    
    return isValid;
}

function resetearModal() {
    document.getElementById('recibos_id').value = '';
    
    var conceptoContainer = document.getElementById('conceptoContainer');
    while (conceptoContainer.children.length > 1) {
        conceptoContainer.removeChild(conceptoContainer.lastChild);
    }
    
    var firstGroup = conceptoContainer.firstElementChild;
    firstGroup.querySelectorAll('input').forEach(input => {
        input.value = '';
        input.removeAttribute('readonly');
    });
    firstGroup.querySelector('select').value = '';
    
    firstGroup.querySelector('.suggestions-container').innerHTML = '';
    firstGroup.querySelector('.suggestions-container').style.display = 'none';
    limpiarErrorStock(firstGroup.querySelector('.cantidad-input'));
    
    document.getElementById('total_general').value = '$0.00';
}

function confirmarGenerarTicket(idRecibos) {
    Swal.fire({
        title: '¿Generar Ticket?',
        text: "¿Estás seguro de generar el ticket de pago?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, generar ticket',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('myModal').style.display = 'block';
            document.getElementById('recibos_id').value = idRecibos;
        } else {
            Swal.fire({
                title: 'Cancelado',
                text: 'La generación del ticket fue cancelada',
                icon: 'info',
                timer: 1500,
                showConfirmButton: false
            });
        }
    });
}

function generarTicket() {
    const form = document.getElementById('ticketForm');
    const formData = new FormData(form);
    
    Swal.fire({
        title: 'Generando Ticket...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Respuesta no es JSON');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: '¡Pago Completado!',
                html: `
                    <div class="text-center">
                        <p class="text-muted">El ticket se ha generado correctamente</p>
                    </div>
                `,
                icon: 'success',
                confirmButtonColor: '#28a745',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            }).then(() => {
                document.getElementById('myModal').style.display = 'none';
                resetearModal();
                
                if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                } else {
                    window.location.reload();
                }
            });
        } else {
            throw new Error(data.error || 'Error al generar el ticket');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            title: 'Error',
            text: 'Error al generar el ticket: ' + error.message,
            icon: 'error',
            confirmButtonColor: '#dc3545'
        });
    });

    //modal para ver y descargar archivos
    let reciboIdActual = null;



}