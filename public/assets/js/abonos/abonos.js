// ==============================================
// FUNCIÓN GLOBAL PARA BÚSQUEDA
// ==============================================
function buscarRecibos(page = 1) {
    var searchTerm = $('#searchInput').val();
    var estado = $('#estadoFilter').val();

    $.ajax({
        url: '/buscarAbono?page=' + page,
        type: 'GET',
        data: { 
            search: searchTerm, 
            estado: estado 
        },
        success: function(response) {
            $('#recibosBody').html(response.recibosBodyHtml);
            $('#paginacion').html(response.paginationLinks);
        },
        error: function(jqXHR, textStatus, errorThrown) {
           
            Swal.fire('Error', 'No se pudieron cargar los datos', 'error');
        }
    });
}

$(document).ready(function() {
    // Variables globales
    let productosEditIndex = 0;
    let totalAbonadoActual = 0;
    let productoIndex = 0;
    let clienteSeleccionado = null;
    let nuevoClienteModal = null;
    
    // Configuración inicial
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    
    // Inicializar modales
    if (document.getElementById('nuevoClienteModal')) {
        nuevoClienteModal = new bootstrap.Modal(document.getElementById('nuevoClienteModal'));
    }
    
    // ==============================================
    // BÚSQUEDA DE RECIBOS
    // ==============================================
    
    // Evento para búsqueda
    $('#searchInput, #estadoFilter').on('input change', function() {
        buscarRecibos(1);
    });

    // Capturar clicks en paginación
    $(document).on('click', '#paginacion a', function(e) {
        e.preventDefault();
        var page = $(this).attr('href').split('page=')[1];
        buscarRecibos(page);
    });

    // ==============================================
    // GESTIÓN DE CLIENTES
    // ==============================================
    
    // Mostrar modal para nuevo cliente
    $('#btnNuevoCliente').click(function() {
        $('#nuevoClienteForm')[0].reset();
        if (nuevoClienteModal) {
            nuevoClienteModal.show();
        }
    });

    // Guardar nuevo cliente
    $('#btnGuardarCliente').click(function() {
        const form = $('#nuevoClienteForm');
        const btn = $(this);
        
        // Validación
        if (form.find('[name="nombre"]').val().trim() === '') {
            Swal.fire('Error', 'El nombre del cliente es requerido', 'error');
            return;
        }
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
        
        $.ajax({
            url: '/clientesAbono',
            method: 'POST',
            data: form.serialize(),
            success: function(response) {
                // Cerrar modal
                if (nuevoClienteModal) {
                    nuevoClienteModal.hide();
                }
                
                Swal.fire({
                    title: '¡Éxito!',
                    text: response.message,
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });
                
                // Seleccionar automáticamente el nuevo cliente
                clienteSeleccionado = {
                    id: response.cliente.id,
                    nombre: response.cliente.nombre
                };
                
                $('#cliente_id').val(clienteSeleccionado.id);
                $('#buscarCliente').val(clienteSeleccionado.nombre).prop('readonly', true);
                $('#nombre-cliente').text(clienteSeleccionado.nombre);
                $('#resultadosClientes').addClass('d-none');
                $('#cliente-seleccionado').removeClass('d-none');
                $('#buscarCliente').removeClass('is-invalid');
            },
            error: function(xhr) {
                let message = 'Error al registrar el cliente';
                if (xhr.status === 409 && xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    message = Object.values(xhr.responseJSON.errors).join('<br>');
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                Swal.fire('Error', message, 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('Guardar');
            }
        });
    });

    // Buscar clientes
    $('#buscarCliente').on('input', function() {
        if (!clienteSeleccionado) {
            buscarClientes($(this).val());
        }
    });

    function buscarClientes(termino) {
        if (termino.length < 2) {
            $('#resultadosClientes').addClass('d-none');
            return;
        }

        $.ajax({
            url: '/buscar-clientes',
            method: 'GET',
            data: { termino: termino },
            success: function(response) {
                const lista = $('#listaClientes');
                lista.empty();

                if (response.length > 0) {
                    response.forEach(cliente => {
                        lista.append(`
                            <a href="#" class="list-group-item list-group-item-action cliente-item" 
                               data-id="${cliente.id}" 
                               data-nombre="${cliente.nombre}">
                                ${cliente.nombre}
                            </a>
                        `);
                    });
                    $('#resultadosClientes').removeClass('d-none');
                } else {
                    lista.append('<div class="list-group-item">No se encontraron clientes</div>');
                    $('#resultadosClientes').removeClass('d-none');
                }
            }
        });
    }

    // Seleccionar cliente
    $(document).on('click', '.cliente-item', function(e) {
        e.preventDefault();
        clienteSeleccionado = {
            id: $(this).data('id'),
            nombre: $(this).data('nombre')
        };
        
        $('#cliente_id').val(clienteSeleccionado.id);
        $('#buscarCliente').val(clienteSeleccionado.nombre).prop('readonly', true);
        $('#nombre-cliente').text(clienteSeleccionado.nombre);
        $('#resultadosClientes').addClass('d-none');
        $('#cliente-seleccionado').removeClass('d-none');
        $('#buscarCliente').removeClass('is-invalid');
    });

    // Cambiar cliente seleccionado
    $('#cambiar-cliente').click(function() {
        clienteSeleccionado = null;
        $('#cliente_id').val('');
        $('#buscarCliente').val('').prop('readonly', false).focus();
        $('#cliente-seleccionado').addClass('d-none');
    });

    // ==============================================
    // GESTIÓN DE PRODUCTOS EN NUEVA VENTA
    // ==============================================
    
    // Agregar producto en nueva venta
    $('#btn-add-producto-venta').on('click', function() {
        const index = $('#productos-container-venta .producto-item-venta').length;
        
        const nuevaFila = `
            <div class="row producto-item-venta mb-2 align-items-center" data-index="${index}">
                <input type="hidden"
                       class="id-concepto"
                       name="productos[${index}][id_concepto]">

                <div class="col-md-5">
                    <input type="text"
                           class="form-control producto-nombre"
                           name="productos[${index}][nombre]"
                           placeholder="Nombre del producto"
                           required>
                </div>

                <div class="col-md-2">
                    <input type="number"
                           class="form-control producto-precio"
                           name="productos[${index}][precio]"
                           min="0"
                           step="0.01"
                           required>
                </div>

                <div class="col-md-2">
                    <input type="number"
                           class="form-control cantidad"
                           name="productos[${index}][cantidad]"
                           min="1"
                           value="1"
                           required>
                </div>

                <div class="col-md-2">
                    <input type="text"
                           class="form-control subtotal"
                           readonly>
                </div>

                <div class="col-md-1">
                    <button type="button"
                            class="btn btn-danger btn-remove-producto-venta"
                            ${index === 0 ? 'disabled' : ''}>&times;</button>
                </div>
            </div>
        `;

        $('#productos-container-venta').append(nuevaFila);
        
        // Habilitar botón de eliminar si hay más de un producto
        if ($('.producto-item-venta').length > 1) {
            $('.btn-remove-producto-venta').prop('disabled', false);
        }
    });

    // Eliminar producto en nueva venta
    $(document).on('click', '.btn-remove-producto-venta', function() {
        if ($('.producto-item-venta').length > 1) {
            $(this).closest('.producto-item-venta').remove();
            actualizarIndicesProductosVenta();
            calcularTotalVenta();
        } else {
            Swal.fire('Aviso', 'Debe existir al menos un producto en la venta', 'warning');
        }
    });

    // Actualizar índices de productos en nueva venta
    function actualizarIndicesProductosVenta() {
        $('#productos-container-venta .producto-item-venta').each(function(index) {
            $(this).attr('data-index', index);
            $(this).find('.producto-nombre').attr('name', `productos[${index}][nombre]`);
            $(this).find('.producto-precio').attr('name', `productos[${index}][precio]`);
            $(this).find('.cantidad').attr('name', `productos[${index}][cantidad]`);
            $(this).find('.id-concepto').attr('name', `productos[${index}][id_concepto]`);
        });
    }

    // Calcular subtotal
    $(document).on('input', '.producto-precio, .cantidad', function() {
        const item = $(this).closest('.producto-item-venta');
        calcularSubtotal(item);
        calcularTotalVenta();
    });

    function calcularSubtotal(item) {
        const precio = parseFloat(item.find('.producto-precio').val()) || 0;
        const cantidad = parseInt(item.find('.cantidad').val()) || 1;
        const subtotal = precio * cantidad;
        
        item.find('.subtotal').val('$' + subtotal.toFixed(2));
    }

    function calcularTotalVenta() {
        let total = 0;
        
        $('.producto-item-venta').each(function() {
            const subtotal = $(this).find('.subtotal').val().replace('$', '') || '0';
            total += parseFloat(subtotal);
        });
        
        $('#total-venta').text('$' + total.toFixed(2));
    }

    // ==============================================
    // BUSCADOR DE PRODUCTOS PARA NUEVA VENTA
    // ==============================================
    
    $(document).on('input', '.buscar-producto-venta', function() {
        const termino = $(this).val();
        const resultados = $('#nuevaVentaModal .resultados-productos-venta');

        if (termino.length < 2) {
            resultados.addClass('d-none').empty();
            return;
        }
        
        $.ajax({
            url: '/abonos/buscar-productos',
            method: 'GET',
            data: { q: termino },
            success: function(data) {
                resultados.empty();

                if (!Array.isArray(data)) {
                    resultados
                        .removeClass('d-none')
                        .html('<div class="list-group-item text-danger">Error en datos</div>');
                    return;
                }

                if (data.length === 0) {
                    resultados
                        .removeClass('d-none')
                        .html('<div class="list-group-item text-muted">Sin resultados</div>');
                    return;
                }

                data.forEach(producto => {
                    const agotado = producto.agotado === true;
                    resultados.append(`
                        <button type="button"
                            class="list-group-item list-group-item-action seleccionar-producto-venta ${agotado ? 'disabled text-danger' : ''}"
                            ${agotado ? 'disabled' : ''}
                            data-id="${producto.id}"
                            data-nombre="${producto.nombre}"
                            data-marca="${producto.marca || ''}"
                            data-modelo="${producto.modelo || ''}"
                            data-precio="${producto.precio}"
                            data-stock="${producto.cantidad}">
                            
                            <strong>${producto.nombre}</strong><br>
                            <small>
                                ${producto.marca ?? ''} ${producto.modelo ?? ''}<br>
                                ${agotado ? '<span class="fw-bold text-danger">AGOTADO</span>' : `Stock: ${producto.cantidad} | $${producto.precio}`}
                            </small>
                        </button>
                    `);
                });

                resultados.removeClass('d-none');
            },
            error: function() {
                resultados
                    .removeClass('d-none')
                    .html('<div class="list-group-item text-danger">Error al buscar productos</div>');
            }
        });
    });

    // Seleccionar producto en nueva venta
    $(document).on('click', '#nuevaVentaModal .seleccionar-producto-venta', function() {
        const productoId = $(this).data('id');
        const productoNombre = $(this).data('nombre');
        const productoMarca = $(this).data('marca') || '';
        const productoModelo = $(this).data('modelo') || '';
        const productoPrecio = $(this).data('precio');
        
        // Construir nombre para mostrar
        let nombreParaMostrar = productoNombre;
        if (productoMarca || productoModelo) {
            nombreParaMostrar += ' - ';
            if (productoMarca) nombreParaMostrar += productoMarca;
            if (productoModelo) {
                if (productoMarca) {
                    nombreParaMostrar += ' ' + productoModelo;
                } else {
                    nombreParaMostrar += productoModelo;
                }
            }
        }
        
        // Crear nueva fila
        const index = $('#productos-container-venta .producto-item-venta').length;
        const nuevaFila = `
            <div class="row producto-item-venta mb-2 align-items-center" data-index="${index}">
                <input type="hidden"
                       class="id-concepto"
                       name="productos[${index}][id_concepto]"
                       value="${productoId}">

                <div class="col-md-5">
                    <input type="text"
                           class="form-control producto-nombre"
                           name="productos[${index}][nombre]"
                           value="${nombreParaMostrar.trim()}"
                           required
                           readonly>
                </div>

                <div class="col-md-2">
                    <input type="number"
                           class="form-control producto-precio bg-light"
                           name="productos[${index}][precio]"
                           value="${productoPrecio}"
                           min="0"
                           step="0.01"
                           required
                           readonly>
                </div>

                <div class="col-md-2">
                    <input type="number"
                           class="form-control cantidad"
                           name="productos[${index}][cantidad]"
                           min="1"
                           value="1"
                           required>
                </div>

                <div class="col-md-2">
                    <input type="text"
                           class="form-control subtotal"
                           readonly>
                </div>

                <div class="col-md-1">
                    <button type="button"
                            class="btn btn-danger btn-remove-producto-venta"
                            ${index === 0 ? 'disabled' : ''}>&times;</button>
                </div>
            </div>
        `;

        $('#productos-container-venta').append(nuevaFila);
        
        // Calcular subtotal
        const fila = $('#productos-container-venta .producto-item-venta').last();
        calcularSubtotal(fila);
        calcularTotalVenta();
        
        // Habilitar botón de eliminar si hay más de un producto
        if ($('.producto-item-venta').length > 1) {
            $('.btn-remove-producto-venta').prop('disabled', false);
        }

        // Limpiar buscador
        $('#nuevaVentaModal .resultados-productos-venta')
            .empty()
            .addClass('d-none');
        $('#nuevaVentaModal .buscar-producto-venta').val('');
    });

    // ==============================================
    // GUARDAR NUEVA VENTA
    // ==============================================
    
    $('#btnGuardarVenta').click(function() {
        // Validar cliente
        if (!clienteSeleccionado) {
            $('#buscarCliente').addClass('is-invalid').focus();
            Swal.fire('Error', 'Seleccione un cliente', 'error');
            return false;
        }

        // Validar productos
        let productosValidos = true;
        $('.producto-item-venta').each(function() {
            const nombre = $(this).find('.producto-nombre').val().trim();
            const precio = parseFloat($(this).find('.producto-precio').val());
            const cantidad = parseInt($(this).find('.cantidad').val());

            if (!nombre) {
                $(this).find('.producto-nombre').addClass('is-invalid');
                productosValidos = false;
            } else {
                $(this).find('.producto-nombre').removeClass('is-invalid');
            }

            if (!precio || precio <= 0) {
                $(this).find('.producto-precio').addClass('is-invalid');
                productosValidos = false;
            } else {
                $(this).find('.producto-precio').removeClass('is-invalid');
            }

            if (!cantidad || cantidad <= 0) {
                $(this).find('.cantidad').addClass('is-invalid');
                productosValidos = false;
            } else {
                $(this).find('.cantidad').removeClass('is-invalid');
            }
        });

        if (!productosValidos) {
            Swal.fire('Error', 'Complete correctamente todos los productos', 'error');
            return false;
        }

        // Validar método de pago
        const tipoPagoId = $('#tipo_pago_id').val();
        if (!tipoPagoId) {
            $('#tipo_pago_id').addClass('is-invalid').focus();
            Swal.fire('Error', 'Seleccione un método de pago', 'error');
            return false;
        } else {
            $('#tipo_pago_id').removeClass('is-invalid');
        }

        // Preparar datos
        const formData = {
            cliente_id: $('#cliente_id').val(),
            productos: [],
            abono_inicial: parseFloat($('#abono_inicial').val()) || 0,
            tipo_pago_id: tipoPagoId
        };

        $('.producto-item-venta').each(function(index) {
            const producto = {
                nombre: $(this).find('.producto-nombre').val(),
                precio: parseFloat($(this).find('.producto-precio').val()),
                cantidad: parseInt($(this).find('.cantidad').val())
            };

            const idConcepto = $(this).find('.id-concepto').val();
            if (idConcepto) {
                producto.id_concepto = parseInt(idConcepto);
            }

            formData.productos.push(producto);
        });

        // Confirmar
        Swal.fire({
            title: 'Confirmar venta',
            html: `
                <div class="text-left">
                    <p><strong>Cliente:</strong> ${clienteSeleccionado.nombre}</p>
                    <p><strong>Total:</strong> $${$('#total-venta').text().replace('$', '')}</p>
                    <p><strong>Abono inicial:</strong> $${formData.abono_inicial.toFixed(2)}</p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Guardar',
            cancelButtonText: 'Cancelar'
        }).then(result => {
            if (result.isConfirmed) {
                const btn = $('#btnGuardarVenta');
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

                $.ajax({
                    url: '/ventas-abonos',
                    method: 'POST',
                    data: JSON.stringify(formData),
                    contentType: 'application/json; charset=utf-8',
                    dataType: 'json',
                    success: function(response) {
                        Swal.fire({
                            title: '¡Éxito!',
                            text: response.message,
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        let message = 'Error al guardar la venta';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            message = Object.values(xhr.responseJSON.errors).join('<br>');
                        }
                        Swal.fire('Error', message, 'error');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html('Guardar');
                    }
                });
            }
        });
    });

    // ==============================================
    // GESTIÓN DE ABONOS
    // ==============================================
    
    // Mostrar modal para registrar abono
    $(document).on('click', '.btn-abonar', function() {
        const ventaId = $(this).data('venta-id');
        const cliente = $(this).data('cliente');
        const productos = $(this).data('productos');
        const saldo = parseFloat($(this).data('saldo'));
        
        $('#venta_id_abono').val(ventaId);
        $('#abono_cliente').val(cliente);
        $('#abono_productos').val(productos);
        $('#abono_saldo').val('$' + saldo.toFixed(2));
        $('#abono_monto').attr('max', saldo).val('');
        
        const abonoModal = new bootstrap.Modal(document.getElementById('abonoModal'));
        abonoModal.show();
    });

    // Registrar abono
    $('#btnRegistrarAbono').click(function() {
        const form = $('#abonoForm');
        const formData = form.serialize();
        const btn = $(this);

        const monto = parseFloat($('#abono_monto').val());
        const saldo = parseFloat($('#abono_saldo').val().replace('$', ''));

        // Validaciones
        if ($('select[name="tipo_pago_id1"]').val() === '') {
            Swal.fire('Error', 'Seleccione un método de pago', 'error');
            $('select[name="tipo_pago_id1"]').addClass('is-invalid');
            return;
        }

        if (!monto || monto <= 0) {
            Swal.fire('Error', 'Ingrese un monto válido', 'error');
            return;
        }

        if (monto > saldo) {
            Swal.fire('Error', 'El monto no puede ser mayor al saldo restante', 'error');
            return;
        }

        Swal.fire({
            title: 'Confirmar Abono',
            html: `
                <div class="text-left">
                    <p><strong>Monto:</strong> $${monto.toFixed(2)}</p>
                    <p><strong>Saldo restante:</strong> $${saldo.toFixed(2)}</p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Registrar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

                $.ajax({
                    url: '/abonos',
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        Swal.fire({
                            title: '¡Éxito!',
                            text: response.message || 'Abono registrado correctamente',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            $('#abonoModal').modal('hide');
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        let message = 'Error al registrar el abono';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            message = Object.values(xhr.responseJSON.errors).join('<br>');
                            if (xhr.responseJSON.errors.tipo_pago_id1) {
                                $('select[name="tipo_pago_id1"]').addClass('is-invalid');
                            }
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', message, 'error');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html('Registrar');
                    }
                });
            }
        });
    });

    // ==============================================
    // HISTORIAL DE ABONOS
    // ==============================================
    
    $(document).on('click', '.btn-historial', function() {
        const ventaId = $(this).data('venta-id');
        const row = $(this).closest('tr');
        const modal = new bootstrap.Modal(document.getElementById('historialModal'));
        
        $('#historialCliente').text('Cliente: ' + row.find('td:eq(1)').text());
        $('#historialTotal').text(row.find('td:eq(4)').text());
        
        const saldoActual = row.find('td:eq(5) span').text();
        $('#historialSaldo').html(saldoActual);
        
        $('#historialBody').html('<tr><td colspan="4" class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>');
        
        $.get('/abonos/' + ventaId)
            .done(function(response) {
                let html = '';
                if (response.length > 0) {
                    response.forEach((abono, index) => {
                        const fecha = new Date(abono.fecha_abono);
                        html += `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${fecha.toLocaleDateString()} ${fecha.toLocaleTimeString()}</td>
                                <td class="font-weight-bold">$${parseFloat(abono.monto).toFixed(2)}</td>
                                <td>${abono.tipo_pago ? abono.tipo_pago.tipoPago : 'No especificado'}</td>
                                <td>
                                    ${abono.tiene_pdf ? 
                                        `<a href="/abonos/pdf/${abono.id}" target="_blank" class="btn btn-sm btn-info">
                                            <i class="fas fa-file-pdf"></i> Ver PDF
                                         </a>` : 
                                        '<span class="text-muted">No disponible</span>'
                                    }
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-warning btn-reimprimir" 
                                            data-abono-id="${abono.id}"  
                                            title="Reimprimir Ticket">
                                        <i class="fas fa-print"></i> Reimprimir
                                    </button>
                                    ${window.isAdmin ? `
                                    <button class="btn btn-sm btn-danger btn-eliminar-abono" data-abono-id="${abono.id}">
                                        <i class="fas fa-trash"></i>
                                    </button>` : ''}
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    html = '<tr><td colspan="6" class="text-center">No hay abonos registrados</td></tr>';
                }
                
                $('#historialBody').html(html);
            })
            .fail(function(xhr) {
                $('#historialBody').html('<tr><td colspan="6" class="text-center text-danger">Error al cargar el historial</td></tr>');
            });
        
        modal.show();
    });

    // Eliminar abono
    $(document).on('click', '.btn-eliminar-abono', function() {
        const abonoId = $(this).data('abono-id');
        
        Swal.fire({
            title: '¿Eliminar este abono?',
            text: "Esta acción no se puede deshacer",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/abonos/' + abonoId,
                    method: 'DELETE',
                    success: function(response) {
                        Swal.fire({
                            title: '¡Eliminado!',
                            text: response.message || 'El abono ha sido eliminado',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            $('#historialModal').modal('hide');
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        let message = 'No se pudo eliminar el abono';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', message, 'error');
                    }
                });
            }
        });
    });

    // Reimprimir ticket
    $(document).on('click', '.btn-reimprimir', function() {
        const abonoId = $(this).data('abono-id');
        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        
        Swal.fire({
            title: 'Reimprimir ticket',
            text: '¿Estás seguro de que deseas reimprimir el ticket de abono?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, reimprimir',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Reimprimiendo',
                    text: 'Por favor espere...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: '/abonos/reimprimir/' + abonoId,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Éxito',
                            text: response.message || 'Ticket reimpreso correctamente'
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'Error al reimprimir el ticket'
                        });
                    }
                });
            }
        });
    });

    // ==============================================
    // EDICIÓN DE TOTAL
    // ==============================================
    
    $(document).on('click', '.btn-editar-total', function() {
        const ventaId = $(this).data('venta-id');
        const totalActual = $(this).data('total-actual');
        const saldoRestante = $(this).data('saldo-restante');
        const cliente = $(this).data('cliente');
        
        $('#editar_total_venta_id').val(ventaId);
        $('#editar_total_cliente').val(cliente);
        $('#editar_total_actual').val('$' + parseFloat(totalActual).toFixed(2));
        $('#editar_total_saldo_actual').val('$' + parseFloat(saldoRestante).toFixed(2));
        $('#editar_total_nuevo').val(totalActual);
        $('#editar_total_saldo_restante').val(saldoRestante);
        
        calcularNuevoSaldo();
        
        const modal = new bootstrap.Modal(document.getElementById('editarTotalModal'));
        modal.show();
    });

    // Calcular nuevo saldo
    $('#editar_total_nuevo').on('input', calcularNuevoSaldo);

    function calcularNuevoSaldo() {
        const nuevoTotal = parseFloat($('#editar_total_nuevo').val()) || 0;
        const saldoRestanteActual = parseFloat($('#editar_total_saldo_restante').val()) || 0;
        const totalActual = parseFloat($('#editar_total_actual').val().replace('$', '')) || 0;
        
        const diferencia = totalActual - nuevoTotal;
        let nuevoSaldo = saldoRestanteActual - diferencia;
        nuevoSaldo = Math.max(0, nuevoSaldo);
        
        $('#editar_total_nuevo_saldo').val('$' + nuevoSaldo.toFixed(2));
    }

    // Actualizar total
    $('#btnActualizarTotal').click(function() {
        const ventaId = $('#editar_total_venta_id').val();
        const nuevoTotal = $('#editar_total_nuevo').val();
        const nuevoSaldoCalculado = $('#editar_total_nuevo_saldo').val().replace('$', '');
        const cliente = $('#editar_total_cliente').val();

        if (nuevoTotal === '' || isNaN(parseFloat(nuevoTotal)) || parseFloat(nuevoTotal) < 0) {
            Swal.fire('Error', 'Ingrese un valor válido para el total', 'error');
            return;
        }

        Swal.fire({
            title: 'Confirmar cambios',
            html: `
                <div class="text-left">
                    <p><strong>Cliente:</strong> ${cliente}</p>
                    <p><strong>Nuevo total:</strong> $${parseFloat(nuevoTotal).toFixed(2)}</p>
                    <p><strong>Nuevo saldo:</strong> $${parseFloat(nuevoSaldoCalculado).toFixed(2)}</p>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Actualizar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/ventas-abonos/${ventaId}/actualizar-total`,
                    method: 'PUT',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        total: nuevoTotal,
                        saldo_restante: nuevoSaldoCalculado
                    }),
                    success: function(data) {
                        if (data.success) {
                            // Actualizar UI
                            const totalDisplay = $(`#total-display-${ventaId}`);
                            const saldoDisplay = $(`#saldo-display-${ventaId}`);
                            const btnAbonar = $(`.btn-abonar[data-venta-id="${ventaId}"]`);
                            
                            if (totalDisplay.length) {
                                totalDisplay.text('$' + parseFloat(data.nuevo_total).toFixed(2));
                            }
                            
                            if (saldoDisplay.length) {
                                saldoDisplay.text('$' + parseFloat(data.nuevo_saldo).toFixed(2));
                                
                                if (data.nuevo_saldo > 0) {
                                    saldoDisplay.removeClass('bg-success').addClass('bg-warning');
                                } else {
                                    saldoDisplay.removeClass('bg-warning').addClass('bg-success');
                                }
                            }
                            
                            if (btnAbonar.length) {
                                btnAbonar.data('saldo', data.nuevo_saldo);
                                if (data.nuevo_saldo <= 0) {
                                    btnAbonar.hide();
                                }
                            }
                            
                            Swal.fire({
                                title: '¡Éxito!',
                                text: data.message,
                                icon: 'success',
                                timer: 1500
                            }).then(() => {
                                $('#editarTotalModal').modal('hide');
                            });
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error', 'Error al actualizar el total', 'error');
                    }
                });
            }
        });
    });

    // ==============================================
    // ELIMINAR VENTA
    // ==============================================
    
    $(document).on('click', '.btn-eliminar-venta', function() {
        const ventaId = $(this).data('venta-id');
        const cliente = $(this).data('cliente');
        const total = $(this).data('total');
        
        Swal.fire({
            title: '¿Eliminar esta venta?',
            html: `
                <div class="text-left">
                    <p><strong>Cliente:</strong> ${cliente}</p>
                    <p><strong>Total:</strong> ${total}</p>
                    <p class="text-danger">Esta acción no se puede deshacer</p>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/ventas-abonos/' + ventaId,
                    method: 'DELETE',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: '¡Eliminada!',
                                text: response.message,
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        let message = 'Error al eliminar la venta';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', message, 'error');
                    }
                });
            }
        });
    });

    // ==============================================
    // EDICIÓN DE PRODUCTOS - REFACTORIZADA
    // ==============================================
    
    let ventaEditData = {
        id: null,
        detallesOriginales: [], // Guarda los detalles originales para comparar
        totalAbonado: 0
    };

    $(document).on('click', '.btn-editar-productos', function() {
        const ventaId = $(this).data('venta-id');
        const cliente = $(this).data('cliente');
        
        // Reiniciar variables
        productosEditIndex = 0;
        ventaEditData = {
            id: ventaId,
            detallesOriginales: [],
            totalAbonado: 0
        };
        
        $('#productos-editar-container').empty();
        $('#editar_productos_venta_id').val(ventaId);
        $('#editar_productos_cliente').val(cliente);
        
        // Cargar detalles de la venta
        $.ajax({
            url: '/ventas-abonos/' + ventaId + '/detalles',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    // Guardar total abonado
                    totalAbonadoActual = response.total_abonado;
                    ventaEditData.totalAbonado = response.total_abonado;
                    
                    $('#editar_productos_total_actual').val('$' + response.venta.total.toFixed(2));
                    $('#editar_productos_total_abonado').val('$' + response.total_abonado.toFixed(2));
                    
                    // Guardar detalles originales
                    ventaEditData.detallesOriginales = response.detalles.map(detalle => ({
                        id_concepto: detalle.id_concepto || detalle.concepto_id,
                        nombre: detalle.nombre,
                        precio: detalle.precio,
                        cantidad: detalle.cantidad
                    }));
                    
                    // Agregar productos a la interfaz
                    response.detalles.forEach((producto, index) => {
                        agregarProductoEditar(
                            producto.id_concepto || producto.concepto_id,
                            producto.nombre,
                            producto.precio,
                            producto.cantidad,
                            index
                        );
                    });
                    
                    calcularResumenEdicion();
                    
                    const modal = new bootstrap.Modal(document.getElementById('editarProductosModal'));
                    modal.show();
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function(xhr) {
                Swal.fire('Error', 'Error al cargar los detalles de la venta', 'error');
            }
        });
    });

    // Función para agregar producto en edición
    function agregarProductoEditar(idConcepto, nombre, precio, cantidad, index = null) {
        const actualIndex = index !== null ? index : productosEditIndex;
        
        // Buscar si ya existe un producto con este id_concepto
        const productoExistente = $(`.producto-item-editar .id-concepto-editar[value="${idConcepto}"]`);
        
        if (productoExistente.length > 0 && index === null) {
            // Si ya existe, incrementar cantidad
            const item = productoExistente.closest('.producto-item-editar');
            const cantidadActual = parseInt(item.find('.cantidad-editar').val()) || 0;
            item.find('.cantidad-editar').val(cantidadActual + 1);
            calcularSubtotalEditar(item);
            calcularResumenEdicion();
            return;
        }
        
        const productoHtml = `
            <div class="row producto-item-editar mb-2 align-items-center" data-index="${actualIndex}">
                <input type="hidden" 
                       class="id-concepto-editar" 
                       name="productos[${actualIndex}][id_concepto]" 
                       value="${idConcepto || ''}">
                
                <div class="col-md-5">
                    <input type="text"
                           class="form-control producto-nombre-editar"
                           name="productos[${actualIndex}][nombre]"
                           value="${nombre || ''}"
                           placeholder="Nombre"
                           required>
                </div>

                <div class="col-md-2">
                    <input type="number"
                           class="form-control producto-precio-editar"
                           name="productos[${actualIndex}][precio]"
                           value="${precio || ''}"
                           min="0"
                           step="0.01"
                           required>
                </div>

                <div class="col-md-2">
                    <input type="number"
                           class="form-control cantidad-editar"
                           name="productos[${actualIndex}][cantidad]"
                           value="${cantidad || 1}"
                           min="1"
                           required>
                </div>

                <div class="col-md-2">
                    <input type="text"
                           class="form-control subtotal-editar"
                           value="$${((precio || 0) * (cantidad || 1)).toFixed(2)}"
                           readonly>
                </div>

                <div class="col-md-1">
                    <button type="button"
                            class="btn btn-danger btn-remove-producto-editar"
                            data-concepto-id="${idConcepto}"
                            ${actualIndex === 0 ? 'disabled' : ''}>&times;</button>
                </div>
            </div>
        `;
        
        $('#productos-editar-container').append(productoHtml);
        
        if (index === null) {
            productosEditIndex++;
        }
    }

    // Agregar nuevo producto en edición
    $('#btn-add-producto-editar').click(function() {
        agregarProductoEditar(null, '', '', 1);
    });

    // Eliminar producto en edición
    $(document).on('click', '.btn-remove-producto-editar', function() {
        if ($('.producto-item-editar').length > 1) {
            $(this).closest('.producto-item-editar').remove();
            recalcularIndicesEdicion();
            calcularResumenEdicion();
        }
    });

    // Recalcular índices en edición
    function recalcularIndicesEdicion() {
        productosEditIndex = 0;
        $('.producto-item-editar').each(function(index) {
            $(this).attr('data-index', index);
            $(this).find('.producto-nombre-editar').attr('name', `productos[${index}][nombre]`);
            $(this).find('.producto-precio-editar').attr('name', `productos[${index}][precio]`);
            $(this).find('.cantidad-editar').attr('name', `productos[${index}][cantidad]`);
            $(this).find('.id-concepto-editar').attr('name', `productos[${index}][id_concepto]`);
            $(this).find('.btn-remove-producto-editar').attr('data-index', index);
            productosEditIndex++;
        });
    }

    // Calcular subtotal en edición
    $(document).on('input', '.producto-precio-editar, .cantidad-editar', function() {
        const item = $(this).closest('.producto-item-editar');
        calcularSubtotalEditar(item);
        calcularResumenEdicion();
    });

    function calcularSubtotalEditar(item) {
        const precio = parseFloat(item.find('.producto-precio-editar').val()) || 0;
        const cantidad = parseInt(item.find('.cantidad-editar').val()) || 1;
        const subtotal = precio * cantidad;
        
        item.find('.subtotal-editar').val('$' + subtotal.toFixed(2));
    }

    // Calcular resumen de cambios
    function calcularResumenEdicion() {
        let nuevoTotal = 0;
        
        $('.producto-item-editar').each(function() {
            const subtotalText = $(this).find('.subtotal-editar').val().replace('$', '') || '0';
            nuevoTotal += parseFloat(subtotalText);
        });
        
        const nuevoSaldo = nuevoTotal - ventaEditData.totalAbonado;
        const nuevoEstado = nuevoSaldo <= 0 ? 'Pagado' : 'Pendiente';
        
        $('#resumen-nuevo-total').text('$' + nuevoTotal.toFixed(2));
        $('#resumen-nuevo-saldo').text('$' + Math.max(0, nuevoSaldo).toFixed(2));
        $('#resumen-nuevo-estado').text(nuevoEstado)
            .removeClass('text-success text-danger')
            .addClass(nuevoEstado === 'Pagado' ? 'text-success' : 'text-danger');
    }

    // Buscador de productos para edición
    $(document).on('input', '.buscar-producto-editar', function() {
        const termino = $(this).val();
        const resultados = $('#editarProductosModal .resultados-productos-editar');

        if (termino.length < 2) {
            resultados.addClass('d-none').empty();
            return;
        }
        
        $.ajax({
            url: '/abonos/buscar-productos',
            method: 'GET',
            data: { q: termino },
            success: function(data) {
                resultados.empty();

                if (!Array.isArray(data)) {
                    resultados
                        .removeClass('d-none')
                        .html('<div class="list-group-item text-danger">Error en datos</div>');
                    return;
                }

                if (data.length === 0) {
                    resultados
                        .removeClass('d-none')
                        .html('<div class="list-group-item text-muted">Sin resultados</div>');
                    return;
                }

                // Filtrar productos que ya están en la lista
                const conceptosEnLista = [];
                $('.id-concepto-editar').each(function() {
                    const id = $(this).val();
                    if (id) conceptosEnLista.push(parseInt(id));
                });

                data.forEach(producto => {
                    // Si ya está en la lista, no mostrar
                    if (conceptosEnLista.includes(producto.id)) {
                        return;
                    }
                    
                    const agotado = producto.agotado === true;
                    resultados.append(`
                        <button type="button"
                            class="list-group-item list-group-item-action seleccionar-producto-editar ${agotado ? 'disabled text-danger' : ''}"
                            ${agotado ? 'disabled' : ''}
                            data-id="${producto.id}"
                            data-nombre="${producto.nombre}"
                            data-marca="${producto.marca || ''}"
                            data-modelo="${producto.modelo || ''}"
                            data-precio="${producto.precio}"
                            data-stock="${producto.cantidad}">
                            
                            <strong>${producto.nombre}</strong><br>
                            <small>
                                ${producto.marca ?? ''} ${producto.modelo ?? ''}<br>
                                ${agotado ? '<span class="fw-bold text-danger">AGOTADO</span>' : `Stock: ${producto.cantidad} | $${producto.precio}`}
                            </small>
                        </button>
                    `);
                });

                if ($('#editarProductosModal .resultados-productos-editar .seleccionar-producto-editar').length === 0) {
                    resultados.html('<div class="list-group-item text-muted">Todos los productos ya están en la lista</div>');
                }
                
                resultados.removeClass('d-none');
            },
            error: function() {
                resultados
                    .removeClass('d-none')
                    .html('<div class="list-group-item text-danger">Error al buscar productos</div>');
            }
        });
    });

    // Seleccionar producto en edición
    $(document).on('click', '.seleccionar-producto-editar', function() {
        const productoId = $(this).data('id');
        const productoNombre = $(this).data('nombre');
        const productoMarca = $(this).data('marca') || '';
        const productoModelo = $(this).data('modelo') || '';
        const productoPrecio = $(this).data('precio');
        
        // Construir nombre para mostrar
        let nombreParaMostrar = productoNombre;
        if (productoMarca || productoModelo) {
            nombreParaMostrar += ' - ';
            if (productoMarca) nombreParaMostrar += productoMarca;
            if (productoModelo) {
                if (productoMarca) {
                    nombreParaMostrar += ' ' + productoModelo;
                } else {
                    nombreParaMostrar += productoModelo;
                }
            }
        }
        
        // Agregar producto con ID del concepto
        agregarProductoEditar(
            productoId,
            nombreParaMostrar.trim(),
            productoPrecio,
            1
        );
        
        // Limpiar buscador
        $('#editarProductosModal .resultados-productos-editar')
            .empty()
            .addClass('d-none');
        $('#editarProductosModal .buscar-producto-editar').val('');
    });

    // Actualizar productos (versión mejorada)
    $('#btnActualizarProductos').click(function() {
        const ventaId = $('#editar_productos_venta_id').val();
        const cliente = $('#editar_productos_cliente').val();
        
        // Validar productos
        let productosValidos = true;
        let productosData = [];
        
        $('.producto-item-editar').each(function() {
            const idConcepto = $(this).find('.id-concepto-editar').val();
            const nombre = $(this).find('.producto-nombre-editar').val().trim();
            const precio = $(this).find('.producto-precio-editar').val();
            const cantidad = $(this).find('.cantidad-editar').val();
            
            // Validaciones
            if (nombre === '') {
                $(this).find('.producto-nombre-editar').addClass('is-invalid');
                productosValidos = false;
            } else {
                $(this).find('.producto-nombre-editar').removeClass('is-invalid');
            }

            if (precio === '' || parseFloat(precio) <= 0) {
                $(this).find('.producto-precio-editar').addClass('is-invalid');
                productosValidos = false;
            } else {
                $(this).find('.producto-precio-editar').removeClass('is-invalid');
            }

            if (cantidad === '' || parseInt(cantidad) <= 0) {
                $(this).find('.cantidad-editar').addClass('is-invalid');
                productosValidos = false;
            } else {
                $(this).find('.cantidad-editar').removeClass('is-invalid');
            }
            
            if (productosValidos) {
                productosData.push({
                    id_concepto: idConcepto || null,
                    nombre: nombre,
                    precio: parseFloat(precio),
                    cantidad: parseInt(cantidad)
                });
            }
        });

        if (!productosValidos) {
            Swal.fire('Error', 'Complete correctamente todos los campos de productos', 'error');
            return false;
        }

        // Confirmar
        Swal.fire({
            title: 'Confirmar cambios',
            html: `
                <div class="text-left">
                    <p><strong>Cliente:</strong> ${cliente}</p>
                    <p><strong>Nuevo total:</strong> $${$('#resumen-nuevo-total').text().replace('$', '')}</p>
                    <p><strong>Nuevo saldo:</strong> $${$('#resumen-nuevo-saldo').text().replace('$', '')}</p>
                    <p><strong>Nuevo estado:</strong> ${$('#resumen-nuevo-estado').text()}</p>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Actualizar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const btn = $('#btnActualizarProductos');
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Actualizando...');

                $.ajax({
                    url: '/ventas-abonos/' + ventaId + '/productos',
                    method: 'PUT',
                    data: JSON.stringify({ 
                        productos: productosData,
                        detalles_originales: ventaEditData.detallesOriginales
                    }),
                    contentType: 'application/json',
                    success: function(response) {
                        Swal.fire({
                            title: '¡Éxito!',
                            text: response.message,
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            $('#editarProductosModal').modal('hide');
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        let message = 'Error al actualizar los productos';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            message = Object.values(xhr.responseJSON.errors).join('<br>');
                        }
                        Swal.fire('Error', message, 'error');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html('Actualizar Productos');
                    }
                });
            }
        });
    });
});