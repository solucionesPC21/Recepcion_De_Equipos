document.addEventListener('DOMContentLoaded', function() {
      // =============================================
    // 1. CONSTANTES Y CONFIGURACIÓN
    // =============================================
    const searchInput = document.getElementById('searchInput');
    const serviciosTabla = document.getElementById('serviciosTabla');
    const tablaOriginalHTML = serviciosTabla.innerHTML;

     // =============================================
    // 2. FUNCIONES UTILITARIAS
    // =============================================
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }


    function convertirAMayusculas() {
        document.querySelectorAll('input[type="text"]').forEach(function(campo) {
            campo.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
        });
    }

      // A. Edición de Productos
    function setupEdicionServicios() {
        document.addEventListener('click', async function(e) {
            if (e.target.closest('.edit-btn')) {
                const button = e.target.closest('.edit-btn');
                const serviciotId = button.dataset.id;
                
                try {
                    const response = await fetch(`/servicios/${serviciotId}/edit`);
                    const data = await response.json();
                    
                    const { value: formValues } = await Swal.fire({
                        title: 'Editar Servicio',
                        html: `
                            <form id="editServicioForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="edit_nombre">Nombre </label>
                                            <input type="text" id="edit_nombre" class="form-control" value="${data.nombre || ''}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                         <div class="form-group">
                                            <label for="edit_precio">Precio</label>
                                            <input type="text" id="edit_precio" class="form-control" value="${data.precio || ''}" required>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        `,
                        focusConfirm: false,
                        showCancelButton: true,
                        confirmButtonText: 'Guardar Cambios',
                        cancelButtonText: 'Cancelar',
                        preConfirm: () => {
                            return {
                                nombre: document.getElementById('edit_nombre').value,
                                precio: document.getElementById('edit_precio').value,
                            }
                        }
                    });
                    
                    if (formValues) {
                        const updateResponse = await fetch(`/servicios/${serviciotId}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(formValues)
                        });
                        
                        const result = await updateResponse.json();
                        
                        if (updateResponse.ok) {
                            Swal.fire(
                                '¡Actualizado!',
                                'El Servicio ha sido actualizado correctamente.',
                                'success'
                            ).then(() => window.location.reload());
                        } else {
                            throw new Error(result.message || 'Error al actualizar');
                        }
                    }
                    
                } catch (error) {
                    Swal.fire(
                        'Error',
                        error.message || 'Ocurrió un error al editar el servicio',
                        'error'
                    );
                    console.error('Error detallado:', error);
                }
            }
        });
    }

     function setupEliminacionServicios() {
        document.addEventListener('submit', function(e) {
            if (e.target.classList.contains('delete-form')) {
                e.preventDefault();
                
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "¡No podrás revertir esta acción!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = e.target;
                        
                        fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                _method: 'DELETE'
                            })
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Error en la respuesta del servidor');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                form.closest('tr').remove();
                                
                                Swal.fire(
                                    '¡Eliminado!',
                                    data.message || 'Servicio eliminado correctamente',
                                    'success'
                                );
                                
                                setTimeout(() => {
                                    window.location.reload();
                                }, 2000);
                            } else {
                                throw new Error(data.message || 'Error al eliminar');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire(
                                'Error',
                                error.message || 'Ocurrió un error al eliminar el Servicio',
                                'error'
                            );
                        });
                    }
                });
            }
        });
    }
    ///
    function setupBusquedaServicios() {
    let currentPage = 1;
    let lastSearchTerm = '';
    let totalPages = 1;
    
    const buscarServicios = debounce(function(termino, page = 1) {
        if(termino.length === 0) {
            serviciosTabla.innerHTML = tablaOriginalHTML;
            return;
        }
        
        if(termino.length < 2) {
            return;
        }
        
        lastSearchTerm = termino;
        currentPage = page;
        
        fetch(`/buscarServicio?q=${encodeURIComponent(termino)}&page=${page}`)
            .then(response => response.json())
            .then(data => {
                serviciosTabla.innerHTML = '';
                
                if(data.data.length === 0) {
                    serviciosTabla.innerHTML = '<tr><td colspan="8" class="text-center">No se encontraron servicios</td></tr>';
                    return;
                }
                
                // Calcular el número base para la numeración
                const baseNumber = (data.current_page - 1) * data.per_page;
                
                data.data.forEach((servicio, index) => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${baseNumber + index + 1}</td> 
                        <td>${servicio.nombre}</td>
                        <td>$${parseFloat(servicio.precio).toFixed(2)}</td>
                        <td class="text-center">
                            <div class="btn-group" style="gap: 10px;">
                                <button class="btn btn-warning btn-sm edit-btn" data-id="${servicio.id}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-fill" viewBox="0 0 16 16">
                                        <path d="M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708zm.646 6.061L9.793 2.5 3.293 9H3.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.207zm-7.468 7.468A.5.5 0 0 1 6 13.5V13h-.5a.5.5 0 0 1-.5-.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.5-.5V10h-.5a.5.5 0 0 1-.175-.032l-.179.178a.5.5 0 0 0-.11.168l-2 5a.5.5 0 0 0 .65.65l5-2a.5.5 0 0 0 .168-.11z"/>
                                    </svg>
                                </button>
                                  ${window.isAdmin ? `
                                    <form action="/servicios/${servicio.id}" method="POST" class="d-inline delete-form">
                                        <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash3-fill" viewBox="0 0 16 16">
                                                <path d="M11 1.5v1h3.5a.5.5 0 0 1 0 1h-.538l-.853 10.66A2 2 0 0 1 11.115 16h-6.23a2 2 0 0 1-1.994-1.84L2.038 3.5H1.5a.5.5 0 0 1 0-1H5v-1A1.5 1.5 0 0 1 6.5 0h3A1.5 1.5 0 0 1 11 1.5m-5 0v1h4v-1a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5M4.5 5.029l.5 8.5a.5.5 0 1 0 .998-.06l-.5-8.5a.5.5 0 1 0-.998.06m6.53-.528a.5.5 0 0 0-.528.47l-.5 8.5a.5.5 0 0 0 .998.058l.5-8.5a.5.5 0 0 0-.47-.528M8 4.5a.5.5 0 0 0-.5.5v8.5a.5.5 0 0 0 1 0V5a.5.5 0 0 0-.5-.5"/>
                                            </svg>
                                        </button>
                                    </form>
                                ` : ''}
                            </div>
                        </td>
                    `;
                    serviciosTabla.appendChild(row);
                });
                
                // Actualizar la paginación
                updatePagination(data);
            })
            .catch(error => {
                console.error('Error:', error);
                servicios.innerHTML = '<tr><td colspan="8" class="text-center">Error al cargar los datos</td></tr>';
            });
    }, 300);
    
    function updatePagination(data) {
        const paginationContainer = document.querySelector('.pagination');
        paginationContainer.innerHTML = '';
        
        if(data.last_page <= 1) return;
        
        // Botón Anterior
        if(data.current_page > 1) {
            const prevLi = document.createElement('li');
            prevLi.className = 'page-item';
            prevLi.innerHTML = `<a class="page-link" href="#" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a>`;
            prevLi.addEventListener('click', (e) => {
                e.preventDefault();
                buscarServicios(lastSearchTerm, data.current_page - 1);
            });
            paginationContainer.appendChild(prevLi);
        }
        
        // Números de página
        for(let i = 1; i <= data.last_page; i++) {
            const pageLi = document.createElement('li');
            pageLi.className = `page-item ${i === data.current_page ? 'active' : ''}`;
            pageLi.innerHTML = `<a class="page-link" href="#">${i}</a>`;
            pageLi.addEventListener('click', (e) => {
                e.preventDefault();
                if(i !== data.current_page) {
                    buscarServicios(lastSearchTerm, i);
                }
            });
            paginationContainer.appendChild(pageLi);
        }
        
        // Botón Siguiente
        if(data.current_page < data.last_page) {
            const nextLi = document.createElement('li');
            nextLi.className = 'page-item';
            nextLi.innerHTML = `<a class="page-link" href="#" aria-label="Next"><span aria-hidden="true">&raquo;</span></a>`;
            nextLi.addEventListener('click', (e) => {
                e.preventDefault();
                buscarServicios(lastSearchTerm, data.current_page + 1);
            });
            paginationContainer.appendChild(nextLi);
        }
    }
    
    searchInput.addEventListener('input', function() {
        buscarServicios(this.value.trim());
    });
}

 // E. Utilidades Adicionales
    function setupUtilidades() {
        convertirAMayusculas();
        
        // Ocultar alertas automáticamente
        setTimeout(function() {
            $(".alert").fadeOut("slow");
        }, 1500);
    }


    // =============================================
    // 4. INICIALIZACIÓN DE COMPONENTES
    // =============================================
    setupEliminacionServicios();
    setupEdicionServicios();
    setupBusquedaServicios();
    setupUtilidades();
});