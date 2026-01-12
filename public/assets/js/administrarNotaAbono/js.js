// ===== VARIABLES GLOBALES =====
let productosSeleccionados = [];
let timeoutBusqueda = null;
let timeoutResponsable = null;
const TIEMPO_ESPERA_BUSQUEDA = 500;
const TIEMPO_ESPERA_RESPONSABLE = 300;
let responsableSeleccionado = null;
let clienteIdActual = null;
let baseImponibleTotal = 0;
let ventaIdCancelar = null;
let ventaTicketCancelar = null;
let ventaIdDevolucion = null;
let productosDevolucion = [];
let ventaIdHistorial = null;
let ventaTicketHistorial = null;
let pagoMixtoHabilitado = false;
let diferenciaPago = 0;
let totalVentaActual = 0;


let regimenCliente = {
    id: null,
    nombre: '',
    iva: 0,
    isr: 0
};

//evento de click al agregar producto y seleccionar responsable
['click', 'touchstart', 'mousedown'].forEach(evt => {
    document.addEventListener(evt, function(e) {

        // --- Manejo de productos ---
        const btn = e.target.closest('.btn-agregar-producto');
        if (btn) {
            agregarProductoDesdeBoton(btn);
            return;
        }

        const item = e.target.closest('.item-producto');
        if (item) {
            const btnInterno = item.querySelector('.btn-agregar-producto');
            if (btnInterno) {
                agregarProductoDesdeBoton(btnInterno);
            }
        }

        // --- Manejo de responsables ---
        const responsableItem = e.target.closest('.item-responsable');
        if (responsableItem) {
            const id = responsableItem.dataset.id;
            const nombre = responsableItem.dataset.nombre;

            // Asignar el nombre al input visible
            const inputNombre = document.getElementById('responsableVenta');
            if (inputNombre) inputNombre.value = nombre;

            // Asignar el id al input hidden
            const inputId = document.getElementById('responsable_id');
            if (inputId) inputId.value = id;

            // Ocultar el dropdown de sugerencias
            const sugerencias = document.getElementById('sugerenciasResponsable');
            if (sugerencias) sugerencias.style.display = 'none';
        }

    }, { passive: true });
});


// ===== INICIALIZACIÓN =====
$(document).ready(function() {
   
    inicializarSistemaVentas();
});

document.addEventListener('DOMContentLoaded', function() {
 
    configurarEventosProductos();
});

//


function inicializarSistemaVentas() {
    
    // Obtener el clienteId actual
    clienteIdActual = obtenerClienteIdActual();
    
    
    // Obtener régimen del cliente
    if (clienteIdActual) {
        obtenerRegimenCliente(clienteIdActual);
    }

    inicializarEventListeners();
    calcularImpuestosYTotal(); // Cambiar a la nueva función
}

//

// ===== OBTENER CLIENTE ID ACTUAL =====
function obtenerClienteIdActual() {
    const modal = document.getElementById('modalRealizarVenta');
    if (modal) {
        const clienteId = modal.dataset.clienteId;
        return clienteId ? parseInt(clienteId) : null;
    }
    return null;
}
// Nueva función para obtener el ID de la nota de abono
function obtenerNotaAbonoIdActual() {
    const modal = document.getElementById('modalRealizarVenta');
    return modal ? modal.dataset.notaAbonoId : null;
}

//
// ===== OBTENER RÉGIMEN DEL CLIENTE =====
async function obtenerRegimenCliente(clienteId) {
    if (!clienteId) {
        console.warn('⚠️ No hay clienteId para obtener régimen');
        return;
    }
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const url = `/obtener-regimen-cliente?cliente_id=${encodeURIComponent(clienteId)}`;
        
      
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            regimenCliente = {
                id: data.regimen.id,
                nombre: data.regimen.nombre,
                iva: parseFloat(data.regimen.iva),
                isr: parseFloat(data.regimen.isr),
                tipo: data.regimen.tipo || 'fisica' // ← Asegurar que tenga tipo
            };
            
          
            
            // VERIFICAR SI ES PERSONA MORAL
            if (regimenCliente.tipo === 'moral') {
              
            }
            
            // Actualizar la interfaz con los porcentajes
            actualizarDisplayImpuestos();
            calcularImpuestosYTotal();
            
        } else {
            throw new Error(data.message || 'Error al obtener régimen');
        }
    } catch (error) {
        console.error('Error obteniendo régimen:', error);
        // Usar valores por defecto en caso de error
        regimenCliente = {
            id: null,
            nombre: 'No disponible',
            iva: 8.00,
            isr: 0.00,
            tipo: 'fisica' // ← Valor por defecto
        };
        actualizarDisplayImpuestos();
    }
}
//
// ===== ACTUALIZAR DISPLAY DE IMPUESTOS =====
function actualizarDisplayImpuestos() {
    // Actualizar porcentajes en la interfaz
    const tasaIvaElement = document.getElementById('tasaIvaPorcentaje');
    const tasaIsrElement = document.getElementById('tasaIsrPorcentaje');
    const regimenNombreElement = document.getElementById('regimenNombre');
    
    if (tasaIvaElement) {
        tasaIvaElement.textContent = regimenCliente.iva + '%';
    }
    
    if (tasaIsrElement) {
        tasaIsrElement.textContent = regimenCliente.isr + '%';
    }
    
    if (regimenNombreElement) {
        regimenNombreElement.textContent = regimenCliente.nombre;
    }
}

// ===== CÁLCULO DE IMPUESTOS =====
function calcularImpuestosYTotal() {

    // =====================================================
    // FUNCIONES DE REDONDEO
    // =====================================================

    const redondear6 = v =>
        Math.floor(parseFloat(v || 0) * 1e6 + 0.5) / 1e6;

    const redondearSAT = v =>
        Math.floor(redondear6(v) * 100 + 0.5) / 100;

    // =====================================================
    // VALIDACIÓN
    // =====================================================

     if (!Array.isArray(productosSeleccionados) || productosSeleccionados.length === 0) {
        actualizarTotalesEnModal(0, 0, 0, 0, 0, 0, 0);
        // IMPORTANTE: También verificar pago mixto cuando no hay productos
        mostrarOpcionPagoMixtoSimple(0, obtenerSaldoActualCliente());
        return;
    }


    // =====================================================
    // TASAS
    // =====================================================

    const tasaIVA = parseFloat(regimenCliente?.iva) || 0;
    const tasaISR = parseFloat(regimenCliente?.isr) || 0;
    const esPersonaMoral = tasaISR > 0;

    // =====================================================
    // CÁLCULO POR CONCEPTO (SAT REAL)
    // =====================================================

    let subtotal = 0;
    let totalConIVA = 0;

    productosSeleccionados.forEach(p => {
        const precioConIVA = parseFloat(p.precioConIVA) || 0;
        const cantidad = parseInt(p.cantidad) || 0;
        if (cantidad <= 0) return;

        totalConIVA += precioConIVA * cantidad;

        if (tasaIVA > 0) {
            const baseUnitaria6 = redondear6(
                precioConIVA / (1 + tasaIVA / 100)
            );
            subtotal += baseUnitaria6 * cantidad;
        } else {
            subtotal += precioConIVA * cantidad;
        }
    });

    subtotal = redondear6(subtotal);
    totalConIVA = redondear6(totalConIVA);

    // =====================================================
    // IMPUESTOS
    // =====================================================

    const iva = tasaIVA > 0
        ? redondear6(totalConIVA - subtotal)
        : 0;

    const isr = esPersonaMoral
        ? redondear6(subtotal * tasaISR / 100)
        : 0;

    const total = esPersonaMoral
        ? redondear6(subtotal + iva - isr)
        : redondear6(subtotal + iva);

    // =====================================================
    // MOSTRAR (SAT)
    // =====================================================

    const subtotalMostrar = redondearSAT(subtotal);
    const ivaMostrar = redondearSAT(iva);
    const isrMostrar = redondearSAT(isr);
    const totalMostrar = redondearSAT(total);
    totalVentaActual = parseFloat(totalMostrar.toFixed(2));
    const saldo = parseFloat(obtenerSaldoActualCliente() || 0);
    diferenciaPago = Math.max(0, parseFloat((totalVentaActual - saldo).toFixed(2)));
    const totalConIVAMostrar = redondearSAT(totalConIVA);

    const saldoAntes = obtenerSaldoActualCliente() || 0;
    const saldoDespues = redondearSAT(saldoAntes - total);

    actualizarTotalesEnModal(
        totalConIVAMostrar,
        subtotalMostrar,
        ivaMostrar,
        isrMostrar,
        totalMostrar,
        redondearSAT(saldoAntes),
        saldoDespues
    );

    
    // =====================================================
    // ¡¡¡ AQUÍ ESTÁ LO QUE FALTA !!!
    // =====================================================
    mostrarOpcionPagoMixtoSimple(totalMostrar, saldoAntes);
}


// === FUNCIÓN AUXILIAR: DEMOSTRACIÓN REDONDEO ===

//function calcularImpuestosYTotal() {
function obtenerSaldoActualCliente() {
    // Implementa esta función según tu lógica de negocio
    // Por ejemplo, podrías obtenerlo de un input hidden o via AJAX
    return parseFloat(document.getElementById('saldoActualCliente')?.value) || 0;
}
//
function actualizarTotalesEnModal(
    totalConIVA,
    subtotal,
    iva,
    isr,
    total,
    saldoAntes,
    saldoDespues
) {

    // =====================================================
    // FORMATEO ÚNICO PARA UI (NO CÁLCULO FISCAL)
    // =====================================================
    const formatCurrency = (valor) => {
        const num = parseFloat(valor) || 0;
        return '$' + num.toFixed(2);
    };

    // Asegurar valores numéricos (SIN RECALCULAR)
    subtotal = parseFloat(subtotal) || 0;
    iva = parseFloat(iva) || 0;
    isr = parseFloat(isr) || 0;
    total = parseFloat(total) || 0;
    saldoAntes = parseFloat(saldoAntes) || 0;
    saldoDespues = parseFloat(saldoDespues) || 0;
    totalConIVA = parseFloat(totalConIVA) || 0;

    // =====================================================
    // RÉGIMEN
    // =====================================================
    const tasaIVA = parseFloat(regimenCliente?.iva) || 0;
    const tasaISR = parseFloat(regimenCliente?.isr) || 0;
    const esPersonaMoral = tasaISR > 0;

    // =====================================================
    // MOSTRAR / OCULTAR ISR
    // =====================================================
    const rowISR = document.getElementById('rowISR');
    const badgeRestaISR = document.getElementById('badgeRestaISR');

    if (rowISR) {
        rowISR.style.display = esPersonaMoral && tasaISR > 0 ? 'flex' : 'none';
    }
    if (badgeRestaISR) {
        badgeRestaISR.style.display = esPersonaMoral && tasaISR > 0 ? 'inline' : 'none';
    }

    // =====================================================
    // ELEMENTOS
    // =====================================================
    const elementos = {
        subtotal: document.getElementById('modalSubtotal'),
        iva: document.getElementById('modalIva'),
        isr: document.getElementById('modalIsr'),
        total: document.getElementById('modalTotal'),
        saldoAntes: document.getElementById('modalSaldoAntes'),
        saldoDespues: document.getElementById('modalSaldoDespues'),
        infoTipoCliente: document.getElementById('infoTipoCliente'),
        infoIVA: document.getElementById('infoIVA'),
        infoISR: document.getElementById('infoISR'),
        formulaTotal: document.getElementById('formulaTotal')
    };

    // =====================================================
    // MOSTRAR VALORES
    // =====================================================
    if (elementos.subtotal) elementos.subtotal.textContent = formatCurrency(subtotal);
    if (elementos.iva) elementos.iva.textContent = formatCurrency(iva);
    if (elementos.total) elementos.total.textContent = formatCurrency(total);
    if (elementos.saldoAntes) elementos.saldoAntes.textContent = formatCurrency(saldoAntes);
    if (elementos.saldoDespues) elementos.saldoDespues.textContent = formatCurrency(saldoDespues);

    if (elementos.isr) {
        if (esPersonaMoral && isr > 0) {
            elementos.isr.textContent = `-${formatCurrency(isr)}`;
            elementos.isr.classList.add('text-danger', 'fw-bold');
            elementos.isr.classList.remove('text-muted');
        } else {
            elementos.isr.textContent = formatCurrency(0);
            elementos.isr.classList.add('text-muted');
            elementos.isr.classList.remove('text-danger', 'fw-bold');
        }
    }

    // =====================================================
    // TEXTOS INFORMATIVOS (COHERENTES CON CÁLCULO REAL)
    // =====================================================
    if (elementos.infoIVA) {
        elementos.infoIVA.textContent = `${tasaIVA}% incluido en el precio`;
    }

    if (elementos.infoISR) {
        elementos.infoISR.textContent = esPersonaMoral
            ? `${tasaISR}% aplicado sobre el subtotal`
            : 'No aplica';
    }

    if (elementos.formulaTotal) {
        elementos.formulaTotal.textContent = esPersonaMoral
            ? `$${subtotal.toFixed(2)} + $${iva.toFixed(2)} - $${isr.toFixed(2)}`
            : `$${subtotal.toFixed(2)} + $${iva.toFixed(2)}`;
    }

    // =====================================================
    // TIPO DE CLIENTE
    // =====================================================
    if (elementos.infoTipoCliente) {
        if (esPersonaMoral) {
            elementos.infoTipoCliente.textContent = 'Persona Moral';
            elementos.infoTipoCliente.className = 'ms-2 badge bg-warning';
        } else {
            elementos.infoTipoCliente.textContent = 'Persona Física';
            elementos.infoTipoCliente.className = 'ms-2 badge bg-info';
        }
    }

    // =====================================================
    // TOTAL CON IVA (SOLO MOSTRAR)
    // =====================================================
    const rowTotalConIVA = document.getElementById('rowTotalConIVA');
    const modalTotalConIVA = document.getElementById('modalTotalConIVA');

    if (rowTotalConIVA && modalTotalConIVA) {
        modalTotalConIVA.textContent = formatCurrency(totalConIVA);
        rowTotalConIVA.style.display = 'flex';
    }

    // =====================================================
    // LOG FINAL (OPCIONAL)
    // =====================================================
    
}


// ===== CONFIGURACIÓN DE EVENT LISTENERS =====
function inicializarEventListeners() {
   
    
    // Búsqueda de productos (existente)
    const btnBuscar = document.getElementById('btnBuscar');
    if (btnBuscar) {
        btnBuscar.addEventListener('click', function() {
          
            buscarProductos();
        });
    }
    //
     // Confirmar venta final
    const btnConfirmarFinal = document.getElementById('btnConfirmarVentaFinal');
    if (btnConfirmarFinal) {
        btnConfirmarFinal.addEventListener('click', confirmarVentaFinal);
    }
    
    const buscarProductoInput = document.getElementById('buscarProducto');
    if (buscarProductoInput) {
        buscarProductoInput.addEventListener('input', function(e) {
            manejarBusquedaEnTiempoReal(e.target.value);
        });
        
        buscarProductoInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
               
                buscarProductos();
            }
        });
        
        buscarProductoInput.addEventListener('blur', function() {
            setTimeout(() => {
                limpiarBusqueda();
            }, 200);
        });
    }
    
    // BÚSQUEDA DE RESPONSABLES EN TIEMPO REAL
    const responsableInput = document.getElementById('responsableVenta');
    if (responsableInput) {
        responsableInput.addEventListener('input', function(e) {
            manejarBusquedaResponsable(e.target.value);
            validarResponsable(); // ✅ Validar en cada cambio
        });
        
        responsableInput.addEventListener('focus', function() {
            if (this.value.length >= 2) {
                buscarResponsables(this.value);
            }
        });
        
        responsableInput.addEventListener('blur', function() {
            setTimeout(() => {
                ocultarSugerencias();
                validarResponsable(); // ✅ Validar al perder foco
            }, 200);
        });
        
        // Validar también con Enter
        responsableInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                validarResponsable();
            }
        });
    }
    
    // Botón registrar nuevo responsable
    const btnRegistrar = document.getElementById('btnRegistrarResponsable');
    if (btnRegistrar) {
        btnRegistrar.addEventListener('click', registrarNuevoResponsable);
    }
    
    // Confirmar venta
    const btnConfirmar = document.getElementById('btnConfirmarVentaModal');
    if (btnConfirmar) {
        btnConfirmar.addEventListener('click', confirmarVenta);
    }
    
    // Validar cuando se agregan/eliminan productos
    document.addEventListener('productosCambiados', function() {
        validarResponsable();
    });
}


// ===== EVENT DELEGATION PARA PRODUCTOS (SOLUCIÓN TOUCHPAD) =====
function configurarEventosProductos() {
    // Usar event delegation para evitar problemas con touchpad
    document.addEventListener('click', function(e) {
        // Manejar clic en botones de agregar producto
        if (e.target.closest('.btn-agregar-producto')) {
            const btn = e.target.closest('.btn-agregar-producto');
            agregarProductoDesdeBoton(btn);
            return;
        }
        
        // Manejar clic en items de producto (área completa)
        if (e.target.closest('.item-producto')) {
            const item = e.target.closest('.item-producto');
            const btn = item.querySelector('.btn-agregar-producto');
            if (btn) {
                agregarProductoDesdeBoton(btn);
            }
            return;
        }
    });
}

function agregarProductoDesdeBoton(btn) {
    const id = btn.getAttribute('data-id');
    const nombre = btn.getAttribute('data-nombre');
    const precio = parseFloat(btn.getAttribute('data-precio')); // PRECIO CON IVA
    const stock = parseInt(btn.getAttribute('data-stock'));
    
    // Validar stock
    if (stock <= 0) {
        mostrarAlerta('Este producto no tiene stock disponible', 'warning');
        return;
    }
    
    const productoEnCarrito = productosSeleccionados.find(p => p.id == id);
    const cantidadEnCarrito = productoEnCarrito ? productoEnCarrito.cantidad : 0;
    
    if (cantidadEnCarrito >= stock) {
        mostrarAlerta(`No hay suficiente stock. Stock disponible: ${stock}`, 'warning');
        return;
    }
    
    // IMPORTANTE: Enviar precio CON IVA
    agregarProductoModal(id, nombre, precio, stock);
}
// ===== BÚSQUEDA EN TIEMPO REAL =====
function manejarBusquedaEnTiempoReal(query) {
    const queryLimpio = query.trim();
    
    if (timeoutBusqueda) {
        clearTimeout(timeoutBusqueda);
    }
    
    if (queryLimpio === '') {
        limpiarBusqueda();
        return;
    }
    
    if (queryLimpio.length === 1) {
        mostrarMensajeMinimoCaracteres();
        return;
    }
    
    timeoutBusqueda = setTimeout(() => {
     
        buscarProductos();
    }, TIEMPO_ESPERA_BUSQUEDA);
}

function mostrarMensajeMinimoCaracteres() {
    const lista = document.getElementById('listaProductos');
    const contenedor = document.getElementById('resultadosBusqueda');
    
    if (lista && contenedor) {
        lista.innerHTML = `
            <div class="list-group-item text-center text-muted py-3">
                <i class="fas fa-info-circle fa-2x mb-2"></i>
                <p>Ingresa al menos 2 caracteres</p>
            </div>
        `;
        contenedor.style.display = 'block';
    }
}

// ===== BÚSQUEDA DE PRODUCTOS =====
async function buscarProductos() {
    const query = document.getElementById('buscarProducto').value.trim();
    

    if (query.length < 2) {
        mostrarMensajeMinimoCaracteres();
        return;
    }

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const url = `/buscar-productos-NotaAbono?query=${encodeURIComponent(query)}`;
        
        mostrarLoadingBusqueda();
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
          
            mostrarResultadosBusqueda(data.productos);
        } else {
            throw new Error(data.message || 'Error en la búsqueda');
        }
    } catch (error) {
        console.error('💥 Error en búsqueda:', error);
        mostrarErrorBusqueda(error.message);
    }
}



function mostrarLoadingBusqueda() {
    const lista = document.getElementById('listaProductos');
    const contenedor = document.getElementById('resultadosBusqueda');
    
    if (lista && contenedor) {
        lista.innerHTML = `
            <div class="list-group-item text-center py-3">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Buscando...</span>
                </div>
                <p class="mt-2 text-muted mb-0">Buscando productos...</p>
            </div>
        `;
        contenedor.style.display = 'block';
    }
}

function mostrarErrorBusqueda(mensaje) {
    const lista = document.getElementById('listaProductos');
    const contenedor = document.getElementById('resultadosBusqueda');
    
    if (lista && contenedor) {
        lista.innerHTML = `
            <div class="list-group-item text-center text-danger py-3">
                <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                <p class="mb-1">Error en la búsqueda</p>
                <small>${mensaje}</small>
            </div>
        `;
        contenedor.style.display = 'block';
    }
}

// ===== MOSTRAR RESULTADOS =====
// ===== MOSTRAR RESULTADOS =====
function mostrarResultadosBusqueda(productos) {
  
    
    const lista = document.getElementById('listaProductos');
    const contenedor = document.getElementById('resultadosBusqueda');
    
    if (!lista || !contenedor) return;
    
    lista.innerHTML = '';
    
    if (productos.length === 0) {
        lista.innerHTML = `
            <div class="list-group-item text-center text-muted py-3">
                <i class="fas fa-search fa-2x mb-2"></i>
                <p class="mb-1">No se encontraron productos</p>
                <small>Intenta con otros términos de búsqueda</small>
            </div>
        `;
    } else {
        productos.forEach((producto) => {
            const tieneStock = producto.stock > 0;
            const claseItem = tieneStock ? 'list-group-item list-group-item-action item-producto' : 'list-group-item text-muted';
            const estiloCursor = tieneStock ? 'pointer' : 'not-allowed';
            
            // Construir la descripción completa con marca y modelo
            let descripcionCompleta = escapeHtml(producto.nombre);
            
            // Agregar marca si existe
            if (producto.marca && producto.marca.trim() !== '') {
                descripcionCompleta += ` ${escapeHtml(producto.marca.trim())}`;
            }
            
            // Agregar modelo si existe
            if (producto.modelo && producto.modelo.trim() !== '') {
                descripcionCompleta += ` ${escapeHtml(producto.modelo.trim())}`;
            }
            
            // Datos para el botón (incluir marca y modelo)
            const datosProducto = {
                id: producto.id,
                nombre: descripcionCompleta, // Usar el nombre completo
                nombre_base: escapeHtml(producto.nombre), // Nombre original por separado
                marca: producto.marca ? escapeHtml(producto.marca.trim()) : '',
                modelo: producto.modelo ? escapeHtml(producto.modelo.trim()) : '',
                precio: producto.precio,
                stock: producto.stock,
                codigo_barra: producto.codigo_barra
            };
            
            const botonHTML = tieneStock ? 
                `<button class="btn btn-sm btn-outline-primary btn-agregar-producto" 
                        data-id="${producto.id}" 
                        data-nombre="${datosProducto.nombre}" 
                        data-nombre-base="${datosProducto.nombre_base}"
                        data-marca="${datosProducto.marca}"
                        data-modelo="${datosProducto.modelo}"
                        data-precio="${producto.precio}"
                        data-stock="${producto.stock}"
                        data-codigo="${producto.codigo_barra}">
                    <i class="fas fa-plus me-1"></i>Agregar
                </button>` :
                `<button class="btn btn-sm btn-outline-secondary" disabled>
                    <i class="fas fa-times me-1"></i>Sin stock
                </button>`;

            const item = document.createElement('div');
            item.className = claseItem;
            item.style.cursor = estiloCursor;
            item.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="mb-1 fw-semibold ${!tieneStock ? 'text-muted' : ''}">
                            ${descripcionCompleta}
                        </h6>
                        <small class="${!tieneStock ? 'text-danger' : 'text-muted'}">
                            Código: ${producto.codigo_barra} | 
                            Stock: ${producto.stock}
                            ${producto.marca || producto.modelo ? '<br>' : ''}
                            ${producto.marca ? `<span class="badge bg-info me-1">${escapeHtml(producto.marca)}</span>` : ''}
                            ${producto.modelo ? `<span class="badge bg-secondary">${escapeHtml(producto.modelo)}</span>` : ''}
                            ${!tieneStock ? '<br><strong class="text-danger">AGOTADO</strong>' : ''}
                        </small>
                    </div>
                    <div class="text-end ms-3">
                        <div class="fw-bold ${!tieneStock ? 'text-muted' : 'text-primary'} mb-1">
                            $${parseFloat(producto.precio).toFixed(2)}
                        </div>
                        ${botonHTML}
                    </div>
                </div>
            `;
            
            // Solo agregar evento click si tiene stock
            if (tieneStock) {
                item.addEventListener('click', function(e) {
                    // Evitar que se active al hacer click en áreas que no son el botón
                    if (!e.target.closest('.btn-agregar-producto')) {
                        const btn = this.querySelector('.btn-agregar-producto');
                        if (btn) {
                            agregarProductoDesdeBoton(btn);
                        }
                    }
                });
            }
            
            lista.appendChild(item);
        });
    }
    
    contenedor.style.display = 'block';
}
//


// ===== AGREGAR PRODUCTO =====
// ===== FUNCIONES ACTUALIZADAS PARA USAR NUEVO CÁLCULO =====
function agregarProductoModal(id, nombre, precio, stock) {
      
    // IMPORTANTE: Asumiendo que 'precio' es CON IVA
    const precioConIVA = parseFloat(precio);
    
    const productoExistente = productosSeleccionados.find(p => p.id == id);
    
    if (productoExistente) {
        if (productoExistente.cantidad >= stock) {
            mostrarAlerta(`No hay suficiente stock. Stock disponible: ${stock}`, 'warning');
            return;
        }
        productoExistente.cantidad += 1;
        // Guardar subtotal con IVA
        productoExistente.subtotalConIVA = productoExistente.precioConIVA * productoExistente.cantidad;
    } else {
        productosSeleccionados.push({
            id: id,
            nombre: nombre,
            precioConIVA: precioConIVA,  // Precio unitario CON IVA
            cantidad: 1,
            subtotalConIVA: precioConIVA, // Total para este producto CON IVA
            stock: stock
        });
    }
    
    actualizarListaProductosModal();
    calcularImpuestosYTotal();
    mostrarOpcionPagoMixtoSimple();
    limpiarBusqueda();
    
    document.dispatchEvent(new CustomEvent('productosCambiados'));
}
    
// ===== GESTIÓN DEL CARRITO =====
function actualizarListaProductosModal() {
    const contenedor = document.getElementById('listaProductosSeleccionados');
    if (!contenedor) return;
    
    if (productosSeleccionados.length === 0) {
        contenedor.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="fas fa-cart-plus fa-2x mb-2"></i>
                <p>No hay productos agregados</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    productosSeleccionados.forEach((producto, index) => {
        // Asegurarnos de que tenemos precioConIVA
        const precioConIVA = producto.precioConIVA || producto.precio || 0;
        const subtotalConIVA = producto.subtotalConIVA || (precioConIVA * producto.cantidad);
        
        // Calcular precio sin IVA para mostrar info
        const tasaIVA = regimenCliente.iva || 0;
        const precioSinIVA = tasaIVA > 0 
            ? precioConIVA / (1 + (tasaIVA / 100))
            : precioConIVA;
        
        const stockDisponible = producto.stock || 0;
        const puedeIncrementar = producto.cantidad < stockDisponible;
        
        html += `
            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                <div class="flex-grow-1">
                    <h6 class="mb-1 fw-semibold">${escapeHtml(producto.nombre)}</h6>
                    <small class="text-muted">
                        $${precioConIVA.toFixed(2)} c/u (IVA incl.) | 
                        Cantidad: ${producto.cantidad} | 
                        Stock: ${stockDisponible}
                    </small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="input-group input-group-sm" style="width: 120px;">
                        <button class="btn btn-outline-secondary" type="button" onclick="modificarCantidad(${index}, -1)">-</button>
                        <input type="number" class="form-control text-center" 
                               value="${producto.cantidad}" 
                               min="1" 
                               max="${stockDisponible}"
                               onchange="actualizarCantidadManual(${index}, this.value)">
                        <button class="btn btn-outline-secondary ${!puedeIncrementar ? 'disabled' : ''}" 
                                type="button" 
                                onclick="modificarCantidad(${index}, 1)"
                                ${!puedeIncrementar ? 'disabled' : ''}>+</button>
                    </div>
                    <span class="fw-semibold text-primary" style="min-width: 80px; text-align: right;">
                        $${subtotalConIVA.toFixed(2)}
                    </span>
                    <button class="btn btn-sm btn-outline-danger" onclick="eliminarProductoModal(${index})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    });
    
    contenedor.innerHTML = html;
}
//

// ===== FUNCIONES GLOBALES =====
window.modificarCantidad = function(index, cambio) {
    const producto = productosSeleccionados[index];
    const nuevaCantidad = producto.cantidad + cambio;
    const stockDisponible = producto.stock || 0;
    
    // ✅ Validar límites de stock
    if (nuevaCantidad < 1) {
        return; // No puede ser menor a 1
    }
    
    if (nuevaCantidad > stockDisponible) {
        mostrarAlerta(`No hay suficiente stock. Stock disponible: ${stockDisponible}`, 'warning');
        return;
    }
    
    producto.cantidad = nuevaCantidad;
    producto.subtotal = producto.precio * nuevaCantidad;
    actualizarListaProductosModal();
    calcularImpuestosYTotal();
};
//
window.actualizarCantidadManual = function(index, nuevaCantidad) {
    const producto = productosSeleccionados[index];
    const cantidad = parseInt(nuevaCantidad) || 1;
    const stockDisponible = producto.stock || 0;
    
    // ✅ Validar límites de stock
    if (cantidad < 1) {
        mostrarAlerta('La cantidad debe ser al menos 1', 'warning');
        actualizarListaProductosModal(); // Restaurar valor anterior
        return;
    }
    
    if (cantidad > stockDisponible) {
        mostrarAlerta(`No hay suficiente stock. Stock disponible: ${stockDisponible}`, 'warning');
        actualizarListaProductosModal(); // Restaurar valor anterior
        return;
    }
    
    producto.cantidad = cantidad;
    producto.subtotal = producto.precio * cantidad;
    actualizarListaProductosModal();
    calcularImpuestosYTotal();
};
//
window.eliminarProductoModal = function(index) {
    productosSeleccionados.splice(index, 1);
    actualizarListaProductosModal();
    calcularImpuestosYTotal();
    
    // ✅ Disparar evento personalizado para validación
    document.dispatchEvent(new CustomEvent('productosCambiados'));
};

// ===== TOTALES =====
function actualizarTotalesModal() {
    const total = productosSeleccionados.reduce((sum, producto) => sum + producto.subtotal, 0);
    document.getElementById('modalTotal').textContent = `$${total.toFixed(2)}`;
}

// ===== BÚSQUEDA DE RESPONSABLES =====
function manejarBusquedaResponsable(query) {
    const queryLimpio = query.trim();
    
    // Limpiar timeout anterior
    if (timeoutResponsable) {
        clearTimeout(timeoutResponsable);
    }
    
    // Actualizar estado
    actualizarEstadoResponsable('buscando');
    ocultarBotonRegistrar();
    responsableSeleccionado = null;
    
    if (queryLimpio === '') {
        actualizarEstadoResponsable('inicial');
        ocultarSugerencias();
        return;
    }
    
    if (queryLimpio.length === 1) {
        actualizarEstadoResponsable('minimo');
        return;
    }
    
    // Buscar después de un delay
    timeoutResponsable = setTimeout(() => {
        buscarResponsables(queryLimpio);
    }, TIEMPO_ESPERA_RESPONSABLE);
}

//
function validarResponsable() {
    const responsableId = document.getElementById('responsable_id').value;
    const responsableNombre = document.getElementById('responsableVenta').value.trim();
    const inputElement = document.getElementById('responsableVenta');
    
    // Validar que tenga un nombre y que esté confirmado (tenga ID o sea nuevo registrado)
    const esValido = responsableNombre.length > 0 && 
                    (responsableId || responsableSeleccionado);
    
    // Aplicar estilos de validación
    if (esValido) {
        inputElement.classList.remove('is-invalid');
        inputElement.classList.add('is-valid');
    } else {
        inputElement.classList.remove('is-valid');
        if (responsableNombre.length > 0) {
            inputElement.classList.add('is-invalid');
        } else {
            inputElement.classList.remove('is-invalid');
        }
    }
    
    // Habilitar/deshabilitar botón de confirmar venta
    const btnConfirmar = document.getElementById('btnConfirmarVentaModal');
    if (btnConfirmar) {
        btnConfirmar.disabled = !esValido || productosSeleccionados.length === 0;
    }
    
    return esValido;
}

// ===== BÚSQUEDA ASÍNCRONA DE RESPONSABLES =====

async function buscarResponsables(query) {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const url = `/buscar-responsables?cliente_id=${clienteIdActual}&query=${encodeURIComponent(query)}`;

        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
        
        const data = await response.json();
        
        if (data.success) {
            mostrarSugerenciasResponsables(data.responsables, query);
            
            if (data.responsables.length === 0) {
                mostrarBotonRegistrar(query);
                actualizarEstadoResponsable('no_encontrado');
            } else {
                actualizarEstadoResponsable('encontrados', data.responsables.length);
            }
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('💥 Error buscando responsables:', error);
        actualizarEstadoResponsable('error');
    }
}
function mostrarSugerenciasResponsables(responsables, query) {
    const contenedor = document.getElementById('sugerenciasResponsable');
    const input = document.getElementById('responsableVenta');
    
    contenedor.innerHTML = '';
    
    if (responsables.length === 0) {
        ocultarSugerencias();
        return;
    }
    
    responsables.forEach(responsable => {
        const item = document.createElement('button');
        item.type = 'button';
        item.className = 'dropdown-item';
        item.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <span>${responsable.nombre}</span>
                <small class="text-muted">${responsable.ventas_nota_abono_count || 0} ventas</small>
            </div>
        `;
        
        item.addEventListener('click', function() {
            seleccionarResponsable(responsable);
        });
        
        contenedor.appendChild(item);
    });
    
    // Posicionar y mostrar el dropdown
    contenedor.style.display = 'block';
    contenedor.style.width = input.offsetWidth + 'px';
    contenedor.style.top = (input.offsetTop + input.offsetHeight) + 'px';
    contenedor.style.left = input.offsetLeft + 'px';
}
//
function seleccionarResponsable(responsable) {
  
    
    // Actualizar inputs
    document.getElementById('responsableVenta').value = responsable.nombre;
    document.getElementById('responsable_id').value = responsable.id;
    
    // Actualizar variable global
    responsableSeleccionado = {
        id: responsable.id,
        nombre: responsable.nombre
    };
    
    // Ocultar sugerencias
    const sugerencias = document.getElementById('sugerenciasResponsable');
    if (sugerencias) sugerencias.style.display = "none";
    
    // Actualizar estado y validar
    actualizarEstadoResponsable('seleccionado', responsable.nombre);
    validarResponsable();
  
}
// ===== MOSTRAR / OCULTAR BOTÓN REGISTRAR =====
function mostrarBotonRegistrar(nombre) {
    const botonContainer = document.getElementById('botonRegistrarResponsable');
    const boton = document.getElementById('btnRegistrarResponsable');
    
    boton.dataset.nombre = nombre;
    botonContainer.style.display = 'block';
}

function ocultarBotonRegistrar() {
    document.getElementById('botonRegistrarResponsable').style.display = 'none';
}

function ocultarSugerencias() {
    document.getElementById('sugerenciasResponsable').style.display = 'none';
}

// ===== REGISTRAR NUEVO RESPONSABLE =====
async function registrarNuevoResponsable() {
    const boton = document.getElementById('btnRegistrarResponsable');
    const nombre = boton.dataset.nombre || document.getElementById('responsableVenta').value.trim();
    
    if (!nombre) {
        mostrarAlerta('Por favor ingresa un nombre para el responsable', 'warning');
        return;
    }
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const url = `/registrar-responsable`;
        
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken || ''
            },
            body: JSON.stringify({
                cliente_id: clienteIdActual,
                nombre: nombre
            })
        });
        
        if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
        
        const data = await response.json();
        
        if (data.success) {
            // Seleccionar el nuevo responsable y marcar como válido
            seleccionarResponsable(data.responsable);
            mostrarAlerta('Responsable registrado exitosamente', 'success');
            
            // Validar después del registro
            validarResponsable();
        } else {
            if (data.message.includes('ya existe')) {
                actualizarEstadoResponsable('duplicado');
                mostrarAlerta(data.message, 'warning');
            } else {
                throw new Error(data.message);
            }
        }
    } catch (error) {
        console.error('💥 Error registrando responsable:', error);
        mostrarAlerta('Error al registrar responsable: ' + error.message, 'error');
    }
}


// ===== ACTUALIZAR ESTADO DEL RESPONSABLE =====
function actualizarEstadoResponsable(estado, datos = null) {
    const elementoEstado = document.getElementById('estadoResponsable');
    
    switch(estado) {
        case 'inicial':
            elementoEstado.innerHTML = '<i class="fas fa-info-circle me-1"></i>Ingresa el nombre de quien recibe los productos';
            elementoEstado.className = 'text-muted';
            break;
            
        case 'minimo':
            elementoEstado.innerHTML = '<i class="fas fa-info-circle me-1"></i>Ingresa al menos 2 caracteres';
            elementoEstado.className = 'text-info';
            break;
            
        case 'buscando':
            elementoEstado.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Buscando responsables...';
            elementoEstado.className = 'text-info';
            break;
            
        case 'encontrados':
            elementoEstado.innerHTML = `<i class="fas fa-check me-1"></i>${datos} responsable(s) encontrado(s) - Selecciona uno`;
            elementoEstado.className = 'text-success';
            break;
            
        case 'no_encontrado':
            elementoEstado.innerHTML = '<i class="fas fa-search me-1"></i>No se encontraron responsables - Puedes registrar uno nuevo';
            elementoEstado.className = 'text-warning';
            break;
            
        case 'seleccionado':
            elementoEstado.innerHTML = `<i class="fas fa-user-check me-1"></i>Responsable seleccionado: ${datos}`;
            elementoEstado.className = 'text-success';
            break;
            
        case 'duplicado':
            elementoEstado.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Este responsable ya está registrado';
            elementoEstado.className = 'text-warning';
            break;
            
        case 'error':
            elementoEstado.innerHTML = '<i class="fas fa-exclamation-circle me-1"></i>Error en la búsqueda';
            elementoEstado.className = 'text-danger';
            break;
    }
}

// ===== CONFIRMAR VENTA ACTUALIZADO =====
// ===== CONFIRMAR VENTA ACTUALIZADO =====
async function confirmarVenta() {
    const checkPagoMixto = document.getElementById('habilitarPagoMixto');
const usaPagoMixto = checkPagoMixto?.checked || false;

if (usaPagoMixto) {
    const selectTipoPago = document.getElementById('tipoPagoCierre');
    const tipoPagoCierre = selectTipoPago?.value || '';

    if (!tipoPagoCierre) {

        // 🔴 Marcar select en rojo
        if (selectTipoPago) {
            selectTipoPago.classList.add('is-invalid');
            selectTipoPago.focus();
        }

        mostrarAlerta(
            'Debe seleccionar el tipo de pago (efectivo o transferencia) para completar el pago mixto.',
            'warning'
        );

        return; // ⛔ NO AVANZA
    }
}

    // Validar responsable antes de continuar
    if (!validarResponsable()) {
        mostrarAlerta('Por favor selecciona o registra un responsable válido', 'warning');
        document.getElementById('responsableVenta').focus();
        return;
    }
    
    // Validar stock
    const productosSinStock = productosSeleccionados.filter(producto => {
        const stockDisponible = producto.stock || 0;
        return producto.cantidad > stockDisponible;
    });
    
    if (productosSinStock.length > 0) {
        const nombresProductos = productosSinStock.map(p => p.nombre).join(', ');
        mostrarAlerta(`Los siguientes productos no tienen suficiente stock: ${nombresProductos}`, 'error');
        return;
    }
    
    // === Calcular total actual ===
    calcularImpuestosYTotal();
    const totalVenta = totalVentaActual;
    const saldoActual = parseFloat(obtenerSaldoActualCliente() || 0);
    const diferencia = diferenciaPago;
    
    // === VERIFICAR PAGO MIXTO ===
    const pagoMixtoCheck = document.getElementById('habilitarPagoMixto');
    const pagoEfectivoInput = document.getElementById('pagoEfectivo');
    
    // Si el total es mayor al saldo
    if (totalVenta > saldoActual) {
    if (!pagoMixtoCheck?.checked) {
        mostrarAlerta(
            `Saldo insuficiente.\nSaldo: $${saldoActual.toFixed(2)}\nTotal: $${totalVenta.toFixed(2)}\nDiferencia: $${diferencia.toFixed(2)}`,
            'error'
        );
        return;
    }

    const pagoEfectivo = parseFloat(pagoEfectivoInput?.value || 0);

    if (isNaN(pagoEfectivo) || pagoEfectivo <= 0) {
        mostrarAlerta('Ingrese un monto válido para la diferencia', 'warning');
        return;
    }

    if (parseFloat(pagoEfectivo.toFixed(2)) < diferencia) {
        mostrarAlerta(
            `El pago mínimo debe ser $${diferencia.toFixed(2)}`,
            'warning'
        );
        return;
    }
}

    
    // Si pasó todas las validaciones, mostrar modal de confirmación
    mostrarModalConfirmacion();
}

//
// ===== MOSTRAR MODAL DE CONFIRMACIÓN =====
// ===== MOSTRAR MODAL DE CONFIRMACIÓN =====
function mostrarModalConfirmacion() {

    // === FUNCIONES DE REDONDEO SAT ===
    const redondearSAT2 = (valor) => {
        if (isNaN(valor) || valor === null || valor === undefined) return 0;
        return Math.round((parseFloat(valor) + Number.EPSILON) * 100) / 100;
    };

    // Asegurar que los totales ya estén calculados
    calcularImpuestosYTotal();

    // === FUENTE ÚNICA DE VERDAD ===
    const total = parseFloat(totalVentaActual);
    const saldoActual = parseFloat(obtenerSaldoActualCliente() || 0);
    const saldoDespues = parseFloat((saldoActual - total).toFixed(2));

    const tasaIVA = parseFloat(regimenCliente.iva) || 0;
    const tasaISR = parseFloat(regimenCliente.isr) || 0;
    const esPersonaMoral = tasaISR > 0;

    // Tomar valores YA calculados (solo visuales)
    const subtotal = parseFloat(
        document.getElementById('modalSubtotal')?.textContent.replace(/[^0-9.-]/g, '') || 0
    );

    const iva = parseFloat(
        document.getElementById('modalIva')?.textContent.replace(/[^0-9.-]/g, '') || 0
    );

    const isr = parseFloat(
        document.getElementById('modalIsr')?.textContent.replace(/[^0-9.-]/g, '') || 0
    );

    // =====================================================
    // PAGO MIXTO
    // =====================================================
    const pagoMixtoCheck = document.getElementById('habilitarPagoMixto');
    const usaPagoMixto = pagoMixtoCheck?.checked || false;
    let datosPagoMixto = null;

    if (usaPagoMixto) {
        const pagoEfectivo = parseFloat(
            document.getElementById('pagoEfectivo')?.value || 0
        );

        const observacionesPago =
            document.getElementById('observacionesPagoMixto')?.value || '';

        datosPagoMixto = {
            habilitado: true,
            pago_saldo: Math.min(saldoActual, total),
            pago_efectivo: redondearSAT2(pagoEfectivo),
            observaciones:
                observacionesPago ||
                `Cliente pagó $${redondearSAT2(pagoEfectivo).toFixed(2)} en efectivo por diferencia de saldo`
        };
    }

    // =====================================================
    // HELPER PARA TEXTO
    // =====================================================
    const setTextContent = (id, value) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent =
            typeof value === 'number'
                ? `$${redondearSAT2(value).toFixed(2)}`
                : value;
    };

    // Responsable
    setTextContent(
        'confirmarResponsableNombre',
        document.getElementById('responsableVenta')?.value || 'No asignado'
    );

    // Totales
    setTextContent(
        'confirmarTotalProductos',
        productosSeleccionados.reduce((s, p) => s + p.cantidad, 0)
    );

    setTextContent('confirmarSubtotal', subtotal);
    setTextContent('confirmarIva', iva);
    setTextContent('confirmarIsr', isr);
    setTextContent('confirmarTotal', total);
    setTextContent('confirmarSaldoActual', saldoActual);
    setTextContent('confirmarSaldoDespues', saldoDespues);
    setTextContent('confirmarTasaIva', `${redondearSAT2(tasaIVA)}%`);
    setTextContent('confirmarTasaIsr', `${redondearSAT2(tasaISR)}%`);
    setTextContent('confirmarRegimenNombre', regimenCliente.nombre || 'No especificado');

    // =====================================================
    // INFO TIPO CLIENTE / PAGO MIXTO
    // =====================================================
    const infoTipoModal = document.getElementById('confirmarInfoTipoCliente');
    if (infoTipoModal) {
        if (usaPagoMixto) {
            infoTipoModal.innerHTML = `
                <div class="alert alert-warning mb-3">
                    <strong>PAGO MIXTO ACTIVADO</strong><br>
                    <small>
                        Saldo: $${saldoActual.toFixed(2)}<br>
                        Efectivo: $${datosPagoMixto.pago_efectivo.toFixed(2)}<br>
                        Total: $${total.toFixed(2)}
                    </small>
                </div>`;
        } else if (esPersonaMoral) {
            infoTipoModal.innerHTML = `
                <div class="alert alert-warning mb-3">
                    <strong>PERSONA MORAL</strong><br>
                    <small>ISR aplicado: $${redondearSAT2(isr).toFixed(2)}</small>
                </div>`;
        } else {
            infoTipoModal.innerHTML = `
                <div class="alert alert-info mb-3">
                    <strong>PERSONA FÍSICA / PÚBLICO GENERAL</strong><br>
                    <small>Sin ISR aplicado</small>
                </div>`;
        }
    }

    // =====================================================
    // ADVERTENCIA DE SALDO
    // =====================================================
    const advertenciaSaldo = document.getElementById('confirmarAdvertenciaSaldo');
    const btnConfirmar = document.getElementById('btnConfirmarVentaFinal');

    if (advertenciaSaldo) {
        if (usaPagoMixto) {
            advertenciaSaldo.style.display = 'block';
            advertenciaSaldo.innerHTML = `
                <div class="alert alert-info">
                    Pago mixto correcto. Total cubierto.
                </div>`;
            btnConfirmar.disabled = false;
        } else if (total > saldoActual) {
            advertenciaSaldo.style.display = 'block';
            advertenciaSaldo.innerHTML = `
                <div class="alert alert-danger">
                    Saldo insuficiente.<br>
                    Diferencia: $${redondearSAT2(total - saldoActual).toFixed(2)}
                </div>`;
            btnConfirmar.disabled = true;
        } else {
            advertenciaSaldo.style.display = 'none';
            btnConfirmar.disabled = false;
        }
    }

    // Guardar datos de pago mixto
    if (btnConfirmar) {
        btnConfirmar.dataset.pagoMixto = datosPagoMixto
            ? JSON.stringify(datosPagoMixto)
            : '';
    }

    // Mostrar modal
    const modal = new bootstrap.Modal(
        document.getElementById('modalConfirmarVenta')
    );
    modal.show();
}

//
// ===== FUNCIÓN DE VALIDACIÓN NUMÉRICA =====
function validarNumero(valor, valorPorDefecto = 0) {
    // Convertir a número si es string
    if (typeof valor === 'string') {
        valor = parseFloat(valor);
    }
    
    // Verificar si es un número válido
    if (valor === undefined || valor === null || isNaN(valor)) {
        console.warn('Valor inválido detectado, usando valor por defecto:', valorPorDefecto);
        return valorPorDefecto;
    }
    
    return valor;
}
//

//
// ===== VALIDAR SALDO SUFICIENTE =====
function validarSaldoSuficiente(totalVenta) {
    const saldoActual = obtenerSaldoActualCliente();
    const tasaIVA = regimenCliente.iva / 100;
    const tasaISR = regimenCliente.isr / 100;
    const iva = totalVenta * tasaIVA;
    const isr = totalVenta * tasaISR;
    const totalConImpuestos = totalVenta + iva - isr;
    
    return {
        suficiente: totalConImpuestos <= saldoActual,
        saldoActual: saldoActual,
        totalConImpuestos: totalConImpuestos,
        diferencia: saldoActual - totalConImpuestos
    };
}
//
async function confirmarVentaFinal() {
    const responsableNombre = document.getElementById('responsableVenta').value.trim();
    const responsableId = document.getElementById('responsable_id').value;
    
    if (!responsableId) {
        mostrarAlerta('Por favor selecciona un responsable', 'warning');
        return;
    }
    
    // Obtener datos de pago mixto si existen
    const btnConfirmarFinal = document.getElementById('btnConfirmarVentaFinal');
    let datosPagoMixto = null;
    
    if (btnConfirmarFinal?.dataset.pagoMixto) {
        try {
            datosPagoMixto = JSON.parse(btnConfirmarFinal.dataset.pagoMixto);
        } catch (e) {
            console.error('Error parseando datos pago mixto:', e);
        }
    }
    
    // Mostrar loading
    const textoOriginal = btnConfirmarFinal.innerHTML;
    btnConfirmarFinal.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';
    btnConfirmarFinal.disabled = true;
    
    try {
        const response = await guardarVenta(
            responsableId,
            responsableNombre,
            productosSeleccionados,
            datosPagoMixto  // ← Pasar datos de pago mixto
        );
        
        if (response.success) {
            // Cerrar modales
            const modalConfirmacion = bootstrap.Modal.getInstance(document.getElementById('modalConfirmarVenta'));
            if (modalConfirmacion) modalConfirmacion.hide();
            
            const modalVenta = bootstrap.Modal.getInstance(document.getElementById('modalRealizarVenta'));
            if (modalVenta) modalVenta.hide();
            
            // Mostrar éxito
            mostrarAlerta('✅ Venta registrada y ticket impreso', 'success');
            
            // Recargar después de 3 segundos
            setTimeout(() => {
                location.reload();
            }, 3000);
            
        } else {
            throw new Error(response.message || 'Error al guardar la venta');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error: ' + error.message, 'error');
        
        // Restaurar botón
        btnConfirmarFinal.innerHTML = textoOriginal;
        btnConfirmarFinal.disabled = false;
    }
}
// ===== GUARDAR VENTA =====

async function verificarResponsableExistente(nombre) {
    try {
        const response = await buscarResponsables(nombre);
        return response && response.responsables && response.responsables.length > 0;
    } catch (error) {
        return false;
    }
}
//
// Función para mostrar formulario de cierre cuando sea necesario
function mostrarFormularioCierre(diferenciaPago) {
    const contenedor = document.getElementById('formularioCierreNota');
    if (!contenedor) return;
    
    if (diferenciaPago > 0) {
        contenedor.innerHTML = `
            <div class="alert alert-info mt-3">
                <h6><i class="fas fa-money-bill-wave me-2"></i>CIERRE DE NOTA REQUERIDO</h6>
                <p>Esta venta cerrará la nota de abono. Complete los datos del pago:</p>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Tipo de Pago *</label>
                        <select id="tipoPagoCierre" class="form-select" required>
                            <option value="">Seleccionar...</option>
                            <option value="1">Efectivo</option>
                            <option value="2">Transferencia</option>
                            <option value="3">Tarjeta</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Monto a Pagar *</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" id="montoPagoCierre" 
                                   class="form-control" 
                                   value="${diferenciaPago.toFixed(2)}"
                                   min="${diferenciaPago.toFixed(2)}"
                                   step="0.01" required>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <label class="form-label">Referencia (opcional)</label>
                        <input type="text" id="referenciaPago" 
                               class="form-control" 
                               placeholder="Ej: Transferencia #12345, Recibo #001">
                    </div>
                    
                    <div class="col-md-12">
                        <label class="form-label">Observaciones (opcional)</label>
                        <textarea id="observacionesCierre" 
                                  class="form-control" 
                                  rows="2"
                                  placeholder="Notas adicionales sobre el cierre"></textarea>
                    </div>
                </div>
            </div>
        `;
        contenedor.style.display = 'block';
        
        // Configurar eventos para mostrar/ocultar campos según tipo de pago
        document.getElementById('tipoPagoCierre').addEventListener('change', function() {
            const esTransferencia = this.value === '2';
            const referenciaField = document.getElementById('referenciaPago').parentElement;
            referenciaField.style.display = esTransferencia ? 'block' : 'none';
        });
        
    } else {
        contenedor.style.display = 'none';
    }
}

// ===== GUARDAR VENTA =====
async function guardarVenta(responsableId, responsableNombre, productos, datosPagoMixto = null) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
  
    
    // === FUNCIONES DE REDONDEO ===
    const redondearSAT6 = (valor) => {
        if (isNaN(valor) || valor === null || valor === undefined) return 0;
        const factor = Math.pow(10, 6);
        return Math.round((parseFloat(valor) + Number.EPSILON) * factor) / factor;
    };
    
    const redondearSAT2 = (valor) => {
        if (isNaN(valor) || valor === null || valor === undefined) return 0;
        return Math.round((parseFloat(valor) + Number.EPSILON) * 100) / 100;
    };
    
    // === 1. VALIDACIONES INICIALES ===
    if (!responsableId || responsableId <= 0) {
        throw new Error('ID de responsable inválido');
    }
    
    if (!Array.isArray(productos) || productos.length === 0) {
        throw new Error('No hay productos para registrar');
    }
    
    // === 2. CALCULAR TOTAL CON IVA ===
    const totalConIVA = redondearSAT6(
        productos.reduce((sum, producto) => {
            const precioConIVA = parseFloat(producto.precioConIVA || producto.precio || 0);
            const cantidad = parseInt(producto.cantidad) || 1;
            return sum + (precioConIVA * cantidad);
        }, 0)
    );
    
    // === 3. OBTENER TASAS ===
    const tasaIVA = parseFloat(regimenCliente?.iva) || 0;
    const tasaISR = parseFloat(regimenCliente?.isr) || 0;
    const esPersonaMoral = tasaISR > 0;
    
    // === 4. CALCULAR SUBTOTAL (SIN IVA) ===
    let subtotal = 0;
    let iva = 0;
    
    if (tasaIVA > 0) {
        subtotal = redondearSAT6(totalConIVA / (1 + (tasaIVA / 100)));
        iva = redondearSAT6(totalConIVA - subtotal);
    } else {
        subtotal = redondearSAT6(totalConIVA);
        iva = 0;
    }
    
    // === 5. CALCULAR ISR ===
    let isr = 0;
    let total = 0;
    
    if (esPersonaMoral) {
        isr = redondearSAT6(subtotal * (tasaISR / 100));
        total = redondearSAT6(subtotal + iva - isr);
    } else {
        isr = 0;
        total = redondearSAT6(subtotal + iva);
    }
    
    // === 6. CALCULAR SALDOS ===
    const saldoAntes = obtenerSaldoActualCliente() || 0;
    
    // Manejar pago mixto
    let saldoDescontar = total;
    let pagoEfectivo = 0;
    let usaPagoMixto = false;
    
    if (datosPagoMixto?.habilitado) {
        usaPagoMixto = true;
        saldoDescontar = parseFloat(datosPagoMixto.pago_saldo) || 0;
        pagoEfectivo = parseFloat(datosPagoMixto.pago_efectivo) || 0;
    }
    
    const saldoDespues = redondearSAT2(saldoAntes - saldoDescontar);
    
    // === 7. PREPARAR DATOS DE CIERRE SI APLICA ===
    let cierreDatos = null;
    
    const pagoMixtoCheckbox = document.getElementById('habilitarPagoMixto');
    const tipoPagoSelect = document.getElementById('tipoPagoCierre');
    
    if (pagoMixtoCheckbox?.checked && tipoPagoSelect?.value) {
        const tipoPagoId = parseInt(tipoPagoSelect.value);
        const opcionSeleccionada = tipoPagoSelect.options[tipoPagoSelect.selectedIndex];
        const esTransferencia = opcionSeleccionada.getAttribute('data-es-transferencia') === '1';
        
        // Obtener valores asegurando que sean strings
        const referenciaInput = document.getElementById('referenciaPago');
        const observacionesInput = document.getElementById('observacionesCierre');
        const montoPagoInput = document.getElementById('pagoEfectivo');
        
        cierreDatos = {
            tipo_pago_id: tipoPagoId,
            efectivo: !esTransferencia ? parseFloat(montoPagoInput?.value || 0) : 0,
            transferencia: esTransferencia ? parseFloat(montoPagoInput?.value || 0) : 0,
            referencia: referenciaInput?.value || '', // ← STRING VACÍO, no null
            observaciones: observacionesInput?.value || '' // ← STRING VACÍO, no null
        };
        
      
    }
    
    // === 8. PREPARAR DATOS PARA ENVIAR ===
    const datosVenta = {
        responsable_id: parseInt(responsableId),
        responsable_nombre: responsableNombre || '',
        productos: productos.map(p => {
            const precioUnitario = parseFloat(p.precioConIVA || p.precio || 0);
            const cantidad = parseInt(p.cantidad) || 1;
            
            return {
                id: parseInt(p.id),
                nombre: p.nombre || 'Producto sin nombre',
                precio: redondearSAT2(precioUnitario),
                cantidad: cantidad,
                subtotal: redondearSAT2(precioUnitario * cantidad)
            };
        }),
        subtotal: redondearSAT2(subtotal),
        iva_calculado: redondearSAT2(iva),
        isr_calculado: redondearSAT2(isr),
        total: redondearSAT2(total),
        saldo_antes: redondearSAT2(saldoAntes),
        saldo_despues: redondearSAT2(saldoDespues),
        total_items: productos.reduce((sum, producto) => sum + (parseInt(producto.cantidad) || 1), 0),
        pago_mixto: usaPagoMixto ? {
            habilitado: true,
            pago_saldo: parseFloat(saldoDescontar),
            pago_efectivo: parseFloat(pagoEfectivo),
            observaciones: datosPagoMixto?.observaciones || ''
        } : null,
        cierre_datos: cierreDatos // ← Puede ser null o objeto con strings
    };
    
   
    
    // === 9. OBTENER ID DE NOTA DE ABONO ===
    const notaAbonoId = document.getElementById('modalRealizarVenta')?.dataset.notaAbonoId;
    
    if (!notaAbonoId) {
        throw new Error('No se encontró el ID de la nota de abono');
    }
    
    // === 10. ENVIAR AL SERVIDOR ===
    const response = await fetch(`/notas-abono/${notaAbonoId}/registrar-venta`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken || '',
            'Accept': 'application/json'
        },
        body: JSON.stringify(datosVenta)
    });
    
   
    
    // === 11. MANEJAR RESPUESTA ===
    if (!response.ok) {
        let errorMessage = `Error HTTP: ${response.status}`;
        
        try {
            const errorData = await response.json();
            console.error('📝 Detalles del error:', errorData);
            
            if (errorData.errors) {
                errorMessage = 'Errores de validación:\n' + 
                    Object.entries(errorData.errors)
                        .map(([key, msgs]) => `${key}: ${msgs.join(', ')}`)
                        .join('\n');
            } else if (errorData.message) {
                errorMessage = errorData.message;
            }
            
        } catch (e) {
            console.error('No se pudo parsear la respuesta de error:', e);
        }
        
        throw new Error(errorMessage);
    }
    
    const data = await response.json();
  
    
    return data;
}
//
function prepararDatosCierre() {
    const pagoMixtoHabilitado = document.getElementById('habilitarPagoMixto')?.checked;
    
    if (!pagoMixtoHabilitado) {
        return null;
    }
    
    const tipoPagoSelect = document.getElementById('tipoPagoCierre');
    const referenciaInput = document.getElementById('referenciaPago');
    const observacionesInput = document.getElementById('observacionesCierre');
    
    if (!tipoPagoSelect?.value) {
        throw new Error('Seleccione un tipo de pago para cerrar la nota');
    }
    
    const tipoPagoId = parseInt(tipoPagoSelect.value);
    const esTransferencia = tipoPagoId === 2; // Asumiendo 2 = Transferencia
    
    // Asegurar que siempre sean strings, no null o undefined
    return {
        tipo_pago_id: tipoPagoId,
        efectivo: !esTransferencia ? parseFloat(document.getElementById('pagoEfectivo')?.value || 0) : 0,
        transferencia: esTransferencia ? parseFloat(document.getElementById('pagoEfectivo')?.value || 0) : 0,
        referencia: referenciaInput?.value || '', // ← Siempre string vacío, no null
        observaciones: observacionesInput?.value || '' // ← Siempre string vacío, no null
    };
}
// === FUNCIÓN AUXILIAR PARA VALIDACIÓN ===
function validarDatosVenta(datos) {
    const errores = [];
    
    // Validar responsable
    if (!datos.responsable_id || datos.responsable_id <= 0) {
        errores.push('ID de responsable inválido');
    }
    
    if (!datos.responsable_nombre || datos.responsable_nombre.trim() === '') {
        errores.push('Nombre de responsable requerido');
    }
    
    // Validar productos
    if (!Array.isArray(datos.productos) || datos.productos.length === 0) {
        errores.push('Debe haber al menos un producto');
    } else {
        datos.productos.forEach((p, index) => {
            if (!p.id || p.id <= 0) errores.push(`Producto ${index + 1}: ID inválido`);
            if (!p.nombre || p.nombre.trim() === '') errores.push(`Producto ${index + 1}: Nombre requerido`);
            if (!p.cantidad || p.cantidad <= 0) errores.push(`Producto ${index + 1}: Cantidad inválida`);
            if (!p.precio || p.precio <= 0) errores.push(`Producto ${index + 1}: Precio inválido`);
        });
    }
    
    // Validar montos
    if (datos.subtotal === undefined || datos.subtotal < 0) errores.push('Subtotal inválido');
    if (datos.iva_calculado === undefined || datos.iva_calculado < 0) errores.push('IVA inválido');
    if (datos.isr_calculado === undefined || datos.isr_calculado < 0) errores.push('ISR inválido');
    if (!datos.total || datos.total <= 0) errores.push('Total inválido');
    
    // Validar saldos
    if (datos.saldo_antes === undefined || datos.saldo_antes < 0) {
        errores.push('Saldo antes inválido');
    }
    
    if (datos.saldo_despues === undefined) {
        errores.push('Saldo después requerido');
    }
    
    // Validar pago mixto si existe
    if (datos.pago_mixto?.habilitado) {
        if (!datos.pago_mixto.pago_saldo || datos.pago_mixto.pago_saldo < 0) {
            errores.push('Monto de saldo en pago mixto inválido');
        }
        
        if (!datos.pago_mixto.pago_efectivo || datos.pago_mixto.pago_efectivo < 0) {
            errores.push('Monto de efectivo en pago mixto inválido');
        }
        
        // Validar que la suma sea igual al total
        const suma = datos.pago_mixto.pago_saldo + datos.pago_mixto.pago_efectivo;
        if (Math.abs(suma - datos.total) > 0.01) {
            errores.push(`La suma del saldo ($${datos.pago_mixto.pago_saldo}) y efectivo ($${datos.pago_mixto.pago_efectivo}) = $${suma} no coincide con el total $${datos.total}`);
        }
    }
    
    // Validar datos de cierre si existen
    if (datos.cierre_datos) {
        if (!datos.cierre_datos.tipo_pago_id || datos.cierre_datos.tipo_pago_id <= 0) {
            errores.push('Tipo de pago para cierre requerido');
        }
        
        // Validar que al menos un monto sea positivo
        const efectivo = datos.cierre_datos.efectivo || 0;
        const transferencia = datos.cierre_datos.transferencia || 0;
        
        if (efectivo <= 0 && transferencia <= 0) {
            errores.push('Debe especificar un monto de pago para el cierre');
        }
    }
    
    return errores;
}


//

function calcularTotal() {
    return productosSeleccionados.reduce((sum, producto) => sum + producto.subtotal, 0);
}

// ===== UTILIDADES =====
function reiniciarBusqueda() {
    if (timeoutBusqueda) clearTimeout(timeoutBusqueda);
    if (timeoutResponsable) clearTimeout(timeoutResponsable);
    
    timeoutBusqueda = null;
    timeoutResponsable = null;
    responsableSeleccionado = null;
    
    document.getElementById('buscarProducto').value = '';
    document.getElementById('responsableVenta').value = '';
    document.getElementById('responsable_id').value = '';
    document.getElementById('resultadosBusqueda').style.display = 'none';
    
    // Limpiar validación visual
    const inputResponsable = document.getElementById('responsableVenta');
    inputResponsable.classList.remove('is-valid', 'is-invalid');
    
    ocultarSugerencias();
    ocultarBotonRegistrar();
    actualizarEstadoResponsable('inicial');
    productosSeleccionados = [];
    calcularImpuestosYTotal();
    actualizarListaProductosModal();
    
    // Validar estado inicial
    validarResponsable();
}
// ===== LIMPIAR BÚSQUEDA =====
function limpiarBusqueda() {
    const contenedor = document.getElementById('resultadosBusqueda');
    if (contenedor) {
        contenedor.style.display = 'none';
    }
}

function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function cerrarModalYRecargar() {
    const modalElement = document.getElementById('modalRealizarVenta');
    const modal = bootstrap.Modal.getInstance(modalElement);
    if (modal) {
        modal.hide();
    }
    
    setTimeout(() => {
        location.reload();
    }, 1000);
}

function mostrarAlerta(mensaje, tipo = 'info') {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: tipo,
            title: tipo.charAt(0).toUpperCase() + tipo.slice(1),
            text: mensaje,
            timer: 3000
        });
    } else {
        alert(`${tipo.toUpperCase()}: ${mensaje}`);
    }
}


//acciones para ticket pdf
function verPDF(ventaId) {
    const url = `{{ url('ventas') }}/${ventaId}/pdf`;
    window.open(url, '_blank', 'width=800,height=600');
}

// Función para reimprimir
async function reimprimirTicket(ventaId) {
    if (!confirm('¿Reimprimir este ticket?')) return;
    
    try {
        const response = await fetch(`/ventas/${ventaId}/reimprimir`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Reimpreso!',
                text: data.message || 'Ticket reimpreso exitosamente',
                timer: 2000
            });
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al reimprimir: ' + error.message
        });
    }
}

// Función para cancelar venta (si la implementas)
function mostrarModalCancelacion(id, ticket) {
    ventaIdCancelar = id;
    ventaTicketCancelar = ticket;
    
    // Actualizar texto del modal
    document.getElementById('textoTicket').innerHTML = 
        `<strong>¿Está seguro de cancelar el ticket <span class="text-danger">${ticket}</span>?</strong>`;
    
    // Resetear formulario
    document.getElementById('motivoCancelacion').value = '';
    document.getElementById('observacionesCancelacion').value = '';
    document.getElementById('confirmarCancelacion').checked = false;
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalCancelacion'));
    modal.show();
}

function confirmarCancelacion() {
    const motivo = document.getElementById('motivoCancelacion').value;
    const observaciones = document.getElementById('observacionesCancelacion').value;
    const confirmado = document.getElementById('confirmarCancelacion').checked;
    
    // Validaciones
    if (!motivo) {
        Swal.fire('Error', 'Debe seleccionar un motivo de cancelación', 'error');
        return;
    }
    
    if (!confirmado) {
        Swal.fire('Error', 'Debe confirmar la cancelación', 'error');
        return;
    }
    
    // Mostrar confirmación final
    Swal.fire({
        title: '¿Cancelar definitivamente?',
        text: `La venta ${ventaTicketCancelar} será cancelada permanentemente`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'No, mantener',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return cancelarVenta(ventaIdCancelar, motivo, observaciones);
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Cerrar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalCancelacion'));
            modal.hide();
            
            // Recargar la página después de 1 segundo
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    });
}

function cancelarVenta(id, motivo, observaciones) {
    return fetch(`/ventas/cancelar/${id}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            motivo: motivo,
            observaciones: observaciones
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Venta cancelada!',
                text: data.message,
                showConfirmButton: false,
                timer: 2000
            });
            return data;
        } else {
            throw new Error(data.message);
        }
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
        throw error;
    });
}

//Deviolucion de productos
// Mostrar modal de devolución
function mostrarModalDevolucion(ventaId, ticket, cliente) {
    ventaIdDevolucion = ventaId;
    productosDevolucion = [];
    
    // Actualizar información básica
    document.getElementById('ticketDevolucion').textContent = ticket;
    document.getElementById('clienteDevolucion').textContent = cliente;
    document.getElementById('infoVentaDevolucion').innerHTML = 
        `Devolución para ticket: <strong>${ticket}</strong> - Cliente: <strong>${cliente}</strong>`;
    
    // Resetear formulario
    resetearFormularioDevolucion();
    
    // Cargar productos de la venta
    cargarProductosVenta(ventaId);
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalDevolucion'));
    modal.show();
}

// Resetear formulario
function resetearFormularioDevolucion() {
    document.getElementById('motivoDevolucion').value = '';
    document.getElementById('observacionesDevolucion').value = '';
    document.getElementById('confirmarDevolucion').checked = false;
    document.getElementById('selectAllProducts').checked = false;
    document.getElementById('listaProductosDevolucion').innerHTML = '';
    document.getElementById('resumenDevolucion').style.display = 'none';
    document.getElementById('btnProcesarDevolucion').disabled = true;
}

// Cargar productos de la venta
function cargarProductosVenta(ventaId) {
    // Mostrar loading
    const tbody = document.getElementById('listaProductosDevolucion');
    tbody.innerHTML = `
        <tr>
            <td colspan="7" class="text-center py-4">
                <div class="spinner-border spinner-border-sm text-warning me-2"></div>
                Cargando productos...
            </td>
        </tr>
    `;
    
    // Obtener productos de la venta
    fetch(`/ventas/${ventaId}/productos-devolucion`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            renderizarProductosDevolucion(data.productos);
        } else {
            Swal.fire('Error', data.message || 'No se pudieron cargar los productos', 'error');
            resetearFormularioDevolucion();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', 'Error al cargar los productos', 'error');
        resetearFormularioDevolucion();
    });
}

// Renderizar lista de productos
function renderizarProductosDevolucion(productos) {
    const tbody = document.getElementById('listaProductosDevolucion');
    
    if (productos.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4 text-muted">
                    <i class="fas fa-box-open me-2"></i> No hay productos disponibles para devolver
                </td>
            </tr>
        `;
        return;
    }
    
    let html = '';
    
    productos.forEach(producto => {
        const disponible = producto.cantidad - producto.cantidad_devuelta;
        const puedeDevolver = disponible > 0;
        
        html += `
            <tr class="${!puedeDevolver ? 'table-secondary' : ''}">
                <td>
                    ${puedeDevolver ? 
                        `<input type="checkbox" class="form-check-input producto-check" 
                               data-id="${producto.id}"
                               data-precio="${producto.precio_unitario}"
                               data-disponible="${disponible}"
                               onchange="toggleProductoDevolucion(this)">` :
                        `<i class="fas fa-ban text-muted"></i>`
                    }
                </td>
                <td>
                    <div class="fw-semibold">${producto.nombre_producto}</div>
                    ${producto.codigo_barra ? `<small class="text-muted">Código: ${producto.codigo_barra}</small>` : ''}
                </td>
                <td class="text-center">$${parseFloat(producto.precio_unitario).toFixed(2)}</td>
                <td class="text-center">${producto.cantidad}</td>
                <td class="text-center">
                    ${producto.cantidad_devuelta > 0 ? 
                        `<span class="badge bg-info">${producto.cantidad_devuelta}</span>` : 
                        '<span class="text-muted">0</span>'
                    }
                </td>
                <td class="text-center">
                    ${disponible > 0 ? 
                        `<span class="badge bg-success">${disponible}</span>` :
                        '<span class="badge bg-secondary">0</span>'
                    }
                </td>
                <td class="text-center">
                    ${puedeDevolver ?
                        `<div class="input-group input-group-sm" style="width: 120px;">
                            <button class="btn btn-outline-secondary" type="button" 
                                    onclick="cambiarCantidadDevolucion(this, -1)" ${disponible <= 1 ? 'disabled' : ''}>
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" 
                                   class="form-control text-center cantidad-input" 
                                   data-id="${producto.id}"
                                   value="0" 
                                   min="0" 
                                   max="${disponible}"
                                   onchange="validarCantidadDevolucion(this)"
                                   oninput="actualizarCantidadDevolucion(this)">
                            <button class="btn btn-outline-secondary" type="button" 
                                    onclick="cambiarCantidadDevolucion(this, 1)" ${disponible <= 0 ? 'disabled' : ''}>
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>` :
                        '<span class="text-muted">-</span>'
                    }
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
    
    // Configurar "Seleccionar todos"
    document.getElementById('selectAllProducts').addEventListener('change', function() {
        const checks = document.querySelectorAll('.producto-check:not(:disabled)');
        checks.forEach(check => {
            check.checked = this.checked;
            if (this.checked) {
                const input = document.querySelector(`.cantidad-input[data-id="${check.dataset.id}"]`);
                if (input) {
                    input.value = parseInt(check.dataset.disponible) || 0;
                    actualizarCantidadDevolucion(input);
                }
            }
        });
        actualizarResumenDevolucion();
    });
}

// Toggle de producto seleccionado
function toggleProductoDevolucion(checkbox) {
    const input = document.querySelector(`.cantidad-input[data-id="${checkbox.dataset.id}"]`);
    if (checkbox.checked) {
        // Si se selecciona, poner cantidad máxima disponible
        input.value = parseInt(checkbox.dataset.disponible) || 1;
    } else {
        // Si se deselecciona, poner 0
        input.value = 0;
    }
    actualizarCantidadDevolucion(input);
}

// Cambiar cantidad con botones +/- 
function cambiarCantidadDevolucion(button, cambio) {
    const inputGroup = button.closest('.input-group');
    const input = inputGroup.querySelector('.cantidad-input');
    const nuevoValor = parseInt(input.value) + cambio;
    
    if (nuevoValor >= 0 && nuevoValor <= parseInt(input.max)) {
        input.value = nuevoValor;
        actualizarCantidadDevolucion(input);
        
        // Marcar checkbox si hay cantidad > 0
        const checkbox = document.querySelector(`.producto-check[data-id="${input.dataset.id}"]`);
        if (checkbox) {
            checkbox.checked = nuevoValor > 0;
        }
    }
}

// Validar cantidad
function validarCantidadDevolucion(input) {
    const max = parseInt(input.max);
    const valor = parseInt(input.value);
    
    if (isNaN(valor) || valor < 0) {
        input.value = 0;
    } else if (valor > max) {
        input.value = max;
        Swal.fire({
            icon: 'warning',
            title: 'Cantidad máxima',
            text: `No puede devolver más de ${max} unidades`,
            timer: 2000,
            showConfirmButton: false
        });
    }
    
    actualizarCantidadDevolucion(input);
}

// Actualizar cantidad en tiempo real
function actualizarCantidadDevolucion(input) {
    const productoId = input.dataset.id;
    const cantidad = parseInt(input.value) || 0;
    const precio = parseFloat(document.querySelector(`.producto-check[data-id="${productoId}"]`).dataset.precio) || 0;
    
    // Encontrar o crear entrada en el array
    const index = productosDevolucion.findIndex(p => p.detalle_id == productoId);
    
    if (cantidad > 0) {
        if (index === -1) {
            productosDevolucion.push({
                detalle_id: productoId,
                cantidad: cantidad
            });
        } else {
            productosDevolucion[index].cantidad = cantidad;
        }
    } else if (index !== -1) {
        productosDevolucion.splice(index, 1);
    }
    
    actualizarResumenDevolucion();
}

// Actualizar resumen
function actualizarResumenDevolucion() {
    const totalProductos = productosDevolucion.reduce((sum, p) => sum + p.cantidad, 0);
    let totalMonto = 0;
    
    // Calcular monto total (simplificado - en realidad se calcula en el servidor)
    productosDevolucion.forEach(p => {
        const checkbox = document.querySelector(`.producto-check[data-id="${p.detalle_id}"]`);
        if (checkbox) {
            const precio = parseFloat(checkbox.dataset.precio) || 0;
            totalMonto += precio * p.cantidad;
        }
    });
    
    // Actualizar UI
    document.getElementById('totalProductosDevolver').textContent = totalProductos;
    document.getElementById('montoTotalDevolver').textContent = `$${totalMonto.toFixed(2)}`;
    
    // Mostrar/ocultar resumen
    const resumen = document.getElementById('resumenDevolucion');
    resumen.style.display = totalProductos > 0 ? 'table-row' : 'none';
    
    // Habilitar/deshabilitar botón
    const btnProcesar = document.getElementById('btnProcesarDevolucion');
    const confirmado = document.getElementById('confirmarDevolucion').checked;
    const motivo = document.getElementById('motivoDevolucion').value;
    
    btnProcesar.disabled = !(totalProductos > 0 && confirmado && motivo);
}

// Procesar devolución
function procesarDevolucion() {
    const motivo = document.getElementById('motivoDevolucion').value;
    const observaciones = document.getElementById('observacionesDevolucion').value;
    
    if (productosDevolucion.length === 0) {
        Swal.fire('Error', 'Seleccione al menos un producto para devolver', 'error');
        return;
    }
    
    if (!motivo) {
        Swal.fire('Error', 'Seleccione un motivo de devolución', 'error');
        return;
    }
    
    // Confirmación final
    Swal.fire({
        title: '¿Confirmar devolución?',
        html: `Se devolverán <strong>${productosDevolucion.reduce((s, p) => s + p.cantidad, 0)} productos</strong><br>
               <small class="text-muted">Esta acción no se puede deshacer</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#fd7e14',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, procesar devolución',
        cancelButtonText: 'Cancelar',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return fetch(`/ventas/devolucion/${ventaIdDevolucion}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    productos: productosDevolucion,
                    motivo: motivo,
                    observaciones: observaciones
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message);
                }
                return data;
            });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: '¡Devolución exitosa!',
                html: `
                    <div class="text-center">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <p>${result.value.message}</p>
                        <div class="alert alert-success d-inline-block">
                            <strong>Folio:</strong> ${result.value.folio_devolucion || 'N/A'}<br>
                            <strong>Total devuelto:</strong> $${result.value.total_devolucion || '0.00'}<br>
                            <strong>Nuevo saldo:</strong> $${result.value.nuevo_saldo || '0.00'}
                        </div>
                    </div>
                `,
                icon: 'success',
                confirmButtonText: 'Aceptar'
            }).then(() => {
                // Cerrar modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalDevolucion'));
                modal.hide();
                
                // Recargar página después de 1 segundo
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            });
        }
    }).catch(error => {
        Swal.fire('Error', error.message || 'Error al procesar la devolución', 'error');
    });
}

// Configurar eventos
document.addEventListener('DOMContentLoaded', function() {
    // Actualizar botón cuando cambian los campos
    document.getElementById('motivoDevolucion').addEventListener('change', actualizarResumenDevolucion);
    document.getElementById('observacionesDevolucion').addEventListener('input', actualizarResumenDevolucion);
    document.getElementById('confirmarDevolucion').addEventListener('change', actualizarResumenDevolucion);
    
    // Limpiar al cerrar modal
    document.getElementById('modalDevolucion').addEventListener('hidden.bs.modal', function() {
        resetearFormularioDevolucion();
        ventaIdDevolucion = null;
        productosDevolucion = [];
    });
});
//historial devolucion
// Mostrar modal de historial de devoluciones
// Mostrar modal de historial de devoluciones
function mostrarHistorialDevoluciones(ventaId, ticket, cliente) {
  
    
    ventaIdHistorial = ventaId;
    ventaTicketHistorial = ticket;
    
    // Obtener el modal
    const modal = document.getElementById('modalHistorialDevoluciones');
    if (!modal) {
        console.error('❌ Modal no encontrado');
        Swal.fire('Error', 'No se pudo abrir el historial', 'error');
        return;
    }
    
    // Actualizar título inmediatamente (no esperar al shown)
    const titulo = modal.querySelector('#tituloHistorialDevolucion');
    const subtitulo = modal.querySelector('#subtituloHistorialDevolucion');
    
    if (titulo) titulo.innerHTML = `Historial de devoluciones - Ticket: <strong>${ticket}</strong>`;
    if (subtitulo) subtitulo.innerHTML = `Cliente: <strong>${cliente}</strong>`;
    
    // Resetear contenido
    resetearHistorialDevoluciones();
    
    // Configurar evento cuando el modal se muestre
    const onModalShown = () => {
       
        cargarHistorialDevoluciones(ventaId);
        
        // Remover el listener
        modal.removeEventListener('shown.bs.modal', onModalShown);
    };
    
    modal.addEventListener('shown.bs.modal', onModalShown);
    
    // Mostrar modal
    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();
}
// Resetear contenido del modal
function resetearHistorialDevoluciones() {
   
    
    const modal = document.getElementById('modalHistorialDevoluciones');
    if (!modal) return;
    
    // Mostrar estado inicial: mensaje "cargando" o "sin devoluciones"
    const container = modal.querySelector('#listaDevolucionesContainer');
    if (container) {
        container.innerHTML = `
            <div id="sinDevoluciones" class="text-center py-5">
                <div class="spinner-border spinner-border-sm text-info me-2"></div>
                Cargando historial de devoluciones...
            </div>
        `;
    }
    
    // Ocultar otros elementos
    const elementosAOcultar = [
        'tablaDevoluciones',
        'resumenDevoluciones',
        'btnImprimirHistorial',
        'detalleProductosContainer',
        'resumenPorProductoContainer'
    ];
    
    elementosAOcultar.forEach(id => {
        const element = modal.querySelector(`#${id}`);
        if (element) element.style.display = 'none';
    });
}
// Cargar historial de devoluciones
async function cargarHistorialDevoluciones(ventaId) {
  
    
    // Mostrar loading
    const modal = document.getElementById('modalHistorialDevoluciones');
    if (!modal) {
        console.error('❌ Modal no encontrado');
        return;
    }
    
    const container = modal.querySelector('#listaDevolucionesContainer');
    if (!container) {
        console.error('❌ Container no encontrado');
        return;
    }
    
    // Mostrar loading
    container.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border spinner-border-sm text-info"></div>
            <p class="mt-2 text-muted">Cargando historial de devoluciones...</p>
        </div>
    `;
    
    try {
        const response = await fetch(`/ventas/${ventaId}/historial-devoluciones`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
       
        
        const data = await response.json();
     
        
        if (data.success) {
            // Verificar si hay devoluciones
            if (data.devoluciones && data.devoluciones.length > 0) {
        
                renderizarHistorialDevoluciones(data);
            } else {
           
                
                // Ocultar secciones que no se necesitan
                const resumenEl = modal.querySelector('#resumenDevoluciones');
                if (resumenEl) resumenEl.style.display = 'none';
                
                const btnImprimir = modal.querySelector('#btnImprimirHistorial');
                if (btnImprimir) btnImprimir.style.display = 'none';
                
                // Mostrar mensaje "sin devoluciones"
                container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No hay devoluciones registradas</h5>
                        <p class="text-muted small">Esta venta no tiene productos devueltos</p>
                    </div>
                `;
            }
        } else {
            console.error('❌ Error en la respuesta:', data.message);
            container.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Error:</strong> ${data.message || 'Error al cargar el historial'}
                </div>
            `;
        }
        
    } catch (error) {
        console.error('💥 Error en fetch:', error);
        
        container.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Error de conexión:</strong> No se pudo cargar el historial
            </div>
        `;
    }
}
// Renderizar historial
function renderizarHistorialDevoluciones(data) {
  
    
    try {
        // Obtener modal
        const modal = document.getElementById('modalHistorialDevoluciones');
        if (!modal) {
            console.error('Modal no disponible');
            return;
        }
        
        // Verificar si hay datos
        if (!data || !data.success) {
            console.error('Datos inválidos o error en la respuesta');
            mostrarMensajeSinDevoluciones(modal, 'Error al cargar los datos');
            return;
        }
        
        // Verificar si hay devoluciones
        const devoluciones = data.devoluciones || [];
        
        if (devoluciones.length === 0) {
         
            mostrarMensajeSinDevoluciones(modal, 'No hay devoluciones registradas para esta venta');
            return;
        }
        
        // Si hay devoluciones, ocultar el mensaje y mostrar la tabla
        mostrarTablaDevoluciones(modal, data);
        
     
        
    } catch (error) {
        console.error('💥 Error en renderizarHistorialDevoluciones:', error);
        mostrarMensajeSinDevoluciones(modal, 'Error al cargar el historial: ' + error.message);
    }
}

// Función para mostrar mensaje "Sin devoluciones"
function mostrarMensajeSinDevoluciones(modal, mensaje = 'No hay devoluciones registradas') {
    
    // Ocultar elementos que no se necesitan
    const elementosAOcultar = [
        'tablaDevoluciones',
        'resumenDevoluciones',
        'btnImprimirHistorial',
        'detalleProductosContainer',
        'resumenPorProductoContainer'
    ];
    
    elementosAOcultar.forEach(id => {
        const element = modal.querySelector(`#${id}`);
        if (element) element.style.display = 'none';
    });
    
    // Mostrar mensaje "sin devoluciones"
    const sinDevolucionesEl = modal.querySelector('#sinDevoluciones');
    if (sinDevolucionesEl) {
        // Actualizar el mensaje si se proporciona uno personalizado
        const icono = sinDevolucionesEl.querySelector('i');
        const titulo = sinDevolucionesEl.querySelector('h5');
        const descripcion = sinDevolucionesEl.querySelector('p');
        
        if (titulo) titulo.textContent = mensaje;
        if (descripcion) descripcion.textContent = 'Esta venta no tiene productos devueltos';
        
        sinDevolucionesEl.style.display = 'block';
    } else {
        // Si no existe el elemento, crearlo dinámicamente
        const container = modal.querySelector('#listaDevolucionesContainer') || modal;
        container.innerHTML = `
            <div id="sinDevoluciones" class="text-center py-5">
                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">${mensaje}</h5>
                <p class="text-muted small">Esta venta no tiene productos devueltos</p>
            </div>
        `;
    }
}

// Función para mostrar tabla con devoluciones
function mostrarTablaDevoluciones(modal, data) {
    const devoluciones = data.devoluciones || [];
    
    // 1. Ocultar mensaje "sin devoluciones"
    const sinDevolucionesEl = modal.querySelector('#sinDevoluciones');
    if (sinDevolucionesEl) {
        sinDevolucionesEl.style.display = 'none';
    }
    
    // 2. Asegurar que exista un contenedor para la tabla
    const container = modal.querySelector('#listaDevolucionesContainer') || modal;
    
    // 3. Crear tabla HTML
    let tablaHTML = `
        <div class="table-responsive" id="tablaDevoluciones">
            <table class="table table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Folio</th>
                        <th>Fecha</th>
                        <th>Productos</th>
                        <th>Motivo</th>
                        <th class="text-end">Monto</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody id="cuerpoTablaDevoluciones">
    `;
    
    // Agregar filas
    devoluciones.forEach(devolucion => {
        const fecha = devolucion.created_at ? new Date(devolucion.created_at) : new Date();
        const fechaFormateada = fecha.toLocaleDateString('es-MX', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        
        let cantidadProductos = 0;
        if (devolucion.detalles && Array.isArray(devolucion.detalles)) {
            cantidadProductos = devolucion.detalles.reduce((sum, p) => {
                return sum + (parseInt(p.cantidad_devuelta) || 0);
            }, 0);
        }
        
        tablaHTML += `
            <tr>
                <td><span class="badge bg-info">${devolucion.folio_devolucion || 'N/A'}</span></td>
                <td>${fechaFormateada}</td>
                <td><small>${cantidadProductos} producto(s)</small></td>
                <td><span class="badge bg-light text-dark">${devolucion.motivo || 'N/A'}</span></td>
                <td class="text-end fw-semibold">$${parseFloat(devolucion.total || 0).toFixed(2)}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-info" onclick="mostrarDetalleDevolucion(${devolucion.id})">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    
    tablaHTML += `
                </tbody>
            </table>
        </div>
    `;
    
    // 4. Insertar en el contenedor (reemplazar contenido existente)
    container.innerHTML = tablaHTML;
    
    // 5. Mostrar resumen
    const resumenEl = modal.querySelector('#resumenDevoluciones');
    if (resumenEl) {
        resumenEl.style.display = 'flex';
        
        // Actualizar valores del resumen
        const totalDevoluciones = devoluciones.length;
        let totalProductos = 0;
        let totalMonto = 0;
        let ultimaFecha = null;
        
        devoluciones.forEach((devolucion, index) => {
            if (devolucion.detalles && Array.isArray(devolucion.detalles)) {
                totalProductos += devolucion.detalles.reduce((sum, p) => {
                    return sum + (parseInt(p.cantidad_devuelta) || 0);
                }, 0);
            }
            totalMonto += parseFloat(devolucion.total) || 0;
            
            if (index === 0 && devolucion.created_at) {
                const fecha = new Date(devolucion.created_at);
                ultimaFecha = fecha.toLocaleDateString('es-MX');
            }
        });
        
        // Actualizar elementos del resumen
        const actualizarElemento = (id, valor) => {
            const element = modal.querySelector(`#${id}`);
            if (element) element.textContent = valor;
        };
        
        actualizarElemento('totalDevoluciones', totalDevoluciones);
        actualizarElemento('totalProductosDevueltos', totalProductos);
        actualizarElemento('montoTotalDevuelto', `$${totalMonto.toFixed(2)}`);
        actualizarElemento('ultimaDevolucion', ultimaFecha || 'N/A');
    }
    
    // 6. Mostrar botón de imprimir
    const btnImprimir = modal.querySelector('#btnImprimirHistorial');
    if (btnImprimir) {
        btnImprimir.style.display = 'inline-block';
    }
    
    // 7. Mostrar resumen por producto si existe
    if (data.resumen_productos && Array.isArray(data.resumen_productos) && data.resumen_productos.length > 0) {
        renderizarResumenPorProducto(data.resumen_productos, modal);
    }
}
// FUNCIONES AUXILIARES CON LOGS
function mostrarMensajeSinDevoluciones(modal = null) {
 
    
    const getElement = modal 
        ? (id) => modal.querySelector(`#${id}`)
        : (id) => document.getElementById(id);
    
    const sinDevoluciones = getElement('sinDevoluciones');
    const tablaDevoluciones = getElement('tablaDevoluciones');
    const resumenDevoluciones = getElement('resumenDevoluciones');
    const btnImprimirHistorial = getElement('btnImprimirHistorial');
    
    if (sinDevoluciones) sinDevoluciones.style.display = 'block';
    if (tablaDevoluciones) tablaDevoluciones.style.display = 'none';
    if (resumenDevoluciones) resumenDevoluciones.style.display = 'none';
    if (btnImprimirHistorial) btnImprimirHistorial.style.display = 'none';
}

function mostrarSeccionesConDatos() {
   
    
    const sinDevoluciones = document.getElementById('sinDevoluciones');
    const tablaDevoluciones = document.getElementById('tablaDevoluciones');
    const resumenDevoluciones = document.getElementById('resumenDevoluciones');
    const btnImprimirHistorial = document.getElementById('btnImprimirHistorial');
    
  
    if (sinDevoluciones) {
  
        sinDevoluciones.style.display = 'none';
    }
    if (tablaDevoluciones) {
      
        tablaDevoluciones.style.display = 'block';
    }
    if (resumenDevoluciones) {
       
        resumenDevoluciones.style.display = 'flex';
    }
    if (btnImprimirHistorial) {
        
        btnImprimirHistorial.style.display = 'inline-block';
    }
}
// Mostrar detalle de una devolución específica
function mostrarDetalleDevolucion(devolucionId) {
    // Mostrar loading
    const container = document.getElementById('detalleProductosContainer');
    container.style.display = 'block';
    document.getElementById('cuerpoProductosDevueltos').innerHTML = `
        <tr>
            <td colspan="7" class="text-center py-3">
                <div class="spinner-border spinner-border-sm text-info me-2"></div>
                Cargando detalles...
            </td>
        </tr>
    `;
    
    // Obtener detalle
    fetch(`/devoluciones/${devolucionId}/detalle`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            renderizarDetalleDevolucion(data.devolucion);
        } else {
            mostrarErrorDetalle(data.message || 'No se pudo cargar el detalle');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarErrorDetalle('Error al cargar el detalle');
    });
}

// Renderizar detalle de devolución
function renderizarDetalleDevolucion(devolucion) {

    const tbody = document.getElementById('cuerpoProductosDevueltos');
    let html = '';

    let totalSubtotal = 0;
    let totalIva = 0;
    let totalIsr = 0;
    let totalGeneral = 0;

    // 🔒 NORMALIZAR detalles
    let productos = devolucion?.detalles ?? [];

    // Si viene como string JSON
    if (typeof productos === 'string') {
        try {
            productos = JSON.parse(productos);
        } catch (e) {
            console.error('❌ detalles no es JSON válido', productos);
            productos = [];
        }
    }

    // Si viene como objeto, convertir a arreglo
    if (!Array.isArray(productos)) {
        productos = [productos];
    }

    // Validación final
    if (productos.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4 text-muted">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    No hay detalles disponibles
                </td>
            </tr>
        `;
        return;
    }

    // ✅ Ya es seguro usar forEach
    productos.forEach(producto => {

        const subtotal = parseFloat(producto.subtotal || 0);
        const iva = parseFloat(producto.iva || 0);
        const isr = parseFloat(producto.isr || 0);
        const total = parseFloat(producto.total || 0);

        totalSubtotal += subtotal;
        totalIva += iva;
        totalIsr += isr;
        totalGeneral += total;

        html += `
            <tr>
                <td>
                    <div class="fw-semibold">${producto.nombre || 'Producto'}</div>
                    ${producto.codigo_barra ? `<small class="text-muted">${producto.codigo_barra}</small>` : ''}
                </td>
                <td class="text-center">${producto.cantidad_devuelta || 0}</td>
                <td class="text-center">$${parseFloat(producto.precio_unitario || 0).toFixed(2)}</td>
                <td class="text-end">$${subtotal.toFixed(2)}</td>
                <td class="text-end">$${iva.toFixed(2)}</td>
                <td class="text-end">$${isr.toFixed(2)}</td>
                <td class="text-end fw-semibold">$${total.toFixed(2)}</td>
            </tr>
        `;
    });

    tbody.innerHTML = html;

    // Totales
    document.getElementById('totalSubtotal').textContent = `$${totalSubtotal.toFixed(2)}`;
    document.getElementById('totalIva').textContent = `$${totalIva.toFixed(2)}`;
    document.getElementById('totalIsr').textContent = `$${totalIsr.toFixed(2)}`;
    document.getElementById('totalGeneral').textContent = `$${totalGeneral.toFixed(2)}`;
    document.getElementById('totalProductosDevueltos').style.display = 'table-row';

    // Scroll a la sección
    document.getElementById('detalleProductosContainer')
        ?.scrollIntoView({ behavior: 'smooth' });
}


// Renderizar resumen por producto
function renderizarResumenPorProducto(productos, modal) {
    const tbody = modal.querySelector('#cuerpoResumenProducto');
    if (!tbody) {
        console.error('❌ tbody de resumen no encontrado');
        return;
    }
    
    // Asegurar que productos sea un array
    const productosArray = Array.isArray(productos) ? productos : [];
    
    if (productosArray.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-3 text-muted">
                    No hay datos de productos
                </td>
            </tr>
        `;
        return;
    }
    
    let html = '';
    
    productosArray.forEach(producto => {
        // Asegurar que los valores sean números
        const comprado = parseInt(producto.cantidad_comprada) || 0;
        const devuelto = parseInt(producto.cantidad_devuelta) || 0;
        const disponible = comprado - devuelto;
        const porcentaje = comprado > 0 ? (devuelto / comprado * 100) : 0;
        
        let estadoBadge = '';
        if (devuelto === 0) {
            estadoBadge = '<span class="badge bg-success">Sin devoluciones</span>';
        } else if (devuelto === comprado) {
            estadoBadge = '<span class="badge bg-danger">Totalmente devuelto</span>';
        } else if (devuelto > 0) {
            estadoBadge = '<span class="badge bg-warning">Parcialmente devuelto</span>';
        }
        
        html += `
            <tr>
                <td>${producto.nombre || 'Producto sin nombre'}</td>
                <td class="text-center">${comprado}</td>
                <td class="text-center">
                    ${devuelto > 0 ? `<span class="badge bg-info">${devuelto}</span>` : '0'}
                </td>
                <td class="text-center">${disponible}</td>
                <td class="text-center">
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar ${porcentaje > 50 ? 'bg-warning' : 'bg-info'}" 
                             role="progressbar" 
                             style="width: ${porcentaje}%"
                             aria-valuenow="${porcentaje}" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            ${porcentaje.toFixed(1)}%
                        </div>
                    </div>
                </td>
                <td class="text-center">${estadoBadge}</td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
    
    // Mostrar el contenedor
    const container = modal.querySelector('#resumenPorProductoContainer');
    if (container) {
        container.style.display = 'block';
      
    }
}
//
function safeArray(value) {
    if (Array.isArray(value)) {
        return value;
    }
    
    if (value === null || value === undefined) {
        return [];
    }
    
    // Intentar convertir a array si es posible
    if (typeof value === 'object' && !Array.isArray(value)) {
        return Object.values(value);
    }
    
    return [];
}

// Función de utilidad para sumar valores numéricos de un array
function sumArray(array, property, defaultValue = 0) {
    const safeArrayValue = safeArray(array);
    return safeArrayValue.reduce((sum, item) => {
        const value = parseFloat(item[property]) || defaultValue;
        return sum + value;
    }, 0);
}
//
function debugModalStructure() {
    const modal = document.getElementById('modalHistorialDevoluciones');
    if (!modal) {
        console.error('❌ Modal no existe');
        return;
    }
    
 
    
    // 1. Verificar si existe la tabla
    const tablas = modal.querySelectorAll('table');
  
    
    tablas.forEach((tabla, index) => {
     
        
        // Verificar si tiene thead
        const thead = tabla.querySelector('thead');
       
        
        // Verificar si tiene tbody
        const tbody = tabla.querySelector('tbody');
      
        if (tbody) {
           
        }
        
        // Verificar si tiene tfoot
        const tfoot = tabla.querySelector('tfoot');
      
        
        // Mostrar estructura HTML breve
       
    });
    
    // 2. Buscar específicamente el elemento con ID "tablaDevoluciones"
    const tablaDevoluciones = modal.querySelector('#tablaDevoluciones');
   
    if (tablaDevoluciones) {
       
    }
    
    // 3. Buscar el div que contiene la tabla
    const contenedorTabla = modal.querySelector('#listaDevolucionesContainer');
  
    if (contenedorTabla) {
       
    }
    
   
}
// Funciones para mostrar errores
function mostrarErrorHistorial(mensaje) {
    const modal = document.getElementById('modalHistorialDevoluciones');
    if (!modal) return;
    
    const container = modal.querySelector('#listaDevolucionesContainer');
    if (!container) return;
    
    container.innerHTML = `
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Error:</strong> ${mensaje}
            <div class="mt-2">
                <button class="btn btn-sm btn-warning" 
                        onclick="cargarHistorialDevoluciones(${ventaIdHistorial || 'null'})">
                    <i class="fas fa-redo me-1"></i> Reintentar
                </button>
            </div>
        </div>
    `;
}

function mostrarErrorDetalle(mensaje) {
    const container = document.getElementById('cuerpoProductosDevueltos');
    container.innerHTML = `
        <tr>
            <td colspan="7" class="text-center py-4">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    ${mensaje}
                </div>
            </td>
        </tr>
    `;
}

// Configurar botón de imprimir
document.getElementById('btnImprimirHistorial').addEventListener('click', function() {
    // Aquí puedes implementar la funcionalidad de impresión
    window.print();
});

// Inicializar tooltips
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips en el modal de historial
    const modalHistorial = document.getElementById('modalHistorialDevoluciones');
    if (modalHistorial) {
        modalHistorial.addEventListener('shown.bs.modal', function() {
            var tooltipTriggerList = [].slice.call(
                this.querySelectorAll('[data-bs-toggle="tooltip"]')
            );
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    }
});

// En js.js, agregar estas funciones

// ===== PAGO MIXTO (SALDO + EFECTIVO) =====
// Agrega estas funciones a tu js.js

// ===== PAGO MIXTO (SALDO + EFECTIVO) =====



// Configurar evento para el checkbox
document.getElementById('habilitarPagoMixto')?.addEventListener('change', function(e) {
    const detalle = document.getElementById('detallePagoMixto');
    pagoMixtoHabilitado = e.target.checked;
    
    if (pagoMixtoHabilitado) {
        detalle.style.display = 'block';
        // Habilitar botón de confirmar
        document.getElementById('btnConfirmarVentaModal').disabled = false;
    } else {
        detalle.style.display = 'none';
        // Deshabilitar botón de confirmar
        document.getElementById('btnConfirmarVentaModal').disabled = true;
    }
});

function mostrarOpcionPagoMixtoSimple(totalVenta, saldoActual) {
  
    
    const contenedor = document.getElementById('opcionPagoMixto');
    const diferenciaElement = document.getElementById('diferenciaPago');
    
    if (!contenedor) {
        console.error('❌ No se encontró el contenedor #opcionPagoMixto');
        return;
    }
    
    const diferencia = totalVenta - saldoActual;
 
    
    // Mostrar si hay diferencia significativa (más de 0.01)
    if (diferencia > 0.01) {
       
        
        // Mostrar el contenedor
        contenedor.style.display = 'block';
        
        // Actualizar valores
        diferenciaElement.textContent = `$${diferencia.toFixed(2)}`;
        document.getElementById('pagoSaldo').value = `$${saldoActual.toFixed(2)}`;
        document.getElementById('pagoEfectivo').value = diferencia.toFixed(2);
        
        // Deshabilitar botón de confirmar
        document.getElementById('btnConfirmarVentaModal').disabled = true;
        
    } else {
      
        contenedor.style.display = 'none';
        
        // Habilitar botón si hay productos y responsable
        const tieneProductos = productosSeleccionados.length > 0;
        const responsableValido = validarResponsable();
        document.getElementById('btnConfirmarVentaModal').disabled = !(tieneProductos && responsableValido);
    }
}

// Validar pago mixto cuando se confirme la venta
function validarPagoMixto(totalVenta) {
    const saldoActual = obtenerSaldoActualCliente();
    
    if (totalVenta > saldoActual) {
        if (!pagoMixtoHabilitado) {
            return {
                valido: false,
                mensaje: `Saldo insuficiente. Diferencia: $${(totalVenta - saldoActual).toFixed(2)}\nActive la opción de pago mixto.`
            };
        }
        
        // Validar pago en efectivo
        const pagoEfectivo = parseFloat(document.getElementById('pagoEfectivo').value) || 0;
        if (pagoEfectivo <= 0 || isNaN(pagoEfectivo)) {
            return {
                valido: false,
                mensaje: 'Ingrese un monto válido para el pago en efectivo.'
            };
        }
        
        const diferencia = totalVenta - saldoActual;
        if (pagoEfectivo < diferencia) {
            return {
                valido: false,
                mensaje: `El pago en efectivo debe ser al menos $${diferencia.toFixed(2)}.`
            };
        }
        
        // Todo válido
        return {
            valido: true,
            datos: {
                habilitado: true,
                pago_saldo: saldoActual,
                pago_efectivo: pagoEfectivo,
                observaciones: document.getElementById('observacionesPagoMixto')?.value || 
                              `Cliente pagó $${pagoEfectivo.toFixed(2)} en efectivo por diferencia de saldo`
            }
        };
    }
    
    // Saldo suficiente, no necesita pago mixto
    return {
        valido: true,
        datos: null
    };
}

// Modificar calcularImpuestosYTotal para incluir verificación de saldo
function calcularImpuestosYTotalConPagoMixto() {
    calcularImpuestosYTotal();
    verificarSaldoYMostrarOpcionMixto();
}

// Inicializar eventos al cargar
document.addEventListener('DOMContentLoaded', function() {
    // Evento para habilitar/deshabilitar pago mixto
    const checkboxPagoMixto = document.getElementById('habilitarPagoMixto');
    if (checkboxPagoMixto) {
        checkboxPagoMixto.addEventListener('change', function() {
            const detalle = document.getElementById('detallePagoMixto');
            if (this.checked) {
                detalle.style.display = 'block';
                // Auto-focus en el campo de efectivo
                document.getElementById('pagoEfectivo').focus();
            } else {
                detalle.style.display = 'none';
            }
        });
    }
    //
     const selectTipoPago = document.getElementById('tipoPagoCierre');

    if (selectTipoPago) {
        selectTipoPago.addEventListener('change', function () {
            if (this.value) {
                this.classList.remove('is-invalid');
            }
        });
    }
    // Evento para validar pago en efectivo
    const inputEfectivo = document.getElementById('pagoEfectivo');
    if (inputEfectivo) {
        inputEfectivo.addEventListener('input', function() {
            const valor = parseFloat(this.value) || 0;
            const saldoActual = obtenerSaldoActualCliente();
            const total = parseFloat(document.getElementById('modalTotal')?.textContent?.replace(/[^0-9.-]+/g, "") || 0);
            const diferencia = total - saldoActual;
            
            if (valor < diferencia) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            } else {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        });
    }
});