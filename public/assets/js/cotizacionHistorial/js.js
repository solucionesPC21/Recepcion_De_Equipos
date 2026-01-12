let cotizacionIdAEliminar = null;
// Función para buscar recibos
function buscarRecibos() {
    var searchTerm = document.getElementById('searchInput').value;
    var url = '/buscarCotizacion';
    
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

// ✅ FUNCIÓN VER PDF
function verPDF(id, tipo) {
    const url = `/cotizaciones/ver/${id}/${tipo}`;
    window.open(url, '_blank');
}

// ✅ FUNCIÓN ELIMINAR COTIZACIÓN
function eliminarCotizacion(id) {
    cotizacionIdAEliminar = id;
    const modal = new bootstrap.Modal(document.getElementById('modalConfirmarEliminar'));
    modal.show();
}

// ✅ CONFIRMAR ELIMINACIÓN MEJORADA
document.getElementById('btnConfirmarEliminar').addEventListener('click', async function() {
    if (!cotizacionIdAEliminar) return;

    const btn = this;
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Eliminando...';

    try {
        const response = await fetch(`/cotizaciones/${cotizacionIdAEliminar}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });

    

        // ✅ MANEJO MEJORADO DE RESPUESTAS
        if (response.ok) {
            const data = await response.json();
            
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalConfirmarEliminar'));
                modal.hide();
                
                showNotification('Cotización eliminada correctamente', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                throw new Error(data.message || 'Error al eliminar');
            }
        } else {
            // ✅ MANEJO ESPECÍFICO DEL ERROR 500
            if (response.status === 500) {
                const errorText = await response.text();
                console.error('Error 500 - Respuesta del servidor:', errorText);
                
                // Verificar si es JSON válido
                try {
                    const errorData = JSON.parse(errorText);
                    throw new Error(errorData.message || 'Error interno del servidor');
                } catch (parseError) {
                    // Si no es JSON, es probablemente una página HTML de error
                    throw new Error('Error interno del servidor. Intente nuevamente.');
                }
            } else {
                throw new Error(`Error del servidor: ${response.status}`);
            }
        }

    } catch (error) {
        console.error('Error completo:', error);
        showNotification(error.message, 'error');
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
});

// ✅ FUNCIÓN DE NOTIFICACIÓN
function showNotification(message, type) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alert = document.createElement('div');
    alert.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
    alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alert.innerHTML = `
        <strong>${type === 'success' ? 'Éxito!' : 'Error!'}</strong> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alert);
    
    setTimeout(() => {
        if (alert.parentNode) {
            alert.parentNode.removeChild(alert);
        }
    }, 5000);
}



// ✅ FUNCIÓN PARA EDITAR COTIZACIÓN
function editarCotizacion(id) {
    if (confirm('¿Desea editar esta cotización? Se abrirá en una nueva pestaña.')) {
        // Redirigir a la página de creación con parámetro de edición
        window.open(`/cotizacion?editar=${id}`, '_blank');
    }
}
// ✅ INICIALIZAR TOOLTIPS
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

