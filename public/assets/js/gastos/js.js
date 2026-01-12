// public/js/gastos.js

// Función para mostrar notificaciones SweetAlert
function showSwalNotification(icon, title, text, isToast = true) {
    if (isToast) {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        
        return Toast.fire({ icon, title, text });
    }
    
    return Swal.fire({ 
        icon, 
        title, 
        text, 
        showConfirmButton: true,
        timer: 5000,
        timerProgressBar: true
    });
}

// Función para mostrar errores de validación
function showValidationErrors(errors) {
    let errorMessages = errors.map(error => `<p>${error}</p>`).join('');
    return Swal.fire({
        icon: 'error',
        title: 'Error de validación',
        html: errorMessages,
        showConfirmButton: true
    });
}

// Inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', function() {
    const nuevoGastoModal = document.getElementById('nuevoGastoModal');
    const formNuevoGasto = document.getElementById('formNuevoGasto');
    const montoInput = document.getElementById('monto');
    const fechaInput = document.getElementById('fecha');

    // Configuración de la modal
    if (nuevoGastoModal) {
        // Limpiar formulario al abrir modal
        nuevoGastoModal.addEventListener('show.bs.modal', function() {
            if (formNuevoGasto) formNuevoGasto.reset();
        });
    }

    // Manejar envío del formulario de nuevo gasto
   // En tu archivo gastos.js - modifica la función de envío del formulario

    if (formNuevoGasto) {
        formNuevoGasto.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            
            // Deshabilitar el botón y cambiar texto
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            
            const formData = new FormData(this);
            const monto = parseFloat(formData.get('monto'));
            
            if (monto <= 0 || isNaN(monto)) {
                showSwalNotification('error', 'Error', 'El monto debe ser mayor que cero', false);
                // Rehabilitar el botón en caso de error
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
                montoInput.focus();
                return;
            }
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: '¡Éxito!',
                        text: data.message || 'Gasto registrado correctamente',
                        icon: 'success',
                        confirmButtonText: 'Aceptar',
                        timer: 3000,
                        timerProgressBar: true
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    showValidationErrors(data.errors || [data.message]);
                    // Rehabilitar el botón en caso de error
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Ocurrió un error al procesar la solicitud',
                    icon: 'error',
                    confirmButtonText: 'Entendido'
                });
                // Rehabilitar el botón en caso de error
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            });
        });
    }

    // Formatear monto a 2 decimales
    if (montoInput) {
        montoInput.addEventListener('input', function() {
            let value = this.value;
            if (value.includes('.')) {
                let parts = value.split('.');
                if (parts[1].length > 2) {
                    this.value = parts[0] + '.' + parts[1].substring(0, 2);
                }
            }
        });
    }

    // Manejar edición de gastos
    document.querySelectorAll('.edit-gasto').forEach(button => {
        button.addEventListener('click', function() {
            const gastoId = this.getAttribute('data-id');
            fetch(`/gastos/${gastoId}/edit`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('formEditarGasto').action = `/gastos/${gastoId}`;
                    document.getElementById('edit_descripcion').value = data.descripcion;
                    document.getElementById('edit_monto').value = data.monto;
                    document.getElementById('edit_fecha').value = data.fecha;
                })
                .catch(error => console.error('Error:', error));
        });
    });

    // Manejar eliminación de gastos
    document.querySelectorAll('.delete-gasto').forEach(button => {
        button.addEventListener('click', function() {
            const form = this.closest('form');
            Swal.fire({
                title: '¿Estás seguro?',
                text: "¡No podrás revertir esto!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, eliminarlo!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: '¡Eliminado!',
                                text: data.message || 'Gasto eliminado correctamente',
                                icon: 'success',
                                confirmButtonText: 'Aceptar',
                                timer: 3000,
                                timerProgressBar: true
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: data.message || 'Error al eliminar el gasto',
                                icon: 'error',
                                confirmButtonText: 'Entendido'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error',
                            text: 'Ocurrió un error al procesar la solicitud',
                            icon: 'error',
                            confirmButtonText: 'Entendido'
                        });
                    });
                }
            });
        });
    });

    // Validación del formulario de edición
    if (document.getElementById('formEditarGasto')) {
        document.getElementById('formEditarGasto').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const monto = parseFloat(formData.get('monto'));
            
            if (monto <= 0 || isNaN(monto)) {
                showSwalNotification('error', 'Error', 'El monto debe ser mayor que cero', false);
                return;
            }
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: '¡Éxito!',
                        text: data.message || 'Gasto actualizado correctamente',
                        icon: 'success',
                        confirmButtonText: 'Aceptar',
                        timer: 3000,
                        timerProgressBar: true
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    showValidationErrors(data.errors || [data.message]);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Ocurrió un error al procesar la solicitud',
                    icon: 'error',
                    confirmButtonText: 'Entendido'
                });
            });
        });
    }

    // Configuración del filtro por fechas
    if (document.getElementById('filtroForm')) {
        const filtroForm = document.getElementById('filtroForm');
        const fechaInicio = document.getElementById('fecha_inicio');
        const fechaFin = document.getElementById('fecha_fin');
        
        // Validar que fecha inicio no sea mayor a fecha fin
        filtroForm.addEventListener('submit', function(e) {
            if (fechaInicio.value && fechaFin.value && fechaInicio.value > fechaFin.value) {
                e.preventDefault();
                showSwalNotification('error', 'Error', 'La fecha de inicio no puede ser mayor a la fecha fin', false);
            }
        });
        
        // Establecer fecha fin máxima como hoy
        const today = new Date().toISOString().split('T')[0];
        if (fechaInicio) fechaInicio.max = today;
        if (fechaFin) fechaFin.max = today;
    }
});