// resources/js/clientes-minimal.js
class ClientesManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.autoHideAlerts();
    }

    setupEventListeners() {
        // Búsqueda con debounce
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            let timeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    this.buscarClientes(e.target.value);
                }, 300);
            });
        }

        // Delegación de eventos para botones
        document.addEventListener('click', (e) => {
            if (e.target.closest('.editarClienteBtn')) {
                e.preventDefault();
                const clienteId = e.target.closest('.editarClienteBtn').dataset.clienteId;
                this.editarCliente(clienteId);
            }

            if (e.target.closest('.btn-danger')) {
                e.preventDefault();
                this.confirmarEliminacion(e.target.closest('.btn-danger'));
            }
        });
    }

    autoHideAlerts() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.3s ease';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        });
    }

    async buscarClientes(termino) {
    try {
        // Mostrar indicador de carga opcional
        this.mostrarLoadingBusqueda(true);
        
        const response = await fetch(`/buscarCliente?search=${encodeURIComponent(termino)}`);
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('recibosBody').innerHTML = data.recibosBodyHtml;
            
        } else {
            this.mostrarError(data.error || 'Error en la búsqueda');
        }
    } catch (error) {
        this.mostrarError('Error de conexión al buscar clientes');
        console.error('Error:', error);
    } finally {
        this.mostrarLoadingBusqueda(false);
    }
}

// Métodos auxiliares opcionales
mostrarLoadingBusqueda(mostrar) {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        if (mostrar) {
            searchInput.parentElement.classList.add('loading');
        } else {
            searchInput.parentElement.classList.remove('loading');
        }
    }
}
    async editarCliente(clienteId) {
    try {
        // Mostrar loading
        Swal.fire({
            title: 'Cargando...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        const response = await fetch(`/clientes/${clienteId}/edit`);
        const data = await response.json();
        const { cliente, colonias } = data;
        
        Swal.close();
        this.mostrarModalEdicion(cliente, colonias);
        
    } catch (error) {
        Swal.close();
        this.mostrarError('Error al cargar datos del cliente');
    }
}

    mostrarModalEdicion(cliente, colonias) {
        // Generar options para el select de colonias
        let coloniasOptions = '<option value="">Selecciona una colonia</option>';
        colonias.forEach(colonia => {
            const selected = cliente.colonia && cliente.colonia.id === colonia.id ? 'selected' : '';
            coloniasOptions += `<option value="${colonia.colonia}" ${selected}>${colonia.colonia}</option>`;
        });

        Swal.fire({
            title: 'Editar Cliente',
            html: `
                <form id="formEditarCliente">
                    <div style="text-align: left;">
                        <div class="mb-3">
                            <label class="form-label">Nombre *</label>
                            <input type="text" class="form-control" id="swal-nombre" 
                                value="${cliente.nombre}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Teléfono *</label>
                            <input type="tel" class="form-control" id="swal-telefono" 
                                value="${cliente.telefono}" required maxlength="10">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Teléfono 2</label>
                            <input type="tel" class="form-control" id="swal-telefono2" 
                                value="${cliente.telefono2 || ''}" maxlength="10">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">RFC</label>
                            <input type="text" class="form-control" id="swal-rfc" 
                                value="${cliente.rfc || ''}" maxlength="14">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Colonia</label>
                            <select class="form-control" id="swal-colonia">
                                ${coloniasOptions}
                            </select>
                        </div>
                    </div>
                </form>
            `,
            showCancelButton: true,
            confirmButtonText: 'Actualizar',
            cancelButtonText: 'Cancelar',
            width: '600px',
            focusConfirm: false,
        }).then((result) => {
            if (result.isConfirmed) {
                this.actualizarCliente(cliente.id);
            }
        });
    }

        
    async actualizarCliente(clienteId) {
        const formData = new FormData();
        formData.append('nombre', document.getElementById('swal-nombre').value);
        formData.append('telefono', document.getElementById('swal-telefono').value);
        formData.append('telefono2', document.getElementById('swal-telefono2').value);
        formData.append('rfc', document.getElementById('swal-rfc').value);
        formData.append('colonia', document.getElementById('swal-colonia').value);
        formData.append('_method', 'PUT');
        
        try {
            const response = await fetch(`/clientes/${clienteId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            });

            if (response.ok) {
                Swal.fire('¡Actualizado!', 'Cliente actualizado correctamente', 'success')
                    .then(() => window.location.reload());
            } else {
                throw new Error('Error en la actualización');
            }
        } catch (error) {
            Swal.fire('Error', 'No se pudo actualizar el cliente', 'error');
        }
    }

    async confirmarEliminacion(boton) {
    const result = await Swal.fire({
        title: '¿Eliminar cliente?',
        text: "Esta acción no se puede deshacer",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        await this.eliminarCliente(boton);
    }
}

async eliminarCliente(boton) {
    try {
        // Mostrar loading durante la eliminación
        Swal.fire({
            title: 'Eliminando...',
            text: 'Por favor espere',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        const form = boton.closest('form');
        const formData = new FormData(form);
        
        // Cambiar el método a DELETE
        formData.append('_method', 'DELETE');

        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: formData
        });

        const data = await response.json();

        Swal.close();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Eliminado!',
                text: data.message || 'Cliente eliminado correctamente',
                confirmButtonColor: '#10b981',
                timer: 3000,
                timerProgressBar: true
            }).then(() => {
                window.location.reload();
            });
        } else {
            throw new Error(data.message || 'Error al eliminar el cliente');
        }

    } catch (error) {
        Swal.close();
        this.mostrarError(error.message);
    }
}

    mostrarError(mensaje) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: mensaje,
            confirmButtonColor: '#ef4444'
        });
    }

}

// Inicializar
document.addEventListener('DOMContentLoaded', () => {
    new ClientesManager();
});