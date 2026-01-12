document.addEventListener('DOMContentLoaded', function() {
    // =============================================
    // 1. CONSTANTES Y CONFIGURACIÓN
    // =============================================
    const searchInput = document.getElementById('searchInput');
    const productosTabla = document.getElementById('productosTabla');
    const tablaOriginalHTML = productosTabla.innerHTML;
    const registrarProductoBtn = document.getElementById('registrarProductoBtn');
    const modalRegistro = document.getElementById('modalRegistrarProducto');
    const guardarProductoBtn = document.getElementById('guardarProducto');

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

    function limpiarErrores() {
        $("#nombre-error, #codigo-error").text("");
        $("#nombre, #codigo_barra").css("border", "");
    }

    function limpiarInputs(modalId) {
        $(`#${modalId}`).find('input[type="text"], input[type="number"], textarea').val('');
        limpiarErrores();
        $("#guardarProducto").attr("disabled", true);
    }

    function convertirAMayusculas() {
        document.querySelectorAll('input[type="text"]').forEach(function(campo) {
            campo.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
        });
    }

    // =============================================
    // 3. FUNCIONALIDAD DE PRODUCTOS (CRUD)
    // =============================================

    // A. Edición de Productos
    function setupEdicionProductos() {
        document.addEventListener('click', async function(e) {
            if (e.target.closest('.edit-btn')) {
                const button = e.target.closest('.edit-btn');
                const productId = button.dataset.id;
                
                try {
                    const response = await fetch(`/productos/${productId}/edit`);
                    const data = await response.json();
                    
                    const { value: formValues } = await Swal.fire({
                        title: 'Editar Producto',
                        html: `
                            <form id="editProductForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="edit_nombre">Nombre Producto</label>
                                            <input type="text" id="edit_nombre" class="form-control" value="${data.nombre || ''}" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="edit_precio">Precio</label>
                                            <input type="text" id="edit_precio" class="form-control" value="${data.precio || ''}" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="edit_cantidad">Cantidad</label>
                                            <input type="number" id="edit_cantidad" class="form-control" value="${data.cantidad || ''}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="edit_codigo_barra">Código de Barra</label>
                                            <input type="text" id="edit_codigo_barra" class="form-control" value="${data.codigo_barra || ''}">
                                        </div>
                                         <div class="form-group">
                                            <label for="edit_marca">Marca</label>
                                            <input type="text" id="edit_marca" class="form-control"  value="${data.marca || ''}">
                                        </div>
                                        <div class="form-group">
                                            <label for="edit_modelo">Modelo</label>
                                            <input type="text" id="edit_modelo" class="form-control" value="${data.modelo || ''}">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="edit_descripcion">Descripción</label>
                                    <textarea id="edit_descripcion" class="form-control" rows="3">${data.descripcion || ''}</textarea>
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
                                cantidad: document.getElementById('edit_cantidad').value,
                                codigo_barra: document.getElementById('edit_codigo_barra').value,
                                marca: document.getElementById('edit_marca').value,
                                modelo: document.getElementById('edit_modelo').value,
                                descripcion: document.getElementById('edit_descripcion').value
                            }
                        }
                    });
                    
                    if (formValues) {
                        const updateResponse = await fetch(`/productos/${productId}`, {
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
                                'El producto ha sido actualizado correctamente.',
                                'success'
                            ).then(() => window.location.reload());
                        } else {
                            throw new Error(result.message || 'Error al actualizar');
                        }
                    }
                    
                } catch (error) {
                    Swal.fire(
                        'Error',
                        error.message || 'Ocurrió un error al editar el producto',
                        'error'
                    );
                    console.error('Error detallado:', error);
                }
            }
        });
    }

    // B. Eliminación de Productos
    function setupEliminacionProductos() {
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
                                    data.message || 'Producto eliminado correctamente',
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
                                error.message || 'Ocurrió un error al eliminar el producto',
                                'error'
                            );
                        });
                    }
                });
            }
        });
    }

    // C. Búsqueda de Productos
    function setupBusquedaProductos() {
    let currentPage = 1;
    let lastSearchTerm = '';
    let totalPages = 1;
    
    const buscarProductos = debounce(function(termino, page = 1) {
        if(termino.length === 0) {
            productosTabla.innerHTML = tablaOriginalHTML;
            return;
        }
        
        if(termino.length < 2) {
            return;
        }
        
        lastSearchTerm = termino;
        currentPage = page;
        
        fetch(`/buscarProducto?q=${encodeURIComponent(termino)}&page=${page}`)
            .then(response => response.json())
            .then(data => {
                productosTabla.innerHTML = '';
                
                if(data.data.length === 0) {
                    productosTabla.innerHTML = '<tr><td colspan="8" class="text-center">No se encontraron productos</td></tr>';
                    return;
                }
                
                // Calcular el número base para la numeración
                const baseNumber = (data.current_page - 1) * data.per_page;
                
                data.data.forEach((producto, index) => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${baseNumber + index + 1}</td> 
                        <td>${producto.nombre}</td>
                        <td>$${parseFloat(producto.precio).toFixed(2)}</td>
                        <td>${producto.cantidad}</td>
                        <td>${producto.modelo || ''}</td>
                        <td>${producto.marca || 'Sin Marca'}</td>
                        <td>${producto.descripcion || ''}</td>
                        <td class="text-center">
                            <div class="btn-group" style="gap: 10px;">
                                <button class="btn btn-warning btn-sm edit-btn" data-id="${producto.id}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-fill" viewBox="0 0 16 16">
                                        <path d="M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708zm.646 6.061L9.793 2.5 3.293 9H3.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.207zm-7.468 7.468A.5.5 0 0 1 6 13.5V13h-.5a.5.5 0 0 1-.5-.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.5-.5V10h-.5a.5.5 0 0 1-.175-.032l-.179.178a.5.5 0 0 0-.11.168l-2 5a.5.5 0 0 0 .65.65l5-2a.5.5 0 0 0 .168-.11z"/>
                                    </svg>
                                </button>
                                 ${window.isAdmin ? `
                                    <form action="/productos/${producto.id}" method="POST" class="d-inline delete-form">
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
                    productosTabla.appendChild(row);
                });
                
                // Actualizar la paginación
                updatePagination(data);
            })
            .catch(error => {
                console.error('Error:', error);
                productosTabla.innerHTML = '<tr><td colspan="8" class="text-center">Error al cargar los datos</td></tr>';
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
                buscarProductos(lastSearchTerm, data.current_page - 1);
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
                    buscarProductos(lastSearchTerm, i);
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
                buscarProductos(lastSearchTerm, data.current_page + 1);
            });
            paginationContainer.appendChild(nextLi);
        }
    }
    
    searchInput.addEventListener('input', function() {
        buscarProductos(this.value.trim());
    });
}

    // D. Registro de Productos
    // D. Registro de Productos
function setupRegistroProductos() {
    // Mostrar modal con SweetAlert2 de confirmación
    registrarProductoBtn.addEventListener('click', function() {
        $(modalRegistro).modal('show');
        guardarProductoBtn.disabled = true;
    });

    // Configuración del modal
    $(modalRegistro).on('hidden.bs.modal', function() {
        limpiarInputs('modalRegistrarProducto');
        $(".error-message").text(""); // Limpiar mensajes de error
    });

    $('.btn-secondary[data-dismiss="modal"]').click(function() {
        $(modalRegistro).modal('hide');
    });

    $(modalRegistro).on('click', function(event) {
        if ($(event.target).hasClass('modal')) {
            $(modalRegistro).modal('hide');
        }
    });

    // Validación en tiempo real simplificada
   let debounceTimer;

    $("#nombre, #codigo_barra, #modelo, #marca").on("input", function() {
        const nombre = $("#nombre").val().trim();
        const codigo_barra = $("#codigo_barra").val().trim();
        const modelo = $("#modelo").val().trim();
        const marca = $("#marca").val().trim();

        // Limpiar estados anteriores
        $("#nombre, #codigo_barra, #modelo, #marca").removeClass("is-invalid is-valid");
        $(".error-message").text("");

        if (nombre === "" && codigo_barra === "") {
            guardarProductoBtn.disabled = true;
            return;
        }

        clearTimeout(debounceTimer);

        debounceTimer = setTimeout(() => {
            $.ajax({
                url: "/productos/validar",
                method: "POST",
                data: {
                    _token: $('meta[name="csrf-token"]').attr("content"),
                    nombre: nombre,
                    codigo_barra: codigo_barra,
                    modelo: modelo,
                    marca: marca
                },
                success: function(response) {
                    $("#nombre, #codigo_barra, #modelo, #marca").removeClass("is-invalid is-valid");
                    $(".error-message").text("");

                    let codigoValido = true;
                    let productoValido = true;

                    if (codigo_barra !== "") {
                        if (response.codigoExiste) {
                            $("#codigo_barra").addClass("is-invalid");
                            $("#codigo-error").text("✖ El código ya está registrado");
                            codigoValido = false;
                        } else {
                            $("#codigo_barra").addClass("is-valid");
                            $("#codigo-error").text("✓ Código disponible");
                        }
                    }

                    if (nombre !== "" && modelo !== "" && marca !== "") {
                        if (response.productoExiste) {
                            $("#nombre").addClass("is-invalid");
                            $("#modelo").addClass("is-invalid");
                            $("#marca").addClass("is-invalid");
                            $("#producto-error").text("✖ Producto ya registrado (nombre + modelo + marca + codigo de barra)");
                            productoValido = false;
                        } else {
                            $("#nombre").addClass("is-valid");
                            $("#modelo").addClass("is-valid");
                            $("#marca").addClass("is-valid");
                            $("#producto-error").text("");
                        }
                    }

                    guardarProductoBtn.disabled = !(codigoValido && productoValido);
                },
                error: function(xhr, status, error) {
                    console.error("Error en la validación:", error);
                    $("#codigo-error").text("Error al validar. Intente nuevamente.");
                }
            });
        }, 500);
    });
    // Manejo del envío del formulario con SweetAlert2
    $("#formRegistrarProducto").on("submit", function(event) {
        event.preventDefault();
        
        if (guardarProductoBtn.disabled) {
            Swal.fire({
                title: 'Datos inválidos',
                html: '<div class="text-left">' +
                      '<p>No se puede guardar el producto porque:</p>' +
                      '<ul>' +
                      ($("#codigo_barra").hasClass("is-invalid") ? '<li>El código de barras ya existe</li>' : '') +
                      '</ul>' +
                      '</div>',
                icon: 'error',
                confirmButtonText: 'Entendido'
            });
            return;
        }

        Swal.fire({
            title: 'Confirmar Registro',
            html: '<div class="text-left">' +
                  '<p>¿Estás seguro de registrar este producto?</p>' +
                  '<p><strong>Nombre:</strong> ' + $("#nombre").val() + '</p>' +
                  ($("#codigo_barra").val() ? '<p><strong>Código Barras:</strong> ' + $("#codigo_barra").val() + '</p>' : '') +
                  '<p><strong>Precio:</strong> $' + $("#precio").val() + '</p>' +
                  '<p><strong>Cantidad:</strong> ' + $("#cantidad").val() + '</p>' +
                  '</div>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, registrar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loader durante el envío
                Swal.fire({
                    title: 'Registrando Producto',
                    html: 'Por favor espere mientras guardamos la información...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                        // Enviar el formulario de manera asíncrona
                        $.ajax({
                            url: $(this).attr('action'),
                            method: 'POST',
                            data: $(this).serialize(),
                            success: function(response) {
                                Swal.fire({
                                    title: '¡Éxito!',
                                    text: 'Producto registrado correctamente',
                                    icon: 'success',
                                    showConfirmButton: false, // Ocultar botón
                                    timer: 3000, // Cerrar después de 3 segundos
                                    timerProgressBar: true // Mostrar barra de progreso
                                }).then(() => {
                                    $(modalRegistro).modal('hide');
                                    location.reload(); // Recargar para ver los cambios
                                });
                            },
                            error: function(xhr) {
                                let errorMsg = 'Ocurrió un error al registrar el producto';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMsg = xhr.responseJSON.message;
                                }
                                Swal.fire({
                                    title: 'Error',
                                    text: errorMsg,
                                    icon: 'error',
                                    confirmButtonText: 'Entendido'
                                });
                            }
                        });
                    }
                });
            }
        });
    });

    // Función para limpiar errores
    function limpiarErrores() {
        $("#nombre, #codigo_barra").removeClass("is-invalid is-valid");
        $(".error-message").text("");
    }
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
    setupEdicionProductos();
    setupEliminacionProductos();
    setupBusquedaProductos();
    setupRegistroProductos();
    setupUtilidades();
});