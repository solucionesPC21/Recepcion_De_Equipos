class RecepcionEquipos {
    constructor() {
        this.contadorEquipos = 1;
        this.clienteSeleccionado = null;
        this.MAX_EQUIPOS = 15; // Límite para prevenir abusos
        this.coloniaSeleccionada = null; // ← FALTA ESTA LÍNEA
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupBusquedaClientes();
        this.setupMarcaDinamica();
          this.setupBusquedaColonias(); // Nueva función para búsqueda de colonias
    }

    setupEventListeners() {
        // Modal registrar cliente
        document.getElementById('registrarClienteBtn')?.addEventListener('click', () => this.mostrarModalRegistrar());
        document.getElementById('cerrarModal')?.addEventListener('click', () => this.cerrarModalRegistrar());
        document.getElementById('cancelarRegistro')?.addEventListener('click', () => this.cerrarModalRegistrar());
          const camposMayusculas = ['nombre', 'rfc','buscarColonia','modelos','nueva_marca']; // Agrega los nombres de los campos que quieras
        camposMayusculas.forEach(campoId => {
            document.getElementById(campoId)?.addEventListener('input', (e) => {
                e.target.value = e.target.value.toUpperCase();
            });
        });
            // Cerrar modal al hacer click fuera
        document.getElementById('modalRegistrarCliente')?.addEventListener('click', (e) => {
            if (e.target.id === 'modalRegistrarCliente') this.cerrarModalRegistrar();
        });

        // Formulario registrar cliente
        document.getElementById('formRegistrarCliente')?.addEventListener('submit', (e) => this.registrarCliente(e));

        // Duplicar campos de equipo
        document.getElementById('duplicarCampo')?.addEventListener('click', () => this.duplicarCampos());

        // Formulario generar recibo
        document.getElementById('formGenerarRecibo')?.addEventListener('submit', (e) => this.generarRecibo(e));

        // Cerrar dropdown al hacer click fuera
        document.addEventListener('click', (e) => {
            if (!e.target.matches('#search') && !e.target.matches('#searchResults li')) {
                this.ocultarResultados();
            }
        });
    }
  //Buscar colonias al registrar al cliente
  setupBusquedaColonias() {
        const buscarColoniaInput = document.getElementById('buscarColonia');
        if (!buscarColoniaInput) return;

        let timeout;

        // Búsqueda en tiempo real
        buscarColoniaInput.addEventListener('input', (e) => {
            clearTimeout(timeout);
            const termino = e.target.value.trim();
            
            if (termino.length < 2) {
                this.ocultarResultadosColonias();
                return;
            }
            
            timeout = setTimeout(() => {
                this.buscarColonias(termino);
            }, 300);
        });

        // Cerrar resultados al hacer click fuera
        document.addEventListener('click', (e) => {
            if (!e.target.matches('#buscarColonia') && 
                !e.target.matches('.resultado-colonia') &&
                !e.target.closest('.resultado-colonia')) {
                this.ocultarResultadosColonias();
            }
        });

        // Limpiar búsqueda al perder foco si no hay selección
        buscarColoniaInput.addEventListener('blur', () => {
            setTimeout(() => {
                if (!this.coloniaSeleccionada) {
                    buscarColoniaInput.value = '';
                }
            }, 200);
        });
    }
  async buscarColonias(termino) {
      
        
        try {
            const response = await fetch(`/home/buscarColonia?term=${encodeURIComponent(termino)}`);
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            
            const colonias = await response.json();
            this.mostrarResultadosColonias(colonias);
        } catch (error) {
            console.error('Error en búsqueda de colonias:', error);
            this.mostrarErrorColonias('Error al buscar colonias');
        }
    }

     mostrarResultadosColonias(colonias) {
        const resultados = document.getElementById('coloniaResults');
        if (!resultados) return;

        resultados.innerHTML = '';

        if (!colonias || colonias.length === 0) {
            resultados.innerHTML = '<div class="no-results">No se encontraron colonias</div>';
            resultados.classList.remove('hidden');
            return;
        }

        colonias.forEach(colonia => {
            const div = document.createElement('div');
            div.className = 'resultado-colonia';
            div.innerHTML = `
                <div class="colonia-info">
                    <strong>${this.escapeHtml(colonia.colonia)}</strong>
                </div>
            `;
            
            div.setAttribute('data-colonia-id', colonia.id);
            div.setAttribute('data-colonia-nombre', colonia.colonia);
            
            div.addEventListener('click', () => this.seleccionarColonia(div));
            
            resultados.appendChild(div);
        });

        resultados.classList.remove('hidden');
    }

    seleccionarColonia(elemento) {
        const coloniaId = elemento.getAttribute('data-colonia-id');
        const coloniaNombre = elemento.getAttribute('data-colonia-nombre');

        this.coloniaSeleccionada = {
            id: coloniaId,
            nombre: coloniaNombre
        };

        // Actualizar campos
        document.getElementById('colonia_id').value = coloniaId;
        document.getElementById('buscarColonia').value = coloniaNombre;

        // Mostrar colonia seleccionada
        this.mostrarColoniaSeleccionada();

        // Ocultar resultados
        this.ocultarResultadosColonias();

     
    }

    mostrarColoniaSeleccionada() {
        const contenedor = document.getElementById('colonia-seleccionada');
        if (!contenedor) return;

        if (this.coloniaSeleccionada) {
            contenedor.innerHTML = `
                <div class="colonia-info">
                    <span class="colonia-nombre">${this.escapeHtml(this.coloniaSeleccionada.nombre)}</span>
                    <button type="button" class="btn-eliminar" title="Quitar colonia">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;

            // Agregar evento al botón eliminar
            contenedor.querySelector('.btn-eliminar').addEventListener('click', (e) => {
                e.preventDefault();
                this.limpiarColoniaSeleccionada();
            });
        } else {
            contenedor.innerHTML = '<span class="no-colonia">Ninguna colonia seleccionada</span>';
        }
    }

    limpiarColoniaSeleccionada() {
        this.coloniaSeleccionada = null;
        document.getElementById('colonia_id').value = '';
        document.getElementById('buscarColonia').value = '';
        this.mostrarColoniaSeleccionada();
    }

    ocultarResultadosColonias() {
        const resultados = document.getElementById('coloniaResults');
        if (resultados) {
            resultados.classList.add('hidden');
        }
    }

    mostrarErrorColonias(mensaje) {
        const resultados = document.getElementById('coloniaResults');
        if (resultados) {
            resultados.innerHTML = `<div class="error">${this.escapeHtml(mensaje)}</div>`;
            resultados.classList.remove('hidden');
        }
    }
     setupBusquedaClientes() {
        const searchInput = document.getElementById('search');
        if (!searchInput) return;

        let timeout;
        
        // Búsqueda en tiempo real
        searchInput.addEventListener('input', (e) => {
            clearTimeout(timeout);
            const termino = e.target.value.trim();
            
            if (termino.length < 2) {
                this.ocultarResultados();
                this.limpiarClienteSeleccionado();
                return;
            }
            
            timeout = setTimeout(() => {
                this.buscarClientes(termino);
            }, 300);
        });

        // También buscar cuando se presiona Enter
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.buscarClientes(e.target.value.trim());
            }
        });

        // Limpiar selección si se borra el input
        searchInput.addEventListener('blur', (e) => {
            setTimeout(() => {
                if (!e.target.value.trim()) {
                    this.limpiarClienteSeleccionado();
                }
            }, 200);
        });
    }


    setupMarcaDinamica() {
        // Delegación de eventos para los selects de marca
        document.addEventListener('change', (e) => {
            if (e.target.name === 'marca[]' && e.target.value === 'nueva_marca') {
                this.mostrarCampoNuevaMarca(e.target);
            } else if (e.target.name === 'marca[]' && e.target.value !== 'nueva_marca') {
                this.ocultarCampoNuevaMarca(e.target);
            }
        });
    }
  limpiarClienteSeleccionado() {
        this.clienteSeleccionado = null;
        document.getElementById('cliente-info').style.display = 'none';
        document.getElementById('formGenerarRecibo').style.display = 'none';
        document.getElementById('nombre_cliente').value = '';
        document.getElementById('cliente_id').value = '';
    }

    mostrarCampoNuevaMarca(selectElement) {
        const equipoCard = selectElement.closest('.equipo-card');
        const nuevaMarcaDiv = equipoCard.querySelector('.nueva-marca');
        if (nuevaMarcaDiv) {
            nuevaMarcaDiv.style.display = 'block';
            nuevaMarcaDiv.querySelector('input').required = true;
        }
    }

    ocultarCampoNuevaMarca(selectElement) {
        const equipoCard = selectElement.closest('.equipo-card');
        const nuevaMarcaDiv = equipoCard.querySelector('.nueva-marca');
        if (nuevaMarcaDiv) {
            nuevaMarcaDiv.style.display = 'none';
            nuevaMarcaDiv.querySelector('input').required = false;
            nuevaMarcaDiv.querySelector('input').value = '';
        }
    }

   async buscarClientes(termino) {
   
    
    if (!termino || termino.length < 2) {
        this.ocultarResultados();
        return;
    }

    try {
        this.mostrarLoadingBusqueda(true);
        
        const response = await fetch(`/home/buscar?term=${encodeURIComponent(termino)}`);
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        
        const clientes = await response.json();
       
        this.mostrarResultados(clientes);
    } catch (error) {
        console.error('Error en búsqueda:', error);
        this.mostrarErrorBusqueda('Error al buscar clientes');
    } finally {
        this.mostrarLoadingBusqueda(false);
    }
}

mostrarResultados(clientes) {
    const resultados = document.getElementById('searchResults');
    if (!resultados) {
        console.error('❌ Elemento searchResults no encontrado en el DOM');
        return;
    }

   

    // Limpiar resultados anteriores
    resultados.innerHTML = '';

    if (!clientes || clientes.length === 0) {
     
        resultados.innerHTML = '<li class="no-results">No se encontraron clientes</li>';
        resultados.classList.remove('hidden');
        return;
    }

  

    // Crear elementos de lista para cada cliente
    clientes.forEach((cliente, index) => {
      
        
        const li = document.createElement('li');
        li.className = 'resultado-cliente';
        li.style.padding = '10px';
        li.style.borderBottom = '1px solid #eee';
        li.style.cursor = 'pointer';
        
        // Asegurar que los datos existen
        const nombre = cliente.nombre || 'Sin nombre';
        const telefono = cliente.telefono || 'Sin teléfono';
        const rfc = cliente.rfc || '';
        
        // Manejar la colonia correctamente
        let coloniaNombre = 'Sin colonia registrada';
        if (cliente.colonia) {
            if (typeof cliente.colonia === 'string') {
                coloniaNombre = cliente.colonia;
            } else if (cliente.colonia.colonia) {
                coloniaNombre = cliente.colonia.colonia;
            } else if (cliente.colonia.nombre) {
                coloniaNombre = cliente.colonia.nombre;
            }
        }
      

        li.innerHTML = `
            <div class="cliente-info">
                <strong>${this.escapeHtml(nombre)}</strong>
                <div class="cliente-detalles">
                    <span class="telefono">Tel: ${this.escapeHtml(telefono)}</span>
                    ${rfc ? `<span class="rfc">RFC: ${this.escapeHtml(rfc)}</span>` : ''}
                    <span class="colonia">Colonia: ${this.escapeHtml(coloniaNombre)}</span>
                </div>
            </div>
        `;
        
        // Agregar datos como atributos
        li.setAttribute('data-cliente-id', cliente.id);
        li.setAttribute('data-cliente-nombre', nombre);
        li.setAttribute('data-cliente-telefono', telefono);
        li.setAttribute('data-cliente-rfc', rfc);
        li.setAttribute('data-cliente-colonia', coloniaNombre);
        
        // Event listener para selección
        li.addEventListener('click', () => {
         
            this.seleccionarCliente(li);
        });
        
        // Agregar hover effect
        li.addEventListener('mouseenter', () => {
            li.style.backgroundColor = '#f8f9fa';
        });
        
        li.addEventListener('mouseleave', () => {
            li.style.backgroundColor = '';
        });
        
        resultados.appendChild(li);
    });


    
    // Remover la clase hidden
    resultados.classList.remove('hidden');

    // Forzar display block por si acaso
    resultados.style.display = 'block';
}
    mostrarLoadingBusqueda(mostrar) {
        let loadingElement = document.getElementById('search-loading');
        if (!loadingElement) {
            loadingElement = document.createElement('div');
            loadingElement.id = 'search-loading';
            loadingElement.className = 'search-loading hidden';
            loadingElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando...';
            document.querySelector('.search-container').appendChild(loadingElement);
        }
        
        if (mostrar) {
            loadingElement.classList.remove('hidden');
        } else {
            loadingElement.classList.add('hidden');
        }
    }

    seleccionarCliente(elemento) {
   
        
        const clienteId = elemento.getAttribute('data-cliente-id');
        const clienteNombre = elemento.getAttribute('data-cliente-nombre');
        const clienteTelefono = elemento.getAttribute('data-cliente-telefono');
        const clienteRfc = elemento.getAttribute('data-cliente-rfc');
        const clienteColonia = elemento.getAttribute('data-cliente-colonia');

        // Guardar información del cliente seleccionado
        this.clienteSeleccionado = {
            id: clienteId,
            nombre: clienteNombre,
            telefono: clienteTelefono,
            rfc: clienteRfc,
            colonia: clienteColonia
        };

      

        // Actualizar campos hidden del formulario
        document.getElementById('nombre_cliente').value = clienteNombre;
        document.getElementById('cliente_id').value = clienteId;

        // Mostrar información del cliente
        this.mostrarInformacionCliente();

        // Mostrar formulario de equipos
        document.getElementById('formGenerarRecibo').style.display = 'block';

        // Actualizar el input de búsqueda con el nombre del cliente
        document.getElementById('search').value = clienteNombre;

        // Ocultar resultados de búsqueda
        this.ocultarResultados();

        // Hacer scroll al formulario de equipos
        setTimeout(() => {
            document.getElementById('formGenerarRecibo').scrollIntoView({ 
                behavior: 'smooth', 
                block: 'start' 
            });
        }, 300);
    }

     mostrarInformacionCliente() {
        const clienteInfo = document.getElementById('cliente-info');
        const clienteDetails = document.getElementById('cliente-details');

        if (!clienteInfo || !clienteDetails) {
            console.error('Elementos del DOM no encontrados');
            return;
        }

        if (!this.clienteSeleccionado) {
            console.error('No hay cliente seleccionado');
            return;
        }

      

        // Mostrar información del cliente
        clienteDetails.innerHTML = `
            <div class="col-md-3">
                <strong>Nombre:</strong> ${this.escapeHtml(this.clienteSeleccionado.nombre)}
            </div>
            <div class="col-md-3">
                <strong>Teléfono:</strong> ${this.escapeHtml(this.clienteSeleccionado.telefono)}
            </div>
            <div class="col-md-3">
                <strong>RFC:</strong> ${this.escapeHtml(this.clienteSeleccionado.rfc || 'No especificado')}
            </div>
            <div class="col-md-3">
                <strong>Colonia:</strong> ${this.escapeHtml(this.clienteSeleccionado.colonia || 'Sin colonia registrada')}
            </div>
        `;

        clienteInfo.style.display = 'block';
    }

     ocultarResultados() {
        const resultados = document.getElementById('searchResults');
        if (resultados) {
            resultados.classList.add('hidden');
        }
    }

    mostrarErrorBusqueda(mensaje) {
        const resultados = document.getElementById('searchResults');
        if (resultados) {
            resultados.innerHTML = `<li class="error">${this.escapeHtml(mensaje)}</li>`;
            resultados.classList.remove('hidden');
        }
    }
    mostrarError(mensaje) {
        const resultados = document.getElementById('searchResults');
        if (resultados) {
            resultados.innerHTML = `<li class="error">${this.escapeHtml(mensaje)}</li>`;
            resultados.classList.remove('hidden');
        }
    }

    // Modal registrar cliente
    mostrarModalRegistrar() {
        document.getElementById('modalRegistrarCliente').style.display = 'block';
    }

    cerrarModalRegistrar() {
        document.getElementById('modalRegistrarCliente').style.display = 'none';
        this.limpiarFormularioRegistro();
    }

    limpiarFormularioRegistro() {
        const form = document.getElementById('formRegistrarCliente');
        if (form) {
            form.reset();
            document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
            // Remover estilos de error
            form.querySelectorAll('input, select').forEach(input => {
                input.style.borderColor = '';
            });
        }
    }

     async registrarCliente(e) {
    e.preventDefault();
    // Validar que si se escribió en buscarColonia, se haya seleccionado una colonia
    const buscarColoniaInput = document.getElementById('buscarColonia');
    const coloniaIdInput = document.getElementById('colonia_id');
    
    if (buscarColoniaInput.value.trim() && !coloniaIdInput.value) {
        this.mostrarErrorCampo(buscarColoniaInput, 'Por favor selecciona una colonia de la lista');
        return;
    }
    
    if (!this.validarFormularioCliente()) {
        return;
    }

    const formData = new FormData(e.target);
    
    // Mostrar loading
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    
    try {
        const response = await fetch('/clientes', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            this.cerrarModalRegistrar();
            
            Swal.fire({
                icon: 'success',
                title: '¡Cliente registrado!',
                text: data.message,
                confirmButtonColor: '#28a745',
                timer: 3000,
                timerProgressBar: true
            }).then(() => {
                // IMPORTANTE: Seleccionar automáticamente el nuevo cliente
                if (data.cliente) {
                    // CORREGIR: Manejar correctamente la colonia
                    this.clienteSeleccionado = {
                        id: data.cliente.id,
                        nombre: data.cliente.nombre,
                        telefono: data.cliente.telefono,
                        rfc: data.cliente.rfc || '',
                        colonia: this.obtenerNombreColonia(data.cliente.colonia)
                    };

                    this.mostrarInformacionCliente();
                    
                    // Actualizar campos hidden del formulario
                    document.getElementById('nombre_cliente').value = data.cliente.nombre;
                    document.getElementById('cliente_id').value = data.cliente.id;
                    
                    // Limpiar campo de búsqueda y mostrar nombre
                    document.getElementById('search').value = data.cliente.nombre;
                    
                    // MOSTRAR FORMULARIO DE EQUIPOS
                    document.getElementById('formGenerarRecibo').style.display = 'block';
                }
            });
            
        } else {
            this.mostrarErroresRegistro(data.errors);
            
            if (data.message && data.message !== 'Error de validación') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message,
                    confirmButtonColor: '#dc3545'
                });
            }
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error de conexión',
            text: 'No se pudo conectar con el servidor',
            confirmButtonColor: '#dc3545'
        });
        console.error('Error:', error);
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}

obtenerNombreColonia(coloniaData) {
        if (!coloniaData) {
            return 'Sin colonia registrada';
        }
        
        if (typeof coloniaData === 'string') {
            return coloniaData;
        }
        
        if (typeof coloniaData === 'object') {
            if (coloniaData.colonia) {
                return coloniaData.colonia;
            } else if (coloniaData.nombre) {
                return coloniaData.nombre;
            } else if (coloniaData.name) {
                return coloniaData.name;
            }
            return 'Colonia no especificada';
        }
        
        return 'Sin colonia registrada';
    }

    validarFormularioCliente() {
        let esValido = true;
        const telefono = document.getElementById('telefono');
        const telefono2 = document.getElementById('telefono2');
        const rfc = document.getElementById('rfc');

        // Validar teléfono principal
        if (telefono && telefono.value && !this.validarTelefono(telefono.value)) {
            this.mostrarErrorCampo(telefono, 'El teléfono debe tener 10 dígitos');
            esValido = false;
        }

        // Validar teléfono secundario
        if (telefono2 && telefono2.value && !this.validarTelefono(telefono2.value)) {
            this.mostrarErrorCampo(telefono2, 'El teléfono debe tener 10 dígitos');
            esValido = false;
        }

        // Validar RFC
        if (rfc && rfc.value && !this.validarRFC(rfc.value)) {
            this.mostrarErrorCampo(rfc, 'RFC inválido');
            esValido = false;
        }

        return esValido;
    }

    validarTelefono(telefono) {
        return /^\d{10}$/.test(telefono);
    }

    validarRFC(rfc) {
        return /^[A-Z&Ñ]{3,4}[0-9]{6}[A-Z0-9]{3}$/.test(rfc);
    }

    mostrarErrorCampo(campo, mensaje) {
        const errorElement = document.getElementById(`error-${campo.name}`);
        if (errorElement) {
            errorElement.textContent = mensaje;
            campo.style.borderColor = '#dc3545';
        }
    }

    mostrarErroresRegistro(errors) {
        document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
        document.querySelectorAll('input, select').forEach(input => {
            input.style.borderColor = '';
        });
        
        if (errors) {
            Object.keys(errors).forEach(field => {
                const errorElement = document.getElementById(`error-${field}`);
                if (errorElement) {
                    errorElement.textContent = errors[field][0];
                    
                    const inputElement = document.getElementById(field);
                    if (inputElement) {
                        inputElement.style.borderColor = '#dc3545';
                        inputElement.addEventListener('input', function() {
                            this.style.borderColor = '';
                            errorElement.textContent = '';
                        }, { once: true });
                    }
                }
            });
            
            if (errors.general) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errors.general[0],
                    confirmButtonColor: '#dc3545'
                });
            }
        }
    }

    // Gestión de equipos
    duplicarCampos() {
        if (this.contadorEquipos >= this.MAX_EQUIPOS) {
            Swal.fire({
                icon: 'warning',
                title: 'Límite alcanzado',
                text: `Solo puedes agregar hasta ${this.MAX_EQUIPOS} equipos`,
                confirmButtonColor: '#ffc107'
            });
            return;
        }

        this.contadorEquipos++;
        
        const equipoPrincipal = document.getElementById('equipo-principal');
        const clon = equipoPrincipal.cloneNode(true);
        
        this.prepararClonEquipo(clon, this.contadorEquipos);
        
        document.getElementById('equipos-adicionales').appendChild(clon);
        this.agregarBotonEliminar(clon);
        
        this.actualizarContadorEquipos();
    }

    prepararClonEquipo(clon, numero) {
        // Actualizar header
        const header = clon.querySelector('.equipo-header h4');
        if (header) header.textContent = `Equipo #${numero}`;
        
        // Limpiar valores
        const inputs = clon.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            if (input.type !== 'button' && input.type !== 'submit') {
                input.value = '';
                // Remover atributos de validación previos
                input.style.borderColor = '';
            }
        });
        
        // Ocultar nueva marca
        const nuevaMarca = clon.querySelector('.nueva-marca');
        if (nuevaMarca) {
            nuevaMarca.style.display = 'none';
            nuevaMarca.querySelector('input').required = false;
        }
        
        // Remover IDs duplicados si existen
        clon.removeAttribute('id');
        clon.querySelectorAll('[id]').forEach(element => {
            element.removeAttribute('id');
        });
    }

    agregarBotonEliminar(equipo) {
        const botonEliminar = document.createElement('button');
        botonEliminar.type = 'button';
        botonEliminar.className = 'btn btn-danger btn-sm btn-eliminar-equipo';
        botonEliminar.innerHTML = '<i class="fas fa-trash"></i> Eliminar';
        botonEliminar.addEventListener('click', () => this.eliminarEquipo(equipo));
        
        equipo.querySelector('.equipo-header').appendChild(botonEliminar);
    }

    eliminarEquipo(equipo) {
        if (this.contadorEquipos <= 1) {
            Swal.fire({
                icon: 'warning',
                title: 'Atención',
                text: 'Debe haber al menos un equipo',
                confirmButtonColor: '#ffc107'
            });
            return;
        }
        
        Swal.fire({
            title: '¿Eliminar equipo?',
            text: "Esta acción no se puede deshacer",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                equipo.remove();
                this.contadorEquipos--;
                this.actualizarContadorEquipos();
            }
        });
    }

    actualizarContadorEquipos() {
        let contadorElement = document.getElementById('contador-equipos');
        if (!contadorElement) {
            contadorElement = document.createElement('div');
            contadorElement.id = 'contador-equipos';
            contadorElement.className = 'contador-equipos';
            document.querySelector('.equipos-section').insertBefore(contadorElement, document.getElementById('equipos-adicionales'));
        }
        contadorElement.textContent = `${this.contadorEquipos} equipo(s) registrado(s)`;
    }

    async generarRecibo(e) {
        e.preventDefault();
        
        if (!this.clienteSeleccionado) {
            Swal.fire({
                icon: 'warning',
                title: 'Selecciona un cliente',
                text: 'Debes seleccionar un cliente antes de generar el recibo',
                confirmButtonColor: '#ffc107'
            });
            return;
        }

        // Validar formulario de equipos
        if (!this.validarFormularioEquipos()) {
            Swal.fire({
                icon: 'warning',
                title: 'Formulario incompleto',
                text: 'Por favor completa todos los campos obligatorios',
                confirmButtonColor: '#ffc107'
            });
            return;
        }

        const boton = e.target.querySelector('.btn-enviar');
        const textoOriginal = boton.innerHTML;
        
        try {
            boton.disabled = true;
            boton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';

            const response = await fetch(e.target.action, {
                method: 'POST',
                body: new FormData(e.target),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Respuesta no es JSON:', text.substring(0, 200));
                throw new Error('Error inesperado del servidor');
            }

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Error al generar recibo');
            }

            if (data.success) {
                await Swal.fire({
                    icon: 'success',
                    title: '¡Recibo generado!',
                    html: `
                        <p>${data.message}</p>
                        ${data.numero_recibo ? `<p><strong>Número de recibo:</strong> ${data.numero_recibo}</p>` : ''}
                    `,
                    confirmButtonColor: '#28a745',
                    showConfirmButton: true,
                    timer: 3000, // ← Agregar esta línea
                    timerProgressBar: true // ← Opcional: barra de progreso
                });
                
                if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                } else {
                    // Recargar la página para limpiar el formulario
                    window.location.reload();
                }
            } else {
                throw new Error(data.error || 'Error al generar recibo');
            }

        } catch (error) {
            console.error('Error completo:', error);
            
            let mensajeError = error.message;
            if (error.message.includes('<!DOCTYPE') || error.message.includes('<html')) {
                mensajeError = 'Error del servidor. Por favor, intente nuevamente.';
            }
            
            await Swal.fire({
                icon: 'error',
                title: 'Error',
                text: mensajeError,
                confirmButtonColor: '#dc3545'
            });
        } finally {
            boton.disabled = false;
            boton.innerHTML = textoOriginal;
        }
    }

    validarFormularioEquipos() {
        let esValido = true;
        const equipos = document.querySelectorAll('.equipo-card');
        
        equipos.forEach((equipo, index) => {
            const camposRequeridos = equipo.querySelectorAll('[required]');
            camposRequeridos.forEach(campo => {
                if (!campo.value.trim()) {
                    campo.style.borderColor = '#dc3545';
                    esValido = false;
                    
                    // Remover el estilo cuando el usuario empiece a escribir
                    campo.addEventListener('input', function() {
                        this.style.borderColor = '';
                    }, { once: true });
                }
            });
            
            // Validar marca nueva si está visible
            const nuevaMarca = equipo.querySelector('.nueva-marca');
            if (nuevaMarca && nuevaMarca.style.display !== 'none') {
                const inputNuevaMarca = nuevaMarca.querySelector('input');
                if (!inputNuevaMarca.value.trim()) {
                    inputNuevaMarca.style.borderColor = '#dc3545';
                    esValido = false;
                    
                    inputNuevaMarca.addEventListener('input', function() {
                        this.style.borderColor = '';
                    }, { once: true });
                }
            }
        });
        
        return esValido;
    }

    // Utilidades
     escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    convertirAMayusculas(input) {
        input.value = input.value.toUpperCase();
    }
}

// Inicializar aplicación
document.addEventListener('DOMContentLoaded', () => {
    window.recepcionApp = new RecepcionEquipos();
  
});