document.addEventListener('DOMContentLoaded', function() {
    NotasAbonoApp.init();
});

const NotasAbonoApp = {
    // CONFIGURACIÓN Y CONSTANTES
    config: {
        debounceDelay: 500,
        apiEndpoints: {
            clientes: '/clientesNotaAbono',
            regimenes: '/regimenes',
            notasAbono: '/notas-abono',
            buscarClientes: '/buscarClienteNotaAbono',
            verificarCliente: '/verificar-clienteAbono',
            notasAbonoCliente: '/notas-abonoCliente'
        }
    },

    // INICIALIZACIÓN PRINCIPAL
    init: function() {
        this.initComponents();
        this.initEventListeners();
        this.initNotasAbono();
      
    },

    initComponents: function() {
        this.initTooltips();
        this.initSelectBusqueda();
    },

    initEventListeners: function() {
        this.initFormListeners();
        this.initBusquedaListeners();
        this.initFiltrosListeners();
    },

    // MÓDULO DE COMPONENTES UI
    initTooltips: function() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    },

    initSelectBusqueda: function() {
        const regimenSelect = document.querySelector('select[name="regimen_id"]');
        if (!regimenSelect) return;

        regimenSelect.addEventListener('focus', function() {
            this.size = 5;
        });
        
        regimenSelect.addEventListener('blur', function() {
            this.size = 1;
            this.style.width = 'auto';
        });
        
        regimenSelect.addEventListener('change', function() {
            this.size = 1;
            this.style.width = 'auto';
            this.blur();
        });
    },

    // MÓDULO DE FORMULARIOS
    initFormListeners: function() {
        this.configurarFormularioCliente();
        this.configurarFormularioRegimen();
        this.configurarFormularioEdicion();
        this.configurarValidacionNombreCliente();
    },

    configurarValidacionNombreCliente: function() {
        const nombreInput = document.getElementById('nombreCliente');
        const nombreError = document.getElementById('nombreError');
        
        if (!nombreInput || !nombreError) return;

        let checkTimeout = null;

        const validarNombre = () => {
            const nombre = nombreInput.value.trim();
            if (nombre.length > 2) {
                this.verificarNombreExistente(nombre);
            } else {
                this.ocultarErrorNombre();
            }
        };

        nombreInput.addEventListener('input', () => {
            clearTimeout(checkTimeout);
            checkTimeout = setTimeout(validarNombre, this.config.debounceDelay);
        });

        nombreInput.addEventListener('blur', () => {
            const nombre = nombreInput.value.trim();
            if (nombre.length > 2) {
                this.verificarNombreExistente(nombre);
            }
        });
    },

    verificarNombreExistente: async function(nombre) {
        try {
            const response = await fetch(`${this.config.apiEndpoints.verificarCliente}?nombre=${encodeURIComponent(nombre)}`);
            const data = await response.json();
            
            if (data.existe) {
                this.mostrarErrorNombre('Ya existe un cliente registrado con este nombre');
            } else {
                this.ocultarErrorNombre();
            }
        } catch (error) {
            console.error('Error al verificar nombre:', error);
        }
    },

    mostrarErrorNombre: function(mensaje) {
        const nombreError = document.getElementById('nombreError');
        const nombreInput = document.getElementById('nombreCliente');
        
        if (nombreError && nombreInput) {
            nombreError.textContent = mensaje;
            nombreError.style.display = 'block';
            nombreInput.classList.add('is-invalid');
        }
    },

    ocultarErrorNombre: function() {
        const nombreError = document.getElementById('nombreError');
        const nombreInput = document.getElementById('nombreCliente');
        
        if (nombreError && nombreInput) {
            nombreError.style.display = 'none';
            nombreInput.classList.remove('is-invalid');
        }
    },

    configurarFormularioCliente: function() {
        const formCliente = document.getElementById('formCliente');
        if (!formCliente) return;

        formCliente.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.manejarEnvioFormularioCliente(e);
        });
    },

    configurarFormularioRegimen: function() {
        const formRegimen = document.getElementById('formRegimen');
        if (!formRegimen) return;

        formRegimen.addEventListener('submit', (e) => {
            e.preventDefault();
            this.enviarFormulario(
                formRegimen, 
                this.config.apiEndpoints.regimenes, 
                'Régimen', 
                formRegimen.querySelector('button[type="submit"]')
            );
        });
    },

    configurarFormularioEdicion: function() {
        const formEditar = document.getElementById('formEditarCliente');
        if (!formEditar) return;

        formEditar.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.actualizarCliente(e);
        });
    },

    // MÓDULO DE BÚSQUEDA
    initBusquedaListeners: function() {
        const searchInput = document.getElementById('busquedaClientes');
        const btnLimpiar = document.getElementById('btnLimpiarBusqueda');
        
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce(() => {
                this.buscarClientes();
            }, this.config.debounceDelay));
        }

        if (btnLimpiar) {
            btnLimpiar.addEventListener('click', () => {
                if (searchInput) {
                    searchInput.value = '';
                    this.buscarClientes();
                    searchInput.focus();
                }
            });
        }
    },

    buscarClientes: function(page = 1) {
        const searchInput = document.getElementById('busquedaClientes');
        if (!searchInput) return;

        const searchTerm = searchInput.value;
        
        $('#recibosBody').html(`
            <tr>
                <td colspan="7" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Buscando...</span>
                    </div>
                    <p class="mt-2">Buscando clientes...</p>
                </td>
            </tr>
        `);

        $.ajax({
            url: `${this.config.apiEndpoints.buscarClientes}?page=${page}`,
            type: 'GET',
            data: { search: searchTerm },
            success: function(response) {
                $('#recibosBody').html(response.recibosBodyHtml);
                $('#paginacion').html(response.paginationLinks);
            },
            error: function(jqXHR, textStatus, errorThrown) {
             
                $('#recibosBody').html(`
                    <tr>
                        <td colspan="7" class="text-center py-4 text-danger">
                            <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                            <p>Error al buscar clientes. Intenta nuevamente.</p>
                        </td>
                    </tr>
                `);
            }
        });
    },

    // MÓDULO DE NOTAS DE ABONO
    initNotasAbono: function() {
        this.initModalCrearAbono();
        this.initValidacionMontoAbono();
        this.initFormularioEditarAbono();
        this.initOperacionesAjuste();
        this.initModalHistorialListeners(); // Nueva función
    },

    initModalCrearAbono: function() {
        const modalCrearAbono = document.getElementById('modalCrearAbono');
        if (modalCrearAbono) {
            modalCrearAbono.addEventListener('shown.bs.modal', () => {
                const montoInput = document.getElementById('monto_abono');
                if (montoInput) montoInput.focus();
            });
        }
    },

    initValidacionMontoAbono: function() {
        const montoAbonoInput = document.getElementById('monto_abono');
        if (!montoAbonoInput) return;

        montoAbonoInput.addEventListener('input', (e) => {
            const value = parseFloat(e.target.value) || 0;
            this.validarMontoAbono(value, e.target);
        });
    },
    //
    initModalHistorialListeners: function() {
        const modalHistorial = document.getElementById('modalHistorialAjustes');
        if (modalHistorial) {
            modalHistorial.addEventListener('hidden.bs.modal', () => {
                // Limpiar todo cuando se cierra el modal
                this.limpiarPaginacionMovimientos();
                
                // También limpiar el dataset
                modalHistorial.dataset.notaId = '';
            });
        }
    },
    //
    validarMontoAbono: function(value, inputElement) {
        let feedback = document.getElementById('montoFeedback');
        
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.id = 'montoFeedback';
            feedback.className = 'form-text';
            inputElement.parentNode.appendChild(feedback);
        }
        
        if (value < 0) {
            inputElement.setCustomValidity('El monto no puede ser negativo');
            feedback.textContent = '❌ El monto no puede ser negativo';
            feedback.className = 'form-text text-danger';
        } else if (value === 0) {
            inputElement.setCustomValidity('El monto debe ser mayor a cero');
            feedback.textContent = '❌ El monto debe ser mayor a cero';
            feedback.className = 'form-text text-danger';
        } else {
            inputElement.setCustomValidity('');
            feedback.textContent = '✅ Monto válido';
            feedback.className = 'form-text text-success';
        }
    },

    initFormularioEditarAbono: function() {
        const formEditarAbono = document.getElementById('formEditarAbono');
        if (formEditarAbono) {
            formEditarAbono.addEventListener('submit', (e) => {
                e.preventDefault();
                this.actualizarNotaAbono();
            });
        }
    },

    initOperacionesAjuste: function() {
        document.querySelectorAll('input[name="tipo_operacion"]').forEach(radio => {
            radio.addEventListener('change', () => {
                this.toggleCamposOperacion();
                this.actualizarResumenAjuste();
            });
        });
        
        const montoAjusteInput = document.getElementById('monto_ajuste');
        if (montoAjusteInput) {
            montoAjusteInput.addEventListener('input', () => {
                this.actualizarResumenAjuste();
            });
        }
    },

    toggleCamposOperacion: function() {
        const tipo = document.querySelector('input[name="tipo_operacion"]:checked').value;
        const campoAjuste = document.getElementById('campo_ajuste_monto');
        const campoEditar = document.getElementById('campo_editar_info');
        
        if (tipo === 'editar') {
            if (campoAjuste) campoAjuste.style.display = 'none';
            if (campoEditar) campoEditar.style.display = 'block';
        } else {
            if (campoAjuste) campoAjuste.style.display = 'block';
            if (campoEditar) campoEditar.style.display = 'none';
        }
    },

    // MÓDULO DE FILTROS
  initFiltrosListeners: function() {
    // Event listeners para filtros de NOTAS
    const inputsFiltroNotas = ['fecha_desde', 'fecha_hasta', 'filtro_estado'];
    
    inputsFiltroNotas.forEach(id => {
        const input = document.getElementById(id);
        if (input) {
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.aplicarFiltrosNotas();
                }
            });
        }
    });

    // Event listeners para filtros de MOVIMIENTOS (historial)
    const filtroTipo = document.getElementById('filtro_tipo');
    const filtroFechaDesdeMov = document.getElementById('filtro_fecha_desde');
    const filtroFechaHastaMov = document.getElementById('filtro_fecha_hasta');

    if (filtroTipo) {
        filtroTipo.addEventListener('change', this.debounce(() => {
            this.aplicarFiltrosMovimientos(1); // Siempre empezar en página 1
        }, 500));
    }
    if (filtroFechaDesdeMov) {
        filtroFechaDesdeMov.addEventListener('change', this.debounce(() => {
            this.aplicarFiltrosMovimientos(1);
        }, 500));
    }
    if (filtroFechaHastaMov) {
        filtroFechaHastaMov.addEventListener('change', this.debounce(() => {
            this.aplicarFiltrosMovimientos(1);
        }, 500));
    }
},

    // FUNCIONES PRINCIPALES DE CLIENTES
    async manejarEnvioFormularioCliente(e) {
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        const nombre = document.getElementById('nombreCliente')?.value.trim();
        
        // Validación final de nombre
        if (nombre) {
            try {
                const response = await fetch(`${this.config.apiEndpoints.verificarCliente}?nombre=${encodeURIComponent(nombre)}`);
                const data = await response.json();
                
                if (data.existe) {
                    this.mostrarErrorFinal('Ya existe un cliente registrado con este nombre. Por favor, elija otro.');
                    return;
                }
            } catch (error) {
                console.error('Error en verificación final:', error);
            }
        }
        
        await this.enviarFormulario(form, this.config.apiEndpoints.clientes, 'Cliente', submitBtn, originalText);
    },

    mostrarErrorFinal: function(mensaje) {
        this.mostrarErrorNombre(mensaje);
        
        Swal.fire({
            icon: 'error',
            title: 'Error de validación',
            text: mensaje,
            confirmButtonColor: '#4361ee',
            background: '#f8f9fa'
        });
    },

    async enviarFormulario(formulario, url, tipo, submitBtn, originalText = null) {
        if (!originalText) originalText = submitBtn.innerHTML;
        
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';
        submitBtn.disabled = true;
        
        try {
            const formData = new FormData(formulario);
            
            const response = await fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status} ${response.statusText}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                this.manejarExito(formulario, result, tipo);
            } else {
                throw new Error(result.message || `Error desconocido al registrar el ${tipo}`);
            }
            
        } catch (error) {
            this.manejarError(error, tipo);
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    },

   manejarExito: function(formulario, result, tipo) {
        Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: result.message || `${tipo} registrado correctamente`,
            showConfirmButton: false,
            timer: 2000,
            background: '#f8f9fa',
            iconColor: '#38b000'
        });
        
        const modalId = tipo === 'Cliente' ? 'modalCliente' : 'modalRegimen';
        const modalElement = document.getElementById(modalId);
        
        if (modalElement) {
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) modal.hide();
        }
        
        formulario.reset();
        
        // ✅ NUEVO: Actualizar la tabla si es un cliente
        if (tipo === 'Cliente' && result.html) {
            this.agregarClienteATabla(result.html);
        }
    },
    manejarError: function(error, tipo) {
        console.error(`Error al registrar ${tipo}:`, error);
        
        Swal.fire({
            icon: 'error',
            title: 'Error ' + (error.message.includes('404') ? '404' : ''),
            text: error.message,
            confirmButtonColor: '#4361ee',
            background: '#f8f9fa'
        });
    },
//
agregarClienteATabla: function(html) {
    const tbody = document.getElementById('recibosBody');
    if (!tbody) return;
    
    // Agregar el nuevo cliente al inicio de la tabla
    tbody.insertAdjacentHTML('afterbegin', html);
    
    // Actualizar contador
    this.actualizarContadorClientes('increment');
    
    // Re-inicializar tooltips para los nuevos elementos
    this.initTooltips();
},

// ✅ NUEVA FUNCIÓN: Actualizar contador
actualizarContadorClientes: function(accion) {
    const contadorElement = document.querySelector('.fw-bold.text-primary');
    if (!contadorElement) return;
    
    let total = parseInt(contadorElement.textContent) || 0;
    
    if (accion === 'increment') {
        total += 1;
    } else if (accion === 'decrement') {
        total = Math.max(0, total - 1);
    }
    
    contadorElement.textContent = total;
},

    // FUNCIONES DE FILTROS DE NOTAS
// MÓDULO DE FILTROS DE NOTAS DE ABONO
aplicarFiltrosNotas: async function(page = 1) {
    try {
     
        
        const fechaDesde = document.getElementById('fecha_desde')?.value;
        const fechaHasta = document.getElementById('fecha_hasta')?.value;
        const estado = document.getElementById('filtro_estado')?.value;

     

        // Validación de fechas
        if (fechaDesde && fechaHasta && new Date(fechaDesde) > new Date(fechaHasta)) {
            Swal.fire({
                icon: 'error',
                title: 'Error en fechas',
                text: 'La fecha "Desde" no puede ser mayor que la fecha "Hasta"',
                confirmButtonColor: '#4361ee'
            });
            return;
        }

        Swal.fire({
            title: 'Filtrando notas...',
            text: 'Por favor espere...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        const params = new URLSearchParams();
        if (fechaDesde) params.append('fecha_desde', fechaDesde);
        if (fechaHasta) params.append('fecha_hasta', fechaHasta);
        if (estado) params.append('estado', estado);
        params.append('page', page);

        // Obtener cliente_id DE FORMA CORRECTA
        let clienteId = this.obtenerClienteIdDeVista();
        
        if (clienteId) {
            params.append('cliente_id', clienteId);
        } else {
            console.error('No se pudo obtener el cliente_id');
            Swal.close();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo identificar al cliente',
                confirmButtonColor: '#4361ee'
            });
            return;
        }

     

        const urlBusqueda = `/filtros/notas-abono?${params.toString()}`;
      

        const response = await fetch(urlBusqueda);
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status} ${response.statusText}`);
        }

        const result = await response.json();
    
        
        Swal.close();

        if (result.success) {
            // Reemplazar la vista con los resultados
            this.actualizarVistaFiltrada(result.notas, result.pagination);
            
            Swal.fire({
                icon: 'success',
                title: 'Filtros aplicados',
                text: `Se encontraron ${result.pagination.total} notas`,
                showConfirmButton: false,
                timer: 1500
            });
        } else {
            throw new Error(result.message || 'Error en el filtrado de notas');
        }

    } catch (error) {
        console.error('Error al aplicar filtros de notas:', error);
        Swal.close();
        Swal.fire({
            icon: 'error',
            title: 'Error en filtros',
            text: error.message || 'No se pudieron aplicar los filtros a las notas',
            confirmButtonColor: '#4361ee'
        });
        
        // En caso de error, restaurar vista original
        this.restaurarVistaOriginal();
    }
},
//
obtenerClienteIdDeVista: function() {
    // Opción 1: Desde la URL
    const path = window.location.pathname;
    const match = path.match(/\/abonar\/(\d+)/);
    if (match) return match[1];
    
    // Opción 2: Desde un input hidden
    const clienteIdInput = document.querySelector('input[name="cliente_id"]');
    if (clienteIdInput && clienteIdInput.value) {
        return clienteIdInput.value;
    }
    
    // Opción 3: Desde el contenido de la página
    const clienteBadge = document.querySelector('.badge.bg-light.text-dark');
    if (clienteBadge) {
        const match = clienteBadge.textContent.match(/Cliente #(\d+)/);
        if (match) return match[1];
    }
    
    return null;
},
//
actualizarVistaFiltrada: function(notas, pagination) {
    const contenedor = document.getElementById('contenedor-notas');
    if (!contenedor) return;

    
    if (!notas || notas.length === 0) {
        contenedor.innerHTML = `
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fas fa-file-invoice-dollar fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">No hay notas de abono</h4>
                    <p class="text-muted">No se encontraron notas de abono con los filtros aplicados.</p>
                    <button type="button" class="btn btn-outline-primary" onclick="NotasAbonoApp.limpiarFiltrosNotas()">
                        <i class="fas fa-times me-1"></i> Limpiar Filtros
                    </button>
                </div>
            </div>
        `;
        return;
    }

    // Generar HTML para las notas
    contenedor.innerHTML = notas.map(nota => {
      
        
        // Obtener nombre del cliente CORRECTAMENTE
        let nombreCliente = 'N/A';
        if (nota.cliente && nota.cliente.nombre) {
            nombreCliente = nota.cliente.nombre;
        } else if (nota.cliente_nombre) { // Backup
            nombreCliente = nota.cliente_nombre;
        }
        
        // Formatear fechas de forma segura
        const fechaApertura = nota.fecha_apertura ? new Date(nota.fecha_apertura) : new Date();
        const fechaCreacion = nota.created_at ? new Date(nota.created_at) : new Date();
        const diasTranscurridos = Math.floor((new Date() - fechaApertura) / (1000 * 60 * 60 * 24));

        return `
            <div class="col-md-6 mb-4">
                <div class="card h-100 border-primary shadow-sm ${nota.estado === 'finalizada' ? 'border-secondary' : ''}">
                    <div class="card-header ${nota.estado === 'finalizada' ? 'bg-secondary text-white' : 'bg-primary text-white'} d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">
                                <i class="fas fa-file-invoice-dollar me-2"></i>${nota.folio || 'N/A'}
                            </h6>
                            <small class="opacity-75">
                                ${fechaCreacion.toLocaleDateString('es-MX')} ${fechaCreacion.toLocaleTimeString('es-MX', {hour: '2-digit', minute:'2-digit'})}
                            </small>
                        </div>
                        <span class="badge ${this.getClaseBadgeEstado(nota.estado)}">
                            ${nota.estado ? nota.estado.toUpperCase() : 'N/A'}
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Cliente:</strong><br>
                            <span class="text-dark">${nombreCliente}</span>
                        </div>

                        <div class="row g-3">
                            <div class="col-6">
                                <div class="info-item">
                                    <span class="info-label">Abono Inicial</span>
                                    <span class="info-value text-success fw-bold fs-5">
                                        $${parseFloat(nota.abono_inicial || 0).toLocaleString('es-MX', {minimumFractionDigits: 2})}
                                    </span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="info-item">
                                    <span class="info-label">Saldo Actual</span>
                                    <span class="info-value ${this.getClaseSaldo(nota.estado, nota.saldo_actual)} fw-bold fs-5">
                                        ${nota.estado === 'saldo_deuda' ? '-$' : '$'}${Math.abs(parseFloat(nota.saldo_actual || 0)).toLocaleString('es-MX', {minimumFractionDigits: 2})}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row g-2 mt-2">
                            <div class="col-6">
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    ${fechaApertura.toLocaleDateString('es-MX')}
                                </small>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    ${diasTranscurridos} días
                                </small>
                            </div>
                        </div>
                        
                        ${nota.observaciones ? `
                        <div class="mt-3 p-2 bg-light rounded">
                            <span class="info-label">Observaciones</span>
                            <p class="info-value mb-0 small">${nota.observaciones}</p>
                        </div>
                        ` : ''}
                    </div>
                    <div class="card-footer bg-transparent">
                        <div class="d-grid gap-2">
                            ${this.generarBotonesNota(nota)}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    // Mostrar paginación si es necesario
    this.mostrarPaginacionFiltrada(pagination);
},
//
getClaseBadgeEstado: function(estado) {
    const clases = {
        'activa': 'bg-success',
        'finalizada': 'bg-secondary',
        'saldo_favor': 'bg-success',
        'saldo_deuda': 'bg-danger',
        'cancelada': 'bg-warning text-dark'
    };
    return clases[estado] || 'bg-info';
},

getClaseSaldo: function(estado, saldo) {
    if (estado === 'saldo_deuda') return 'text-danger';
    if (estado === 'saldo_favor') return 'text-success';
    if (estado === 'activa') return 'text-primary';
    return 'text-secondary';
},

generarBotonesNota: function(nota) {
    let html = '';
    
    if (nota.estado === 'activa') {
        html += `
            <div class="btn-group w-100" role="group">
                <a href="/notas-abono/administrar/${nota.id}" 
                   class="btn btn-success btn-sm flex-fill">
                    <i class="fas fa-cog me-1"></i> Administrar
                </a>
                <button type="button" 
                        class="btn btn-accent btn-sm"
                        onclick="NotasAbonoApp.editarNotaAbono(${nota.id})"
                        data-bs-toggle="tooltip"
                        title="Editar información de la nota">
                    <i class="fas fa-edit"></i>
                </button>
            </div>
            
            <button type="button" 
                    class="btn btn-info btn-sm"
                    onclick="NotasAbonoApp.verHistorialAjustes(${nota.id})"
                    data-bs-toggle="tooltip"
                    title="Ver historial de movimientos">
                <i class="fas fa-history me-1"></i> Historial
            </button>
        `;
        
        if (nota.saldo_actual == 0) {
            html += `
                <button type="button" 
                        class="btn btn-warning btn-sm"
                        onclick="NotasAbonoApp.finalizarNotaAbono(${nota.id}, '${nota.folio || ''}')">
                    <i class="fas fa-lock me-1"></i> Finalizar Nota
                </button>
            `;
        } else {
            html += `
                <small class="text-muted text-center">
                    <i class="fas fa-info-circle me-1"></i>Saldo debe ser $0 para finalizar
                </small>
            `;
        }
    } else {
        html += `
            <button type="button" 
                    class="btn btn-secondary btn-sm w-100"
                    disabled>
                <i class="fas fa-lock me-1"></i> ${nota.estado ? nota.estado.toUpperCase() : 'INACTIVA'}
            </button>
            
            <button type="button" 
                    class="btn btn-info btn-sm"
                    onclick="NotasAbonoApp.verHistorialAjustes(${nota.id})">
                <i class="fas fa-history me-1"></i> Ver Historial
            </button>
        `;
    }
    
    return html;
},
// Función para reemplazar completamente la vista
reemplazarVistaCompleta: function(notas, pagination) {
    const contenedor = document.getElementById('contenedor-notas');
    if (!contenedor) return;

    // Limpiar el contenedor completamente
    contenedor.innerHTML = '';
    
    // Agregar las nuevas notas
    this.actualizarVistaNotas(notas);
    
    // Ocultar elementos originales
    this.ocultarElementosOriginales();
    
    // Mostrar paginación AJAX si es necesario
    if (pagination && pagination.last_page > 1) {
        document.getElementById('paginacionNotasAjax').style.display = 'block';
        this.mostrarPaginacionNotasAjax(pagination);
    } else {
        document.getElementById('paginacionNotasAjax').style.display = 'none';
    }
},
//
mostrarPaginacionFiltrada: function(pagination) {
    const container = document.getElementById('paginacionNotasAjax');
    if (!container) return;

    if (pagination.last_page <= 1) {
        container.style.display = 'none';
        return;
    }

    container.style.display = 'block';
    container.innerHTML = `
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted small">
                                Mostrando ${pagination.from} a ${pagination.to} de ${pagination.total} notas
                            </div>
                            <nav aria-label="Paginación de notas filtradas">
                                <ul class="pagination pagination-sm mb-0">
                                    ${this.generarPaginacionHTML(pagination)}
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
},

generarPaginacionHTML: function(pagination) {
    let html = '';
    const currentPage = pagination.current_page;
    const lastPage = pagination.last_page;

    // Botón anterior
    if (currentPage > 1) {
        html += `
            <li class="page-item">
                <button class="page-link" onclick="NotasAbonoApp.aplicarFiltrosNotas(${currentPage - 1})">
                    &laquo; Anterior
                </button>
            </li>
        `;
    } else {
        html += `
            <li class="page-item disabled">
                <span class="page-link">&laquo; Anterior</span>
            </li>
        `;
    }

    // Números de página
    for (let i = 1; i <= lastPage; i++) {
        if (i === currentPage) {
            html += `
                <li class="page-item active">
                    <span class="page-link">${i}</span>
                </li>
            `;
        } else {
            html += `
                <li class="page-item">
                    <button class="page-link" onclick="NotasAbonoApp.aplicarFiltrosNotas(${i})">
                        ${i}
                    </button>
                </li>
            `;
        }
    }

    // Botón siguiente
    if (currentPage < lastPage) {
        html += `
            <li class="page-item">
                <button class="page-link" onclick="NotasAbonoApp.aplicarFiltrosNotas(${currentPage + 1})">
                    Siguiente &raquo;
                </button>
            </li>
        `;
    } else {
        html += `
            <li class="page-item disabled">
                <span class="page-link">Siguiente &raquo;</span>
            </li>
        `;
    }

    return html;
},
//
ocultarElementosOriginales: function() {
    // Ocultar paginación normal
    const paginacionNormal = document.querySelector('#paginacionNotasAjax').previousElementSibling;
    if (paginacionNormal) {
        paginacionNormal.style.display = 'none';
    }
    
    // Ocultar resumen original
    const resumenOriginal = document.querySelector('.card.border-info');
    if (resumenOriginal) {
        resumenOriginal.style.display = 'none';
    }
},
//
mostrarPaginacionNotasAjax: function(pagination) {
    const container = document.getElementById('paginacionNotasAjax');
    if (!container || !pagination) return;

    const currentPage = pagination.current_page;
    const lastPage = pagination.last_page;
    const total = pagination.total;

    // Si solo hay una página, no mostrar paginación
    if (lastPage <= 1) {
        container.innerHTML = '';
        return;
    }

    let paginationHtml = `
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted small">
                                Mostrando ${pagination.from} a ${pagination.to} de ${total} notas
                            </div>
                            <nav aria-label="Paginación de notas">
                                <ul class="pagination pagination-sm mb-0">
    `;

    // Botón anterior
    if (currentPage > 1) {
        paginationHtml += `
            <li class="page-item">
                <button class="page-link" onclick="NotasAbonoApp.aplicarFiltrosNotas(${currentPage - 1})">
                    &laquo; Anterior
                </button>
            </li>
        `;
    } else {
        paginationHtml += `
            <li class="page-item disabled">
                <span class="page-link">&laquo; Anterior</span>
            </li>
        `;
    }

    // Números de página
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(lastPage, currentPage + 2);

    for (let i = startPage; i <= endPage; i++) {
        if (i === currentPage) {
            paginationHtml += `
                <li class="page-item active">
                    <span class="page-link">${i}</span>
                </li>
            `;
        } else {
            paginationHtml += `
                <li class="page-item">
                    <button class="page-link" onclick="NotasAbonoApp.aplicarFiltrosNotas(${i})">
                        ${i}
                    </button>
                </li>
            `;
        }
    }

    // Botón siguiente
    if (currentPage < lastPage) {
        paginationHtml += `
            <li class="page-item">
                <button class="page-link" onclick="NotasAbonoApp.aplicarFiltrosNotas(${currentPage + 1})">
                    Siguiente &raquo;
                </button>
            </li>
        `;
    } else {
        paginationHtml += `
            <li class="page-item disabled">
                <span class="page-link">Siguiente &raquo;</span>
            </li>
        `;
    }

    paginationHtml += `
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    container.innerHTML = paginationHtml;
},

//
limpiarFiltrosNotas: function() {
    document.getElementById('fecha_desde').value = '';
    document.getElementById('fecha_hasta').value = '';
    document.getElementById('filtro_estado').value = '';
    
    // Limpiar el contenedor de notas filtradas
    const contenedor = document.getElementById('contenedor-notas');
    if (contenedor) {
        contenedor.innerHTML = '';
    }
    
    // Ocultar paginación AJAX
    document.getElementById('paginacionNotasAjax').style.display = 'none';
    
    // Mostrar paginación normal
    const paginacionNormal = document.querySelector('#paginacionNotasAjax').previousElementSibling;
    if (paginacionNormal) {
        paginacionNormal.style.display = 'block';
    }
    
    // Mostrar resumen original
    const resumenOriginal = document.querySelector('.card.border-info');
    if (resumenOriginal) {
        resumenOriginal.style.display = 'block';
    }
    
    // Mostrar notas originales
    const notasOriginales = document.querySelectorAll('#contenedor-notas > .col-md-6');
    notasOriginales.forEach(nota => {
        nota.style.display = 'block';
    });
    
    // Recargar la página para un reset completo (opcional)
    // location.reload();
},
// MÓDULO DE FILTROS DE MOVIMIENTOS (HISTORIAL)

// Agrega estas funciones auxiliares
ocultarNotasOriginales: function() {
    // Ocultar las notas originales cargadas por Laravel
    const notasOriginales = document.querySelectorAll('#contenedor-notas > .col-md-6');
    notasOriginales.forEach(nota => {
        nota.style.display = 'none';
    });
    
    // Ocultar la paginación normal
    const paginacionNormal = document.querySelector('#paginacionNotasAjax').previousElementSibling;
    if (paginacionNormal) {
        paginacionNormal.style.display = 'none';
    }
},

ocultarResumenOriginal: function() {
    // Ocultar el resumen original
    const resumenOriginal = document.querySelector('.card.border-info');
    if (resumenOriginal) {
        resumenOriginal.style.display = 'none';
    }
},
// MÓDULO DE PAGINACIÓN DE MOVIMIENTOS
aplicarFiltrosMovimientos: async function(page = 1) {
    try {
        const modal = document.getElementById('modalHistorialAjustes');
        if (!modal) return;

        const notaId = modal.dataset.notaId;
        if (!notaId) {
            console.error('No se encontró el ID de la nota');
            return;
        }

        const tipo = document.getElementById('filtro_tipo')?.value;
        const fechaDesde = document.getElementById('filtro_fecha_desde')?.value;
        const fechaHasta = document.getElementById('filtro_fecha_hasta')?.value;

    

        // Mostrar loading
        const tbody = document.getElementById('cuerpoHistorial');
        const paginationContainer = document.getElementById('paginacionMovimientos');
        
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2">Cargando movimientos...</p>
                    </td>
                </tr>
            `;
        }

        if (paginationContainer) {
            paginationContainer.innerHTML = '';
        }

        // Construir parámetros
        const params = new URLSearchParams();
        if (tipo) params.append('tipo', tipo);
        if (fechaDesde) params.append('fecha_desde', fechaDesde);
        if (fechaHasta) params.append('fecha_hasta', fechaHasta);
        params.append('page', page);

        const url = `/filtros/movimientos/${notaId}?${params.toString()}`;
   

        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }

        const result = await response.json();
      

        if (result.success) {
            this.mostrarMovimientosEnTabla(result.movimientos);
            this.mostrarPaginacionMovimientos(result.pagination, notaId);
            this.actualizarInfoPaginacion(result.pagination); // ← Asegúrate de que esté aquí
            this.calcularResumenMovimientos(result.movimientos);
            
        } else {
            throw new Error(result.message || 'Error al filtrar movimientos');
        }

    } catch (error) {
        console.error('Error al aplicar filtros de movimientos:', error);
        
        const tbody = document.getElementById('cuerpoHistorial');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4 text-danger">
                        <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                        <p>Error al cargar movimientos</p>
                        <small class="text-muted">${error.message}</small>
                    </td>
                </tr>
            `;
        }
        this.actualizarInfoPaginacion(null); // Limpiar info en caso de error
        this.calcularResumenMovimientos([]);
    }
},

mostrarPaginacionMovimientos: function(pagination, notaId) {
    const container = document.getElementById('paginacionMovimientos');
    if (!container || !pagination) return;

    const currentPage = pagination.current_page;
    const lastPage = pagination.last_page;
    const total = pagination.total;

    // Si no hay movimientos, no mostrar paginación
    if (total === 0) {
        container.innerHTML = '';
        return;
    }

    let paginationHtml = `
        <div class="d-flex justify-content-between align-items-center w-100">
            <div class="text-muted small">
                Mostrando ${pagination.from} a ${pagination.to} de ${total} movimientos
            </div>
            <nav aria-label="Paginación de movimientos">
                <ul class="pagination pagination-sm mb-0">
    `;

    // Botón anterior
    if (currentPage > 1) {
        paginationHtml += `
            <li class="page-item">
                <button class="page-link" onclick="NotasAbonoApp.aplicarFiltrosMovimientos(${currentPage - 1})">
                    &laquo; Anterior
                </button>
            </li>
        `;
    } else {
        paginationHtml += `
            <li class="page-item disabled">
                <span class="page-link">&laquo; Anterior</span>
            </li>
        `;
    }

    // Números de página
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(lastPage, currentPage + 2);

    // Mostrar primera página si no está en el rango
    if (startPage > 1) {
        paginationHtml += `
            <li class="page-item">
                <button class="page-link" onclick="NotasAbonoApp.aplicarFiltrosMovimientos(1)">1</button>
            </li>
        `;
        if (startPage > 2) {
            paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        if (i === currentPage) {
            paginationHtml += `
                <li class="page-item active">
                    <span class="page-link">${i}</span>
                </li>
            `;
        } else {
            paginationHtml += `
                <li class="page-item">
                    <button class="page-link" onclick="NotasAbonoApp.aplicarFiltrosMovimientos(${i})">
                        ${i}
                    </button>
                </li>
            `;
        }
    }

    // Mostrar última página si no está en el rango
    if (endPage < lastPage) {
        if (endPage < lastPage - 1) {
            paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
        paginationHtml += `
            <li class="page-item">
                <button class="page-link" onclick="NotasAbonoApp.aplicarFiltrosMovimientos(${lastPage})">
                    ${lastPage}
                </button>
            </li>
        `;
    }

    // Botón siguiente
    if (currentPage < lastPage) {
        paginationHtml += `
            <li class="page-item">
                <button class="page-link" onclick="NotasAbonoApp.aplicarFiltrosMovimientos(${currentPage + 1})">
                    Siguiente &raquo;
                </button>
            </li>
        `;
    } else {
        paginationHtml += `
            <li class="page-item disabled">
                <span class="page-link">Siguiente &raquo;</span>
            </li>
        `;
    }

    paginationHtml += `
                </ul>
            </nav>
        </div>
    `;

    container.innerHTML = paginationHtml;
    
    // Actualizar también la información de paginación
    this.actualizarInfoPaginacion(pagination);
},
//
actualizarInfoPaginacion: function(pagination) {
    const infoContainer = document.getElementById('infoPaginacionMovimientos');
    if (!infoContainer) return;

    if (!pagination || pagination.total === 0) {
        infoContainer.innerHTML = `
            <small class="text-muted">
                No hay movimientos para mostrar
            </small>
        `;
        return;
    }

    infoContainer.innerHTML = `
        <small class="text-muted">
            Página ${pagination.current_page} de ${pagination.last_page} 
            • ${pagination.total} movimiento(s) en total
        </small>
    `;
},
// Agrega esta función dentro del objeto NotasAbonoApp
mostrarToastExito: function(mensaje) {
    // Verificar si Bootstrap toast está disponible
    if (typeof bootstrap === 'undefined' || !bootstrap.Toast) {
     
        return;
    }
    
    // Crear toast dinámicamente
    const toastId = 'toast-' + Date.now();
    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-white bg-success border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-check-circle me-2"></i>${mensaje}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    const toastContainer = document.createElement('div');
    toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
    toastContainer.style.zIndex = '9999';
    toastContainer.innerHTML = toastHtml;
    document.body.appendChild(toastContainer);
    
    const toastElement = document.getElementById(toastId);
    if (toastElement) {
        const toast = new bootstrap.Toast(toastElement, {
            autohide: true,
            delay: 3000
        });
        toast.show();
        
        // Remover del DOM después de que se oculte
        toastElement.addEventListener('hidden.bs.toast', function() {
            toastContainer.remove();
        });
    }
},

limpiarFiltrosMovimientos: function() {
    document.getElementById('filtro_tipo').value = '';
    document.getElementById('filtro_fecha_desde').value = '';
    document.getElementById('filtro_fecha_hasta').value = '';
    
    // Re-aplicar filtros (que ahora estarán vacíos)
    this.aplicarFiltrosMovimientos();
},
//
obtenerClienteId: function() {
    // Intentar obtener de la URL
    const urlPath = window.location.pathname;
    const match = urlPath.match(/\/abonar\/(\d+)/);
    if (match) {
        return match[1];
    }
    
    // Intentar obtener de query parameters
    const urlParams = new URLSearchParams(window.location.search);
    const clienteId = urlParams.get('cliente_id');
    if (clienteId) {
        return clienteId;
    }
    
    // Intentar obtener de un input hidden en el formulario
    const clienteIdInput = document.querySelector('input[name="cliente_id"]');
    if (clienteIdInput && clienteIdInput.value) {
        return clienteIdInput.value;
    }
    
    return null;
},

 actualizarVistaNotas: function(notas) {
    const contenedor = document.getElementById('contenedor-notas');
    if (!contenedor) return;

  

    if (!notas || notas.length === 0) {
        contenedor.innerHTML = `
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fas fa-file-invoice-dollar fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">No hay notas de abono</h4>
                    <p class="text-muted">No se encontraron notas de abono con los filtros aplicados.</p>
                    <button type="button" class="btn btn-outline-primary" onclick="NotasAbonoApp.limpiarFiltrosNotas()">
                        <i class="fas fa-times me-1"></i> Limpiar Filtros
                    </button>
                </div>
            </div>
        `;
        return;
    }

    // Generar HTML para las notas
    contenedor.innerHTML = notas.map(nota => {
        // Verificar la estructura de la nota
           
        // Obtener nombre del cliente de diferentes formas posibles
        let nombreCliente = 'N/A';
        if (nota.cliente_nombre) {
            nombreCliente = nota.cliente_nombre;
        } else if (nota.cliente && nota.cliente.nombre) {
            nombreCliente = nota.cliente.nombre;
        } else if (nota.nombre_cliente) {
            nombreCliente = nota.nombre_cliente;
        }
        
        // Formatear fechas de forma segura
        const fechaCreacion = nota.created_at ? new Date(nota.created_at) : new Date();
        const fechaApertura = nota.fecha_apertura ? new Date(nota.fecha_apertura) : new Date();
        const diasTranscurridos = Math.floor((new Date() - fechaApertura) / (1000 * 60 * 60 * 24));

        return `
            <div class="col-md-6 mb-4">
                <div class="card h-100 border-primary shadow-sm ${nota.estado === 'cerrada' ? 'border-secondary' : ''}">
                    <div class="card-header ${nota.estado === 'cerrada' ? 'bg-secondary text-white' : 'bg-primary text-white'} d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">
                                <i class="fas fa-file-invoice-dollar me-2"></i>${nota.folio || 'N/A'}
                            </h6>
                            <small class="opacity-75">
                                ${fechaCreacion.toLocaleDateString('es-MX')} ${fechaCreacion.toLocaleTimeString('es-MX')}
                            </small>
                        </div>
                        <span class="badge ${nota.estado === 'activa' ? 'bg-success' : 'bg-secondary'}">
                            ${nota.estado ? nota.estado.toUpperCase() : 'N/A'}
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Cliente:</strong><br>
                            <span class="text-dark">${nombreCliente}</span>
                        </div>

                        <div class="row g-3">
                            <div class="col-6">
                                <div class="info-item">
                                    <span class="info-label">Abono Inicial</span>
                                    <span class="info-value text-success fw-bold fs-5">
                                        $${parseFloat(nota.abono_inicial || 0).toLocaleString('es-MX', {minimumFractionDigits: 2})}
                                    </span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="info-item">
                                    <span class="info-label">Saldo Actual</span>
                                    <span class="info-value ${(nota.saldo_actual || 0) > 0 ? 'text-primary' : 'text-warning'} fw-bold fs-5">
                                        $${parseFloat(nota.saldo_actual || 0).toLocaleString('es-MX', {minimumFractionDigits: 2})}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row g-2 mt-2">
                            <div class="col-6">
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    ${fechaApertura.toLocaleDateString('es-MX')}
                                </small>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    ${diasTranscurridos} días
                                </small>
                            </div>
                        </div>
                        
                        ${nota.observaciones ? `
                        <div class="mt-3 p-2 bg-light rounded">
                            <span class="info-label">Observaciones</span>
                            <p class="info-value mb-0 small">${nota.observaciones}</p>
                        </div>
                        ` : ''}
                    </div>
                    <div class="card-footer bg-transparent">
                        <div class="d-grid gap-2">
                            <div class="btn-group w-100" role="group">
                                ${nota.estado === 'activa' ? `
                                <a href="/notas-abono/administrar/${nota.id}" 
                                   class="btn btn-success btn-sm flex-fill">
                                    <i class="fas fa-cog me-1"></i> Administrar
                                </a>
                                <button type="button" 
                                        class="btn btn-accent btn-sm"
                                        onclick="NotasAbonoApp.editarNotaAbono(${nota.id})"
                                        data-bs-toggle="tooltip"
                                        title="Editar información de la nota">
                                    <i class="fas fa-edit"></i>
                                </button>
                                ` : `
                                <button type="button" 
                                        class="btn btn-secondary btn-sm flex-fill"
                                        disabled>
                                    <i class="fas fa-lock me-1"></i> Cerrada
                                </button>
                                `}
                            </div>
                            
                            <button type="button" 
                                    class="btn btn-info btn-sm"
                                    onclick="NotasAbonoApp.verHistorialAjustes(${nota.id})"
                                    data-bs-toggle="tooltip"
                                    title="Ver historial de movimientos">
                                <i class="fas fa-history me-1"></i> Historial
                            </button>

                            ${nota.estado === 'activa' && (nota.saldo_actual || 0) == 0 ? `
                            <button type="button" 
                                    class="btn btn-outline-warning btn-sm" 
                                    onclick="NotasAbonoApp.cerrarNotaAbono(${nota.id}, '${nota.folio || ''}')">
                                <i class="fas fa-lock me-1"></i> Cerrar Nota
                            </button>
                            ` : nota.estado === 'activa' ? `
                            <small class="text-muted text-center">
                                <i class="fas fa-info-circle me-1"></i>Saldo debe ser $0 para cerrar
                            </small>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
},

    limpiarFiltros: function() {
        const fechaDesde = document.getElementById('fecha_desde');
        const fechaHasta = document.getElementById('fecha_hasta');
        const filtroEstado = document.getElementById('filtro_estado');
        
        if (fechaDesde) fechaDesde.value = '';
        if (fechaHasta) fechaHasta.value = '';
        if (filtroEstado) filtroEstado.value = '';
        
        location.reload();
    },

    // FUNCIONES DE NOTAS DE ABONO
    actualizarResumenAjuste: function() {
        const saldoActualElement = document.getElementById('edit_saldo_actual');
        const montoAjusteElement = document.getElementById('monto_ajuste');
        
        if (!saldoActualElement || !montoAjusteElement) return;

        const saldoActual = parseFloat(saldoActualElement.textContent.replace('$', '').replace(/,/g, '')) || 0;
        const montoAjuste = parseFloat(montoAjusteElement.value) || 0;
        const tipoOperacion = document.querySelector('input[name="tipo_operacion"]:checked')?.value;
        
        let nuevoSaldo = saldoActual;
        let simboloAjuste = '+';
        
        if (tipoOperacion === 'sumar') {
            nuevoSaldo = saldoActual + montoAjuste;
            simboloAjuste = '+';
        } else if (tipoOperacion === 'restar') {
            nuevoSaldo = saldoActual - montoAjuste;
            simboloAjuste = '-';
        }
        
        const resumenSaldoActual = document.getElementById('resumen_saldo_actual');
        const resumenMontoAjuste = document.getElementById('resumen_monto_ajuste');
        const resumenNuevoSaldo = document.getElementById('resumen_nuevo_saldo');
        
        if (resumenSaldoActual) {
            resumenSaldoActual.textContent = '$' + saldoActual.toLocaleString('es-MX', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
        
        if (resumenMontoAjuste) {
            resumenMontoAjuste.textContent = 
                simboloAjuste + '$' + montoAjuste.toLocaleString('es-MX', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            
            if (tipoOperacion === 'sumar') {
                resumenMontoAjuste.className = 'text-success fw-bold';
            } else if (tipoOperacion === 'restar') {
                resumenMontoAjuste.className = 'text-danger fw-bold';
            }
        }
        
        if (resumenNuevoSaldo) {
            resumenNuevoSaldo.textContent = '$' + nuevoSaldo.toLocaleString('es-MX', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
    },

    editarNotaAbono: async function(notaAbonoId) {
        try {
            Swal.fire({
                title: 'Cargando información',
                text: 'Por favor espere...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            const response = await fetch(`/notas-abono/${notaAbonoId}`);
            if (!response.ok) throw new Error(`Error ${response.status}`);
            
            const result = await response.json();
            
            Swal.close();

            if (!result.success) {
                throw new Error(result.message || 'Error al obtener los datos');
            }

            const notaAbono = result.nota_abono || result.data || result;
            
            document.getElementById('edit_nota_id').value = notaAbono.id;
            document.getElementById('edit_folio_actual').textContent = notaAbono.folio || 'N/A';
            document.getElementById('edit_abono_inicial_actual').textContent = '$' + (parseFloat(notaAbono.abono_inicial) || 0).toLocaleString('es-MX', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            document.getElementById('edit_saldo_actual').textContent = '$' + (parseFloat(notaAbono.saldo_actual) || 0).toLocaleString('es-MX', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            
            document.getElementById('edit_abono_inicial').value = notaAbono.abono_inicial || '';
            document.getElementById('edit_fecha_abono').value = notaAbono.fecha_apertura?.split('T')[0] || notaAbono.fecha_apertura || '';
            document.getElementById('edit_observaciones').value = notaAbono.observaciones || '';

            this.actualizarResumenAjuste();

            const modal = new bootstrap.Modal(document.getElementById('modalEditarAbono'));
            modal.show();

        } catch (error) {
            console.error('Error al cargar datos para editar:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'No se pudieron cargar los datos de la nota de abono'
            });
        }
    },

    actualizarNotaAbono: async function() {
        try {
        
            const elementosRequeridos = {
                'edit_nota_id': document.getElementById('edit_nota_id'),
                'monto_ajuste': document.getElementById('monto_ajuste'),
                'edit_fecha_abono': document.getElementById('edit_fecha_abono'),
                'edit_observaciones': document.getElementById('edit_observaciones')
            };

            for (const [id, elemento] of Object.entries(elementosRequeridos)) {
                if (!elemento) {
                    throw new Error(`Elemento con ID '${id}' no encontrado en el DOM`);
                }
            }

            const notaAbonoId = elementosRequeridos.edit_nota_id.value;
            const tipoOperacion = document.querySelector('input[name="tipo_operacion"]:checked')?.value || 'editar';
            
       

            let datos = {
                'tipo_operacion': tipoOperacion,
                'fecha_abono': elementosRequeridos.edit_fecha_abono.value,
                'observaciones': elementosRequeridos.edit_observaciones.value,
                '_method': 'PUT'
            };

            if (tipoOperacion === 'sumar' || tipoOperacion === 'restar') {
                const montoAjuste = parseFloat(elementosRequeridos.monto_ajuste.value);
                const conceptoAjuste = document.getElementById('concepto_ajuste')?.value;
                
                if (!montoAjuste || montoAjuste <= 0) {
                    throw new Error('Por favor ingrese un monto válido para el ajuste');
                }

                if (!conceptoAjuste) {
                    throw new Error('Por favor seleccione un concepto para el ajuste');
                }

                datos.monto_ajuste = montoAjuste.toFixed(2);
                datos.concepto_ajuste = conceptoAjuste;

            } else if (tipoOperacion === 'editar') {
                const abonoInicial = document.getElementById('edit_abono_inicial')?.value;
                
                if (!abonoInicial || parseFloat(abonoInicial) <= 0) {
                    throw new Error('Por favor ingrese un abono inicial válido');
                }

                datos.abono_inicial = parseFloat(abonoInicial).toFixed(2);
            }

        

            const submitBtn = document.querySelector('#modalEditarAbono .btn-primary');
            if (!submitBtn) {
                throw new Error('Botón de enviar no encontrado');
            }

            const originalText = submitBtn.innerHTML;

            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';
            submitBtn.disabled = true;

            const response = await fetch(`/notas-abono/${notaAbonoId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(datos)
            });

            const result = await response.json();
           
            if (result.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditarAbono'));
                if (modal) {
                    modal.hide();
                }
                
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: result.message,
                    showConfirmButton: false,
                    timer: 2000
                });

                setTimeout(() => {
                    location.reload();
                }, 2000);
                
            } else {
                if (result.errors) {
                    const errores = Object.values(result.errors).flat().join('\n• ');
                    throw new Error('Errores de validación:\n• ' + errores);
                }
                throw new Error(result.message || 'Error al actualizar la nota de abono');
            }

        } catch (error) {
            console.error('Error en actualizarNotaAbono:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message,
                confirmButtonColor: '#4361ee'
            });
        } finally {
            const submitBtn = document.querySelector('#modalEditarAbono .btn-primary');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-save me-1"></i> Aplicar Cambios';
                submitBtn.disabled = false;
            }
        }
    },

verHistorialAjustes: async function(notaAbonoId) {
    try {
        // Limpiar la paginación e información anterior
        this.limpiarPaginacionMovimientos();
        
        Swal.fire({
            title: 'Cargando historial',
            text: 'Por favor espere...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        // Cargar datos iniciales (página 1)
        const response = await fetch(`/filtros/movimientos/${notaAbonoId}?page=1`);
        
        if (!response.ok) {
            throw new Error('Error al cargar los datos del historial');
        }

        const result = await response.json();
        Swal.close();

        if (!result.success) {
            throw new Error(result.message || 'Error al obtener el historial');
        }

        const notaAbono = result.nota_abono;
        const movimientos = result.movimientos;

        // Llenar información de la nota
        document.getElementById('historial_folio').textContent = notaAbono.folio || 'N/A';
        
        let nombreCliente = 'N/A';
        if (notaAbono.cliente_nombre) {
            nombreCliente = notaAbono.cliente_nombre;
        } else if (notaAbono.cliente && notaAbono.cliente.nombre) {
            nombreCliente = notaAbono.cliente.nombre;
        }
        document.getElementById('historial_cliente').textContent = nombreCliente;
        
        document.getElementById('historial_abono_inicial').textContent = 
            '$' + (parseFloat(notaAbono.abono_inicial) || 0).toLocaleString('es-MX', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        document.getElementById('historial_saldo_actual').textContent = 
            '$' + (parseFloat(notaAbono.saldo_actual) || 0).toLocaleString('es-MX', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

        // Mostrar movimientos y paginación
        this.mostrarMovimientosEnTabla(movimientos);
        this.mostrarPaginacionMovimientos(result.pagination, notaAbonoId);
        this.actualizarInfoPaginacion(result.pagination);
        this.calcularResumenMovimientos(movimientos);

        // Guardar el ID de la nota y mostrar modal
        document.getElementById('modalHistorialAjustes').dataset.notaId = notaAbonoId;
        
        const modal = new bootstrap.Modal(document.getElementById('modalHistorialAjustes'));
        modal.show();

    } catch (error) {
        console.error('Error al cargar historial:', error);
        Swal.close();
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'No se pudo cargar el historial de movimientos'
        });
    }
},

// Agrega esta función para limpiar la paginación
limpiarPaginacionMovimientos: function() {
    const paginationContainer = document.getElementById('paginacionMovimientos');
    const infoContainer = document.getElementById('infoPaginacionMovimientos');
    const tbody = document.getElementById('cuerpoHistorial');
    
    if (paginationContainer) {
        paginationContainer.innerHTML = '';
    }
    
    if (infoContainer) {
        infoContainer.innerHTML = '';
    }
    
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2">Cargando movimientos...</p>
                </td>
            </tr>
        `;
    }
    
    // Limpiar también los filtros
    document.getElementById('filtro_tipo').value = '';
    document.getElementById('filtro_fecha_desde').value = '';
    document.getElementById('filtro_fecha_hasta').value = '';
    
    // Resetear el resumen
    this.calcularResumenMovimientos([]);
},
//
mostrarMovimientosEnTabla: function(movimientos) {
    const tbody = document.getElementById('cuerpoHistorial');
    if (!tbody) return;
    
  
    
    if (movimientos.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-4 text-muted">
                    <i class="fas fa-inbox fa-2x mb-3"></i>
                    <p>No hay movimientos registrados</p>
                    <small class="text-muted">Con los filtros aplicados</small>
                </td>
            </tr>
        `;
        return;
    }

    // Debug del primer movimiento
    if (movimientos.length > 0) {
        const primerMov = movimientos[0];
       
        if (primerMov.usuario) {
          
        }
    }

    tbody.innerHTML = movimientos.map(movimiento => {
        const fecha = new Date(movimiento.created_at);
        
        // Usar el campo correcto 'nombre' en lugar de 'name'
        let nombreUsuario = 'Sistema';
        let usuarioInfo = `User ID: ${movimiento.user_id}`;
        
        if (movimiento.usuario) {
            if (movimiento.usuario.nombre && movimiento.usuario.nombre.trim() !== '') {
                nombreUsuario = movimiento.usuario.nombre;
                usuarioInfo += ` | Usuario: ${movimiento.usuario.usuario}`;
            } else if (movimiento.usuario.usuario && movimiento.usuario.usuario.trim() !== '') {
                nombreUsuario = movimiento.usuario.usuario;
            } else {
                nombreUsuario = `Usuario #${movimiento.user_id}`;
            }
        } else {
            nombreUsuario = `Usuario #${movimiento.user_id}`;
            usuarioInfo += ' (relación no cargada)';
        }

        return `
            <tr>
                <td>
                    <small class="text-muted">
                        ${fecha.toLocaleDateString('es-MX')}<br>
                        <span class="text-muted">${fecha.toLocaleTimeString('es-MX')}</span>
                    </small>
                </td>
                <td>
                    <span class="badge ${this.getClaseBadgeTipo(movimiento.tipo)}">
                        ${this.getEtiquetaTipo(movimiento.tipo)}
                    </span>
                </td>
                <td>${movimiento.concepto || 'Sin concepto'}</td>
                <td class="fw-bold ${this.getClaseMonto(movimiento.tipo)}">
                    ${this.getSimboloMonto(movimiento.tipo)}$${parseFloat(movimiento.monto).toLocaleString('es-MX', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    })}
                </td>
                <td>
                    $${parseFloat(movimiento.saldo_anterior).toLocaleString('es-MX', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    })}
                </td>
                <td class="fw-bold">
                    $${parseFloat(movimiento.nuevo_saldo).toLocaleString('es-MX', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    })}
                </td>
                <td>
                    <small class="text-muted" title="${usuarioInfo}">
                        ${nombreUsuario}
                    </small>
                </td>
                <td>
                    <small class="text-muted">
                        ${movimiento.observaciones || 'Sin observaciones'}
                    </small>
                </td>
            </tr>
        `;
    }).join('');
},

    calcularResumenMovimientos: function(movimientos) {
        let totalAbonos = 0;
        let totalCompras = 0;
        let totalAjustes = 0;

        movimientos.forEach(movimiento => {
            const monto = parseFloat(movimiento.monto);
            
            switch (movimiento.tipo) {
                case 'abono':
                    totalAbonos += monto;
                    break;
                case 'compra':
                    totalCompras += monto;
                    break;
                case 'ajuste':
                    totalAjustes += monto;
                    break;
            }
        });

        document.getElementById('total_movimientos').textContent = movimientos.length;
        document.getElementById('total_abonos').textContent = 
            '$' + totalAbonos.toLocaleString('es-MX', { minimumFractionDigits: 2 });
        document.getElementById('total_compras').textContent = 
            '$' + totalCompras.toLocaleString('es-MX', { minimumFractionDigits: 2 });
        document.getElementById('total_ajustes').textContent = 
            '$' + totalAjustes.toLocaleString('es-MX', { minimumFractionDigits: 2 });
    },

   aplicarFiltrosHistorial: async function() {
    try {
        const modal = document.getElementById('modalHistorialAjustes');
        if (!modal) return;

        const notaId = modal.dataset.notaId;
        if (!notaId) {
            console.error('No se encontró el ID de la nota en el modal');
            return;
        }

        const tipo = document.getElementById('filtro_tipo')?.value;
        const fechaDesde = document.getElementById('filtro_fecha_desde')?.value;
        const fechaHasta = document.getElementById('filtro_fecha_hasta')?.value;

      

        // Validar fechas si ambas están presentes
        if (fechaDesde && fechaHasta && new Date(fechaDesde) > new Date(fechaHasta)) {
            Swal.fire({
                icon: 'error',
                title: 'Error en fechas',
                text: 'La fecha "Desde" no puede ser mayor que la fecha "Hasta"',
                confirmButtonColor: '#4361ee',
                timer: 2000
            });
            return;
        }

        // Mostrar loading en la tabla
        const tbody = document.getElementById('cuerpoHistorial');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2">Filtrando movimientos...</p>
                    </td>
                </tr>
            `;
        }

        // Construir parámetros de búsqueda
        const params = new URLSearchParams();
        if (tipo) params.append('tipo', tipo);
        if (fechaDesde) params.append('fecha_desde', fechaDesde);
        if (fechaHasta) params.append('fecha_hasta', fechaHasta);

        const url = `/notas-abono/${notaId}/historial?${params.toString()}`;
      

        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }

        const result = await response.json();

        if (result.success) {
            this.mostrarMovimientosEnTabla(result.movimientos);
            this.calcularResumenMovimientos(result.movimientos);
            
            // Mostrar mensaje de éxito si hay filtros aplicados
            if (tipo || fechaDesde || fechaHasta) {
                const totalFiltrado = result.movimientos ? result.movimientos.length : 0;
              
            }
        } else {
            throw new Error(result.message || 'Error al aplicar filtros');
        }

    } catch (error) {
        console.error('Error al aplicar filtros del historial:', error);
        
        const tbody = document.getElementById('cuerpoHistorial');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4 text-danger">
                        <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                        <p>Error al cargar los movimientos</p>
                        <small class="text-muted">${error.message}</small>
                    </td>
                </tr>
            `;
        }
        
        // Resetear resumen
        this.calcularResumenMovimientos([]);
    }
},

// Función para limpiar filtros del historial
limpiarFiltrosHistorial: function() {
    document.getElementById('filtro_tipo').value = '';
    document.getElementById('filtro_fecha_desde').value = '';
    document.getElementById('filtro_fecha_hasta').value = '';
    
    // Re-aplicar filtros (que ahora estarán vacíos)
    this.aplicarFiltrosHistorial();
},

    cerrarNotaAbono: async function(notaAbonoId, folio) {
        const { value: confirmar } = await Swal.fire({
            title: '¿Cerrar esta nota de abono?',
            html: `<p>Vas a cerrar la nota: <strong>${folio}</strong></p><p>Esta acción no se puede deshacer.</p>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, cerrar nota',
            cancelButtonText: 'Cancelar'
        });

        if (!confirmar) return;

        try {
            const response = await fetch(`/notas-abono/${notaAbonoId}/cerrar`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Nota Cerrada!',
                    text: result.message,
                    showConfirmButton: false,
                    timer: 1500
                });

                setTimeout(() => location.reload(), 1500);
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message
            });
        }
    },

    guardarAbono: async function() {
        const montoInput = document.getElementById('monto_abono');
        const fechaInput = document.getElementById('fecha_abono');
        const clienteIdInput = document.querySelector('input[name="cliente_id"]');
        
        if (!montoInput || !fechaInput || !clienteIdInput) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se encontraron los campos necesarios',
                confirmButtonColor: '#4361ee'
            });
            return;
        }

        const monto = montoInput.value;
        const fecha = fechaInput.value;
        const clienteId = clienteIdInput.value;
        
        if (!monto || monto <= 0) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Por favor ingrese un monto válido',
                confirmButtonColor: '#4361ee'
            });
            return;
        }

        const submitBtn = document.querySelector('#modalCrearAbono .btn-primary');
        if (!submitBtn) return;

        const originalText = submitBtn.innerHTML;

        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
        submitBtn.disabled = true;

        try {
            const formData = new FormData(document.getElementById('formCrearAbono'));

            const response = await fetch(this.config.apiEndpoints.notasAbonoCliente, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (!response.ok) throw new Error(result.message || 'Error en la petición');

            if (result.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalCrearAbono'));
                if (modal) modal.hide();

                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    html: `
                        <div class="text-center">
                            <h5>Nota de abono creada</h5>
                            <p class="mb-2"><strong>Folio:</strong> ${result.folio}</p>
                            <p class="mb-2"><strong>Monto:</strong> $${parseFloat(monto).toLocaleString('es-MX', {minimumFractionDigits: 2})}</p>
                        </div>
                    `,
                    showConfirmButton: false,
                    timer: 3000
                });

                document.getElementById('formCrearAbono').reset();
                fechaInput.value = new Date().toISOString().split('T')[0];

                setTimeout(() => {
                    window.location.reload();
                }, 3000);

            } else {
                throw new Error(result.message || 'Error al guardar el abono');
            }

        } catch (error) {
            console.error('Error al guardar abono:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message,
                confirmButtonColor: '#4361ee'
            });
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    },

    // FUNCIONES DE CLIENTES
    verCliente: async function(clienteId) {
        try {
            const modal = document.getElementById('modalVerCliente');
            if (!modal) return;

            const modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();

            const response = await fetch(`${this.config.apiEndpoints.clientes}/${clienteId}`);
            if (!response.ok) throw new Error(`Error ${response.status}`);
            
            const cliente = await response.json();

            this.setTextSafe('clienteNombre', cliente.nombre);
            this.setTextSafe('clienteTelefono', cliente.telefono);
            this.setTextSafe('clienteCorreo', cliente.correo);
            this.setTextSafe('clienteRfc', cliente.rfc);
            this.setTextSafe('clienteDireccion', cliente.direccion);
            this.setTextSafe('clienteObservaciones', cliente.observaciones);
            this.setTextSafe('clienteRegimen', cliente.regimen ? cliente.regimen.nombre : 'No especificado');
            
            this.setTextSafe('clienteSaldoGlobal', this.formatCurrency(cliente.saldo_global || 0));

        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al cargar información del cliente',
                confirmButtonColor: '#4361ee'
            });
        }
    },

    editarCliente: async function(clienteId) {
        try {
         
            Swal.fire({
                title: 'Cargando información',
                text: 'Por favor espere...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            const response = await fetch(`${this.config.apiEndpoints.clientes}/${clienteId}`);
            if (!response.ok) throw new Error(`Error ${response.status}`);
            
            const cliente = await response.json();
            
            Swal.close();

            document.getElementById('edit_cliente_id').value = cliente.id;
            document.getElementById('edit_nombre').value = cliente.nombre || '';
            document.getElementById('edit_correo').value = cliente.correo || '';
            document.getElementById('edit_telefono').value = cliente.telefono || '';
            document.getElementById('edit_rfc').value = cliente.rfc || '';
            document.getElementById('edit_direccion').value = cliente.direccion || '';
            document.getElementById('edit_observaciones').value = cliente.observaciones || '';
            document.getElementById('edit_regimen_id').value = cliente.regimen_id || '';

            const modal = new bootstrap.Modal(document.getElementById('modalEditarCliente'));
            modal.show();

        } catch (error) {
            console.error('Error al cargar datos para editar:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudieron cargar los datos del cliente',
                confirmButtonColor: '#4361ee'
            });
        }
    },

    actualizarCliente: async function(e) {
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        const clienteId = document.getElementById('edit_cliente_id').value;
        
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Actualizando...';
        submitBtn.disabled = true;
        
        try {
            const formData = new FormData(form);
            
            const response = await fetch(`${this.config.apiEndpoints.clientes}/${clienteId}`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-HTTP-Method-Override': 'PUT'
                }
            });
            
            if (!response.ok) throw new Error(`Error ${response.status}`);
            
            const result = await response.json();
            
            if (result.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalEditarCliente')).hide();
                
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: result.message || 'Cliente actualizado correctamente',
                    showConfirmButton: false,
                    timer: 2000
                });
                
                setTimeout(() => {
                    location.reload();
                }, 2000);
                
            } else {
                throw new Error(result.message || 'Error al actualizar el cliente');
            }
            
        } catch (error) {
            console.error('Error al actualizar cliente:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message,
                confirmButtonColor: '#4361ee'
            });
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    },

    // FUNCIONES AUXILIARES
    getClaseBadgeTipo: function(tipo) {
        const clases = {
            'abono': 'bg-success',
            'compra': 'bg-danger',
            'ajuste': 'bg-warning text-dark',
            'cierre': 'bg-secondary'
        };
        return clases[tipo] || 'bg-info';
    },

    getEtiquetaTipo: function(tipo) {
        const etiquetas = {
            'abono': 'Abono',
            'compra': 'Compra',
            'ajuste': 'Ajuste',
            'cierre': 'Cierre'
        };
        return etiquetas[tipo] || tipo;
    },

    getClaseMonto: function(tipo) {
        const clases = {
            'abono': 'text-success',
            'compra': 'text-danger',
            'ajuste': 'text-warning'
        };
        return clases[tipo] || 'text-info';
    },

    getSimboloMonto: function(tipo) {
        const simbolos = {
            'abono': '+',
            'compra': '-',
            'ajuste': '±'
        };
        return simbolos[tipo] || '';
    },

    setTextSafe: function(elementId, text) {
        const element = document.getElementById(elementId);
        if (element) {
            element.textContent = text || 'No especificado';
        } else {
            console.warn(`Elemento #${elementId} no encontrado`);
        }
    },

    formatCurrency: function(amount) {
        return '$' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    },

    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    exportarHistorial: async function() {
    try {
        // Obtener el ID de la nota del modal
        const modal = document.getElementById('modalHistorialAjustes');
        if (!modal) {
            throw new Error('No se encontró el modal del historial');
        }

        const notaId = modal.dataset.notaId;
        if (!notaId) {
            throw new Error('No se encontró el ID de la nota');
        }

        // Obtener los filtros actuales
        const tipo = document.getElementById('filtro_tipo')?.value || '';
        const fechaDesde = document.getElementById('filtro_fecha_desde')?.value || '';
        const fechaHasta = document.getElementById('filtro_fecha_hasta')?.value || '';

        // Mostrar loading
        Swal.fire({
            title: 'Generando PDF',
            text: 'Por favor espere...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        // Construir URL con filtros
        const params = new URLSearchParams();
        if (tipo) params.append('tipo', tipo);
        if (fechaDesde) params.append('fecha_desde', fechaDesde);
        if (fechaHasta) params.append('fecha_hasta', fechaHasta);

        const url = `/notas-abono/${notaId}/exportar-pdf?${params.toString()}`;
   
        // Crear un link temporal para la descarga
        const link = document.createElement('a');
        link.href = url;
        link.target = '_blank';
        link.download = `historial-nota-${notaId}.pdf`;
        
        // Simular click
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        Swal.close();

        // Mostrar mensaje de éxito
        Swal.fire({
            icon: 'success',
            title: 'PDF Generado',
            text: 'El historial se ha descargado correctamente',
            showConfirmButton: false,
            timer: 2000
        });

    } catch (error) {
        console.error('Error al exportar historial:', error);
        Swal.close();
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'No se pudo generar el PDF',
            confirmButtonColor: '#4361ee'
        });
    }
},
};

// Manejador de paginación (usa jQuery como en tu código original)
$(document).on('click', '#paginacion a', function(e) {
    e.preventDefault();
    var page = $(this).attr('href').split('page=')[1];
    NotasAbonoApp.buscarClientes(page);
});