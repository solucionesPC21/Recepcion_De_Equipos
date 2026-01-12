document.addEventListener('DOMContentLoaded', function() {
    let productos = [];
    let contadorProductos = 0;
    let productoArrastrado = null;
    let descuentoPorcentaje = 0;

    // Constantes
    const IVA = 0.08;
    const ISR = 0.0125;
    const TRANSPORTE = 12;
    const urlParams = new URLSearchParams(window.location.search);
    const cotizacionId = urlParams.get('editar');


    // Elementos del DOM
    const elementos = {
        claveProductoInput: document.getElementById('claveProducto'), // ✅ NUEVO
        nombreInput: document.getElementById('nombreProducto'),
        cantidadInput: document.getElementById('cantidadProducto'),
        precioInput: document.getElementById('precioProducto'),
        transporteGeneralSelect: document.getElementById('transporteGeneral'),
        tipoClienteSelect: document.getElementById('tipoCliente'),
        precioFinalSpan: document.getElementById('precioFinalProducto'),
        precioSinIvaSpan: document.getElementById('precioSinIvaProducto'),
        btnAgregarMas: document.getElementById('btnAgregarMas'),
        productosCotizacion: document.getElementById('productosCotizacion'),
        btnAgregarProducto: document.getElementById('btnAgregarProducto'),
        clienteInput: document.getElementById('cliente'),
        direccionCliente: document.getElementById('direccionCliente'),
        telefonoCliente: document.getElementById('telefonoCliente'),
        validoHastaInput: document.querySelector('input[name="valido_hasta"]'),
        descuentoInput: document.getElementById('descuentoPorcentaje'),
        btnAplicarDescuento: document.getElementById('btnAplicarDescuento'),
        btnQuitarDescuento: document.getElementById('btnQuitarDescuento'),
        infoDescuentoProducto: document.getElementById('infoDescuentoProducto'),
        porcentajeDescuentoProducto: document.getElementById('porcentajeDescuentoProducto'),
        totalDescuentoRow: document.getElementById('totalDescuentoRow'),
        totalDescuento: document.getElementById('totalDescuento'),
        porcentajeDescuentoTotal: document.getElementById('porcentajeDescuentoTotal'),
        sinGanancia: document.getElementById('sinGanancia'),
        infoSinGanancia: document.getElementById('infoSinGanancia')
    };

    // Rangos de ganancia
    const rangosGanancia = [
        { max: 100, porcentaje: 0.30 },
        { max: 400, porcentaje: 0.28 },
        { max: 800, porcentaje: 0.25 },
        { max: 1000, porcentaje: 0.23 },
        { max: 2000, porcentaje: 0.20 },
        { max: 3000, porcentaje: 0.18 },
        { max: Infinity, porcentaje: 0.15 }
    ];

    // Convertir texto a mayúsculas en campos específicos
    ['cliente', 'direccionCliente'].forEach(id => {
        const input = document.getElementById(id);
        if (input) {
            input.addEventListener('input', () => {
                input.value = input.value.toUpperCase();
            });
        }
    });

    // Función unificada para obtener porcentaje de ganancia
    function obtenerPorcentajeGanancia(costoConIvaYTransporte) {
        const rango = rangosGanancia.find(r => costoConIvaYTransporte <= r.max);
        return rango ? rango.porcentaje : 0.15; // ✅ RETORNA DECIMAL EXACTO
    }

    // Función para determinar si aplica transporte
    function debeAplicarTransporte(precioCosto) {
        return elementos.transporteGeneralSelect.value === 'si' && precioCosto > 30;
    }


if (cotizacionId) {
    cargarCotizacionParaEditar(cotizacionId);
}

// FUNCIÓN PARA CARGAR COTIZACIÓN EXISTENTE
// FUNCIÓN PARA CARGAR COTIZACIÓN EXISTENTE (CORREGIDA)
async function cargarCotizacionParaEditar(id) {
    try {
      
        const response = await fetch(`/cotizaciones/${id}/editar`);
        const data = await response.json();
        
        if (data.success) {
            const cotizacion = data.data;
           
            
            // ✅ 1. LIMPIAR TODO ANTES DE CARGAR
            productos = [];
            contadorProductos = 0;
            descuentoPorcentaje = cotizacion.descuentoPorcentaje || 0; // ✅ MANTENER DESCUENTO
            
            // Llenar formulario
            elementos.clienteInput.value = cotizacion.cliente || '';
            elementos.tipoClienteSelect.value = cotizacion.tipo_cliente || 'persona_fisica';
            elementos.direccionCliente.value = cotizacion.direccion || '';
            elementos.telefonoCliente.value = cotizacion.telefono || '';
            elementos.validoHastaInput.value = cotizacion.valido_hasta || '';
            
            // ✅ 2. Configurar transporte general
            elementos.transporteGeneralSelect.value = cotizacion.transporteGeneral || 'no';
            
            // ✅ 3. CONFIGURAR DESCUENTO (NO RESETEAR)
            if (cotizacion.descuentoPorcentaje > 0) {
                elementos.descuentoInput.value = cotizacion.descuentoPorcentaje;
                elementos.descuentoInput.disabled = true;
                elementos.btnAplicarDescuento.style.display = 'none';
                elementos.btnQuitarDescuento.style.display = 'inline-block';
            } else {
                elementos.descuentoInput.value = '';
                elementos.descuentoInput.disabled = false;
                elementos.btnAplicarDescuento.style.display = 'inline-block';
                elementos.btnQuitarDescuento.style.display = 'none';
            }
            
            // ✅ 4. Cargar productos con SUS PRECIOS ORIGINALES
            if (cotizacion.productos && Array.isArray(cotizacion.productos)) {
              
                
                cotizacion.productos.forEach((productoJSON, index) => {
                    
                    
                    // ✅ CREAR PRODUCTO SIN PASAR POR AGREGARPRODUCTO (DIRECTAMENTE)
                    contadorProductos++;
                    
                    // Determinar si es sin ganancia
                    const sinGanancia = Boolean(productoJSON.sinGanancia);
                    
                    // Determinar transporte (0 si es sin ganancia)
                    let aplicaTransporte, transporte;
                    if (sinGanancia) {
                        aplicaTransporte = false;
                        transporte = 0;
                    } else {
                        aplicaTransporte = Boolean(productoJSON.aplicaTransporte) || false;
                        transporte = Number(productoJSON.transporte) || 0;
                    }
                    
                    // ✅ CALCULAR PRECIOS DIRECTAMENTE (CONSISTENTE)
                    const calculo = calcularPrecioFinal(
                        Number(productoJSON.precioCosto) || 0,
                        sinGanancia,
                        descuentoPorcentaje, // Usar el descuento que ya tenemos
                        false, // No es producto nuevo
                        aplicaTransporte,
                        transporte,
                        productoJSON.precioSinIvaOriginal // ✅ PASAR EL PRECIO ORIGINAL DEL JSON
                    );
                    
                    const producto = {
                        id: contadorProductos,
                        claveProducto: productoJSON.claveProducto || null,
                        nombre: productoJSON.nombre.trim(),
                        cantidad: Number(productoJSON.cantidad) || 0,
                        precioCosto: Number(productoJSON.precioCosto) || 0,
                        aplicaTransporte: aplicaTransporte,
                        transporte: transporte,
                        porcentajeGanancia: sinGanancia ? 0 : (productoJSON.porcentajeGanancia || calculo.porcentajeGanancia),
                        precioSinIvaFinal: calculo.precioSinIvaFinal,
                        precioSinIvaFinalConDescuento: calculo.precioSinIvaFinalConDescuento,
                        precioSinIvaOriginal: calculo.precioSinIvaOriginal, // ✅ USAR EL DEL CÁLCULO
                        precioFinal: calculo.precioFinal,
                        subtotal: Math.round(calculo.precioFinal * productoJSON.cantidad * 100) / 100,
                        ivaFinal: calculo.ivaFinal,
                        ivaFinalTotal: Math.round(calculo.ivaFinal * productoJSON.cantidad * 100) / 100,
                        descuentoAplicado: calculo.descuentoAplicado,
                        descuentoMonto: calculo.descuentoMonto,
                        descuentoMontoTotal: Math.round(calculo.descuentoMonto * productoJSON.cantidad * 100) / 100,
                        sinGanancia: sinGanancia,
                        subtotalSinDescuento: Math.round(calculo.precioFinalSinDescuento * productoJSON.cantidad * 100) / 100,
                        subtotalOriginalSinIva: Math.round(calculo.precioSinIvaOriginal * productoJSON.cantidad * 100) / 100,
                        precioFinalSinDescuento: calculo.precioFinalSinDescuento,
                        fueRedondeado: false,
                        porcentajeDescuentoAplicado: descuentoPorcentaje,
                        isrPorProducto: calculo.isrPorProducto,
                        isrTotal: Math.round(calculo.isrPorProducto * productoJSON.cantidad * 100) / 100,
                        esPersonaMoral: calculo.esPersonaMoral
                    };
                    
                    productos.push(producto);
                    
                });
            }
            
            // ✅ 5. ACTUALIZAR UI DIRECTAMENTE (NO RECALCULAR DESDE CERO)
          
            actualizarTabla();
            calcularTotales();
            calcularPreciosEnTiempoReal();
            
            // Cambiar título
            document.title = `Editando: ${cotizacion.cliente} - Cotización`;
            
         
            
            // Mostrar mensaje
            Swal.fire({
                title: 'Modo Edición',
                text: 'Está editando una cotización existente',
                icon: 'info',
                timer: 3000,
                showConfirmButton: false
            });
        } else {
            throw new Error(data.error || 'Error al cargar la cotización');
        }
    } catch (error) {
        console.error('❌ Error cargando cotización:', error);
        Swal.fire('Error', 'No se pudo cargar la cotización: ' + error.message, 'error');
    }
}
// ✅ NUEVA FUNCIÓN: Recalcular solo lo necesario
function recalcularTodoDesdeCeroConDescuento(nuevoDescuento) {
     
    // Si no hay productos, no hacer nada
    if (productos.length === 0) return;
    
    // Actualizar el descuento global
    descuentoPorcentaje = nuevoDescuento;
    
    // Recalcular cada producto manteniendo sus propiedades base
    productos.forEach(producto => {
        const nuevoCalculo = calcularPrecioFinal(
            producto.precioCosto,
            producto.sinGanancia,
            nuevoDescuento,
            false, // No es nuevo
            producto.aplicaTransporte,
            producto.transporte,
            producto.precioSinIvaOriginal // ✅ PASAR EL PRECIO ORIGINAL
        );
        
        // Actualizar solo los campos afectados por el descuento
        producto.precioSinIvaFinalConDescuento = nuevoCalculo.precioSinIvaFinalConDescuento;
        producto.precioFinal = nuevoCalculo.precioFinal;
        producto.subtotal = Math.round(nuevoCalculo.precioFinal * producto.cantidad * 100) / 100;
        producto.ivaFinal = nuevoCalculo.ivaFinal;
        producto.ivaFinalTotal = Math.round(nuevoCalculo.ivaFinal * producto.cantidad * 100) / 100;
        producto.descuentoAplicado = nuevoCalculo.descuentoAplicado;
        producto.descuentoMonto = nuevoCalculo.descuentoMonto;
        producto.descuentoMontoTotal = Math.round(nuevoCalculo.descuentoMonto * producto.cantidad * 100) / 100;
        producto.subtotalSinDescuento = Math.round(nuevoCalculo.precioFinalSinDescuento * producto.cantidad * 100) / 100;
        producto.porcentajeDescuentoAplicado = nuevoDescuento;
        producto.isrPorProducto = nuevoCalculo.isrPorProducto;
        producto.isrTotal = Math.round(nuevoCalculo.isrPorProducto * producto.cantidad * 100) / 100;
    });
    
    // Actualizar UI
    actualizarTabla();
    calcularTotales();
    calcularPreciosEnTiempoReal();
    
 
}

// ✅ FUNCIÓN PARA MANEJAR CAMBIOS EN TIPO DE CLIENTE DURANTE EDICIÓN
function manejarCambioTipoClienteEnEdicion() {
  
    
    // Recalcular todos los productos
    productos.forEach(producto => {
        const nuevoCalculo = calcularPrecioFinal(
            producto.precioCosto,
            producto.sinGanancia,
            descuentoPorcentaje,
            false, // ⚠️ Es edición, no es nuevo
            producto.aplicaTransporte,
            producto.transporte
        );
        
        // Actualizar propiedades
        producto.precioSinIvaFinal = nuevoCalculo.precioSinIvaFinal;
        producto.precioSinIvaFinalConDescuento = nuevoCalculo.precioSinIvaFinalConDescuento;
        producto.precioFinal = nuevoCalculo.precioFinal;
        producto.precioFinalSinDescuento = nuevoCalculo.precioFinalSinDescuento;
        producto.subtotal = nuevoCalculo.precioFinal * producto.cantidad;
        producto.ivaFinal = nuevoCalculo.ivaFinal;
        producto.ivaFinalTotal = nuevoCalculo.ivaFinal * producto.cantidad;
        producto.isrPorProducto = nuevoCalculo.isrPorProducto;
        producto.isrTotal = nuevoCalculo.isrPorProducto * producto.cantidad;
        producto.esPersonaMoral = nuevoCalculo.esPersonaMoral;
    });
    
    actualizarTabla();
    calcularTotales();
}

// ✅ MODIFICAR EL EVENT LISTENER PARA TIPO DE CLIENTE
elementos.tipoClienteSelect.addEventListener('change', function() {
   
    
    if (productos.length > 0) {
        // Si estamos editando una cotización, usar función especial
        manejarCambioTipoClienteEnEdicion();
    } else {
        // Si no hay productos, solo actualizar cálculo en tiempo real
        calcularPreciosEnTiempoReal();
    }
});
// Función principal para calcular el precio final
// Función principal para calcular el precio final (MODIFICADA)
// Función principal para calcular el precio final (CORREGIDA)
// Función principal para calcular el precio final (CORREGIDA)
// Función principal para calcular el precio final (REVISADA PARA CONSISTENCIA)
function calcularPrecioFinal(precioCosto, sinGanancia = false, descuentoAplicar = descuentoPorcentaje, esNuevoProducto = true, aplicaTransporteParam = null, transporteParam = null, precioSinIvaOriginalCache = null) {
    let aplicaTransporte = false;
    let transporte = 0;
    let porcentajeGanancia = 0;
    let ivaInicial = 0;
    let costoConIvaYTransporte = 0;
    let precioSinIvaFinal = 0;
    let descuentoMonto = 0;
    let precioSinIvaFinalConDescuento = 0;
    let precioSinIvaOriginal = 0;
    let ivaFinal = 0;
    let precioFinal = 0;
    let precioFinalSinDescuento = 0;
    let isrPorProducto = 0;

    const descuentoActual = descuentoAplicar !== undefined ? descuentoAplicar : descuentoPorcentaje;
    const esPersonaMoral = elementos.tipoClienteSelect.value === 'persona_moral';
    
    // ✅ 1. DETERMINAR TRANSPORTE (SIN GANANCIA = SIN TRANSPORTE)
    if (sinGanancia) {
        aplicaTransporte = false;
        transporte = 0;
    } 
    else if (aplicaTransporteParam !== null && transporteParam !== null) {
        aplicaTransporte = Boolean(aplicaTransporteParam);
        transporte = Number(transporteParam) || 0;
    } 
    else {
        aplicaTransporte = debeAplicarTransporte(precioCosto);
        transporte = aplicaTransporte ? TRANSPORTE : 0;
    }
    
    // ✅ 2. SI TENEMOS CACHE, USARLO (PARA CONSISTENCIA)
    if (precioSinIvaOriginalCache !== null && precioSinIvaOriginalCache > 0) {
        precioSinIvaOriginal = precioSinIvaOriginalCache;
      
    }
    
    // ✅ 3. CÁLCULO PARA PRODUCTOS "SIN GANANCIA" (REVISADO)
    if (sinGanancia) {
        precioFinalSinDescuento = precioCosto;
        
        // ✅ CALCULAR PRECIO SIN IVA ORIGINAL DE FORMA CONSISTENTE
        if (precioSinIvaOriginal <= 0) { // Si no hay cache, calcular
            if (esPersonaMoral) {
                // ✅ FÓRMULA ÚNICA PARA PERSONA MORAL (CONSISTENTE)
                // Esta fórmula debe ser la MISMA en creación y edición
                precioSinIvaOriginal = precioFinalSinDescuento / 1.0675;
            
            } else {
                // ✅ PERSONA FÍSICA
                precioSinIvaOriginal = precioFinalSinDescuento / (1 + IVA);
             
            }
        }
        
        precioSinIvaFinal = precioSinIvaOriginal;
        ivaFinal = precioSinIvaOriginal * IVA;
        isrPorProducto = esPersonaMoral ? precioSinIvaOriginal * ISR : 0;
        precioFinal = precioFinalSinDescuento;
        
        // ✅ APLICAR DESCUENTO (CONSISTENTE)
        if (descuentoActual > 0) {
            // 1. Calcular descuento sobre el precio FINAL
            descuentoMonto = precioFinalSinDescuento * (descuentoActual / 100);
            descuentoMonto = Math.round(descuentoMonto * 100) / 100;
            
            // 2. Restar al precio final
            precioFinal = precioFinalSinDescuento - descuentoMonto;
            precioFinal = Math.round(precioFinal * 100) / 100;
            
            // 3. Recalcular precio sin IVA a partir del precio final con descuento
            if (esPersonaMoral) {
                // ✅ MISMA FÓRMULA QUE ARRIBA
                precioSinIvaFinal = precioFinal / 1.0675;
                isrPorProducto = precioSinIvaFinal * ISR;
            } else {
                precioSinIvaFinal = precioFinal / (1 + IVA);
                isrPorProducto = 0;
            }
            
            precioSinIvaFinal = Math.round(precioSinIvaFinal * 100) / 100;
            precioSinIvaFinalConDescuento = precioSinIvaFinal;
            
            // 4. Recalcular IVA
            ivaFinal = precioSinIvaFinal * IVA;
            ivaFinal = Math.round(ivaFinal * 100) / 100;
        }
        
        precioSinIvaFinalConDescuento = precioSinIvaFinal;
        
    } else {
        // ✅ 4. PRODUCTOS CON GANANCIA (MANTENIDO)
        ivaInicial = precioCosto * IVA;
        costoConIvaYTransporte = precioCosto + ivaInicial + transporte;
        porcentajeGanancia = obtenerPorcentajeGanancia(costoConIvaYTransporte);
        
        // CALCULAR PRECIO SIN IVA ORIGINAL
        if (precioSinIvaOriginal <= 0) {
            precioSinIvaOriginal = costoConIvaYTransporte / (1 - porcentajeGanancia);
            precioSinIvaOriginal = Math.round(precioSinIvaOriginal * 100) / 100;
        }
        
        precioSinIvaFinal = precioSinIvaOriginal;
        precioFinalSinDescuento = precioSinIvaOriginal + (precioSinIvaOriginal * IVA);
        precioFinalSinDescuento = Math.round(precioFinalSinDescuento * 100) / 100;

        precioSinIvaFinalConDescuento = precioSinIvaOriginal;
        precioFinal = precioFinalSinDescuento;
        ivaFinal = precioSinIvaOriginal * IVA;

        // APLICAR DESCUENTO
        if (descuentoActual > 0) {
            descuentoMonto = precioFinalSinDescuento * (descuentoActual / 100);
            descuentoMonto = Math.round(descuentoMonto * 100) / 100;
            
            precioFinal = precioFinalSinDescuento - descuentoMonto;
            precioFinal = Math.round(precioFinal * 100) / 100;
            
            precioSinIvaFinalConDescuento = precioFinal / (1 + IVA);
            precioSinIvaFinalConDescuento = Math.round(precioSinIvaFinalConDescuento * 100) / 100;
            
            ivaFinal = precioFinal - precioSinIvaFinalConDescuento;
            ivaFinal = Math.round(ivaFinal * 100) / 100;
        }

        isrPorProducto = esPersonaMoral ? precioSinIvaOriginal * ISR : 0;
        isrPorProducto = Math.round(isrPorProducto * 100) / 100;
    }

    return {
        precioSinIvaFinal,
        precioSinIvaFinalConDescuento,
        precioSinIvaOriginal, // ✅ SIEMPRE EL MISMO VALOR
        precioFinal,
        precioFinalSinDescuento,
        ivaFinal,
        porcentajeGanancia: sinGanancia ? 0 : porcentajeGanancia * 100,
        transporte,
        aplicaTransporte,
        descuentoMonto,
        descuentoAplicado: descuentoActual > 0,
        sinGanancia,
        fueRedondeado: false,
        porcentajeDescuentoAplicado: descuentoActual,
        isrPorProducto,
        esPersonaMoral
    };
}
//
// ✅ FUNCIÓN AUXILIAR: Cálculos precisos
function calcularConPrecision(valor1, valor2, operacion = 'multiplicar') {
    const factor = 100; // Usar 2 decimales de precisión
    
    switch(operacion) {
        case 'multiplicar':
            return Math.round((valor1 * valor2) * factor) / factor;
        case 'dividir':
            return Math.round((valor1 / valor2) * factor) / factor;
        case 'sumar':
            return Math.round((valor1 + valor2) * factor) / factor;
        case 'restar':
            return Math.round((valor1 - valor2) * factor) / factor;
        default:
            return Math.round(valor1 * factor) / factor;
    }
}
// ✅ NUEVA FUNCIÓN: Recalcular todos los productos cuando cambie el tipo de cliente
// ✅ NUEVA FUNCIÓN: Recalcular todos los productos cuando cambie el tipo de cliente
function recalcularTodosLosProductos() {
    if (productos.length === 0) return;
    

    
    // Recalcular cada producto con el nuevo tipo de cliente
    productos.forEach(producto => {
    
        const nuevoCalculo = calcularPrecioFinal(producto.precioCosto, producto.sinGanancia, descuentoPorcentaje, false);
        
        // ✅ ACTUALIZAR TODAS LAS PROPIEDADES (INCLUYENDO LAS NUEVAS)
        producto.precioSinIvaFinal = nuevoCalculo.precioSinIvaFinal;
        producto.precioSinIvaFinalConDescuento = nuevoCalculo.precioSinIvaFinalConDescuento;
        producto.precioSinIvaOriginal = nuevoCalculo.precioSinIvaOriginal; // ✅ NUEVA
        producto.precioFinal = nuevoCalculo.precioFinal;
        producto.precioFinalSinDescuento = nuevoCalculo.precioFinalSinDescuento;
        producto.subtotal = nuevoCalculo.precioFinal * producto.cantidad;
        producto.ivaFinal = nuevoCalculo.ivaFinal;
        producto.ivaFinalTotal = nuevoCalculo.ivaFinal * producto.cantidad;
        producto.descuentoAplicado = nuevoCalculo.descuentoAplicado;
        producto.descuentoMonto = nuevoCalculo.descuentoMonto;
        producto.descuentoMontoTotal = nuevoCalculo.descuentoMonto * producto.cantidad;
        producto.subtotalSinDescuento = nuevoCalculo.precioFinalSinDescuento * producto.cantidad;
        producto.subtotalOriginalSinIva = nuevoCalculo.precioSinIvaOriginal * producto.cantidad; // ✅ NUEVA
        producto.porcentajeGanancia = nuevoCalculo.porcentajeGanancia;
        producto.isrPorProducto = nuevoCalculo.isrPorProducto;
        producto.isrTotal = nuevoCalculo.isrPorProducto * producto.cantidad;
        producto.esPersonaMoral = nuevoCalculo.esPersonaMoral;
    });
    
    // Actualizar la tabla visual y los totales
    actualizarTabla();
    calcularTotales();
}
  // Función para calcular precios en tiempo real (optimizada para evitar repeticiones)
// Función para calcular precios en tiempo real (ACTUALIZADA)
function calcularPreciosEnTiempoReal() {
    const precioCosto = parseFloat(elementos.precioInput.value) || 0;
    const sinGanancia = elementos.sinGanancia?.checked || false;

    if (precioCosto > 0) {
        const resultado = calcularPrecioFinal(precioCosto, sinGanancia, descuentoPorcentaje);
        elementos.precioSinIvaSpan.textContent = `$${resultado.precioSinIvaFinalConDescuento.toFixed(2)}`;
        elementos.precioFinalSpan.textContent = `$${resultado.precioFinal.toFixed(2)}`;

        if (elementos.infoSinGanancia) {
            elementos.infoSinGanancia.style.display = sinGanancia ? 'block' : 'none';
        }

        // ✅ TÍTULO MEJORADO Y SIMPLIFICADO
        let titulo = "";
        
        if (sinGanancia) {
            titulo = "🚫 SIN GANANCIA - No aplica transporte";
        } else {
            titulo = resultado.aplicaTransporte ? 
                "🚚 Incluye $12 de transporte (precio > $30)" : 
                "📦 No incluye transporte";
        }
        
        if (descuentoPorcentaje > 0) {
            titulo += `\n🎁 ${descuentoPorcentaje}% de descuento aplicado`;
        }
        
        // ✅ AGREGAR INFORMACIÓN DEL TIPO DE CLIENTE
        if (elementos.tipoClienteSelect.value === 'persona_moral') {
            titulo += "\n📋 Persona Moral (Aplica ISR 1.25%)";
        } else {
            titulo += "\n📋 Persona Física/Público General";
        }
        
        titulo += "\n✅ Precios exactos para facturación";
        
        elementos.precioFinalSpan.title = titulo;
        elementos.precioSinIvaSpan.title = titulo;

        if (elementos.infoDescuentoProducto && elementos.porcentajeDescuentoProducto) {
            if (descuentoPorcentaje > 0) {
                elementos.infoDescuentoProducto.style.display = 'block';
                elementos.porcentajeDescuentoProducto.textContent = `${descuentoPorcentaje}%`;
            } else {
                elementos.infoDescuentoProducto.style.display = 'none';
            }
        }
        
        // ✅ CAMBIAR COLOR PARA SIN GANANCIA
        if (sinGanancia) {
            elementos.precioFinalSpan.style.color = '#e67e22'; // Naranja
            elementos.precioSinIvaSpan.style.color = '#e67e22';
        } else {
            elementos.precioFinalSpan.style.color = '#28a745'; // Verde
            elementos.precioSinIvaSpan.style.color = '#007bff'; // Azul
        }
    } else {
        elementos.precioSinIvaSpan.textContent = '$0.00';
        elementos.precioFinalSpan.textContent = '$0.00';
        elementos.precioFinalSpan.title = "";
        elementos.precioSinIvaSpan.title = "";
        elementos.precioFinalSpan.style.color = '';
        elementos.precioSinIvaSpan.style.color = '';
        
        if (elementos.infoSinGanancia) {
            elementos.infoSinGanancia.style.display = 'none';
        }
        
        if (elementos.infoDescuentoProducto) {
            elementos.infoDescuentoProducto.style.display = 'none';
        }
    }
}
    // Event listeners para cálculos en tiempo real
    elementos.precioInput.addEventListener('input', calcularPreciosEnTiempoReal);
    elementos.transporteGeneralSelect.addEventListener('change', calcularPreciosEnTiempoReal);
    elementos.sinGanancia.addEventListener('change', calcularPreciosEnTiempoReal);

    // Función para agregar producto (optimizada con validaciones unificadas)
   // Función para agregar producto (CORREGIDA)
// Función para agregar producto (CORREGIDA PARA FORZAR TRANSPORTE 0 EN SIN GANANCIA)
function agregarProducto(clave, nombre, cantidad, precioCosto, sinGananciaParam = false, aplicaTransporteParam = false, transporteParam = 0) {
    contadorProductos++;
    
    // ✅ 1. DETERMINAR sinGanancia SIN AMBIGÜEDADES
    let sinGanancia;
    
    if (sinGananciaParam !== false) {
        sinGanancia = Boolean(sinGananciaParam);
      
    } 
    else if (elementos.sinGanancia) {
        sinGanancia = elementos.sinGanancia.checked;
     
    }
    else {
        sinGanancia = false;
    }
    
    sinGanancia = !!sinGanancia; // Doble negación para asegurar booleano
  
    
    // ✅ 2. DETERMINAR TRANSPORTE - FORZAR 0 SI ES SIN GANANCIA
    let aplicaTransporte, transporte;
    
    if (sinGanancia) {
        // ✅ PRODUCTOS SIN GANANCIA: TRANSPORTE SIEMPRE 0
        aplicaTransporte = false;
        transporte = 0;
      
    } 
    else {
        // ✅ PRODUCTOS CON GANANCIA: Usar lógica normal
        const vieneDeEdicion = aplicaTransporteParam !== false || transporteParam !== 0;
        
        if (vieneDeEdicion) {
            aplicaTransporte = Boolean(aplicaTransporteParam);
            transporte = Number(transporteParam) || 0;
          
        } else {
            aplicaTransporte = debeAplicarTransporte(precioCosto);
            transporte = aplicaTransporte ? TRANSPORTE : 0;
          
        }
    }
    
    // ✅ 3. CALCULAR (pasar !vieneDeEdicion como esNuevoProducto)
    const vieneDeEdicion = aplicaTransporteParam !== false || transporteParam !== 0;
    const calculo = calcularPrecioFinal(
        precioCosto, 
        sinGanancia, 
        descuentoPorcentaje, 
        !vieneDeEdicion,
        aplicaTransporte,
        transporte
    );
    
    
    // ✅ 4. CREAR PRODUCTO (el resto del código se mantiene igual)
    const producto = {
        id: contadorProductos,
        claveProducto: clave || null,
        nombre: nombre.trim(),
        cantidad: Number(cantidad) || 0,
        precioCosto: Number(precioCosto) || 0,
        aplicaTransporte: aplicaTransporte, // ✅ Ahora siempre false para sin ganancia
        transporte: transporte, // ✅ Ahora siempre 0 para sin ganancia
        porcentajeGanancia: sinGanancia ? 0 : calculo.porcentajeGanancia,
        precioSinIvaFinal: calculo.precioSinIvaFinal,
        precioSinIvaFinalConDescuento: calculo.precioSinIvaFinalConDescuento,
        precioSinIvaOriginal: calculo.precioSinIvaOriginal,
        precioFinal: calculo.precioFinal,
        subtotal: Math.round(calculo.precioFinal * cantidad * 100) / 100,
        ivaFinal: calculo.ivaFinal,
        ivaFinalTotal: Math.round(calculo.ivaFinal * cantidad * 100) / 100,
        descuentoAplicado: calculo.descuentoAplicado,
        descuentoMonto: calculo.descuentoMonto,
        descuentoMontoTotal: Math.round(calculo.descuentoMonto * cantidad * 100) / 100,
        sinGanancia: sinGanancia,
        subtotalSinDescuento: Math.round(calculo.precioFinalSinDescuento * cantidad * 100) / 100,
        subtotalOriginalSinIva: Math.round(calculo.precioSinIvaOriginal * cantidad * 100) / 100,
        precioFinalSinDescuento: calculo.precioFinalSinDescuento,
        fueRedondeado: false,
        porcentajeDescuentoAplicado: descuentoPorcentaje,
        isrPorProducto: calculo.isrPorProducto,
        isrTotal: Math.round(calculo.isrPorProducto * cantidad * 100) / 100,
        esPersonaMoral: calculo.esPersonaMoral
    };
    
    productos.push(producto);
    actualizarTabla();
    calcularTotales();
}
//
// ✅ NUEVA FUNCIÓN: Recalcular todo desde cero
function recalcularTodoDesdeCero() {
    const productosBackup = [...productos];
    const descuentoBackup = descuentoPorcentaje;
    
    // Limpiar productos
    productos = [];
    contadorProductos = 0;
    
    // Re-agregar cada producto con los mismos datos de entrada
    productosBackup.forEach(productoBackup => {
        agregarProducto(
            productoBackup.claveProducto,
            productoBackup.nombre,
            productoBackup.cantidad,
            productoBackup.precioCosto,
            productoBackup.sinGanancia,
            productoBackup.aplicaTransporte, // ✅ Pasar transporte si existe
            productoBackup.transporte
        );
    });
    
    // Restaurar descuento si existía
    if (descuentoBackup > 0) {
        elementos.descuentoInput.value = descuentoBackup;
        aplicarDescuento();
    }
}
   // Event listener para agregar producto
    elementos.btnAgregarProducto.addEventListener('click', () => {
        const clave = elementos.claveProductoInput.value.trim(); // ✅ NUEVO
        const nombre = elementos.nombreInput.value.trim();
        const cantidad = parseInt(elementos.cantidadInput.value) || 0;
        const precioCosto = parseFloat(elementos.precioInput.value) || 0;

        if (!nombre) return alert('Por favor ingrese el nombre del producto');
        if (cantidad <= 0) return alert('La cantidad debe ser mayor a 0');
        if (precioCosto <= 0) return alert('El precio costo debe ser mayor a 0');
         // Clave es opcional, si está vacía usamos null
        const claveFinal = clave === '' ? null : clave;

        agregarProducto(claveFinal,nombre, cantidad, precioCosto);
        limpiarFormularioProducto(true);
    });

    // Función para limpiar formulario de producto
    function limpiarFormularioProducto(enfocar = true) {
    elementos.claveProductoInput.value = '';
    elementos.nombreInput.value = '';
    elementos.cantidadInput.value = '1';
    elementos.precioInput.value = '';
    elementos.precioSinIvaSpan.textContent = '$0.00';
    elementos.precioFinalSpan.textContent = '$0.00';
    elementos.precioFinalSpan.title = "";
    elementos.precioSinIvaSpan.title = "";
    // Verificación de existencia antes de modificar style
    if (elementos.infoDescuentoProducto) {
        elementos.infoDescuentoProducto.style.display = 'none';
    }

    if (enfocar) elementos.nombreInput.focus();
}

    // Botón para agregar más productos
    elementos.btnAgregarMas?.addEventListener('click', () => limpiarFormularioProducto(true));

    // Función optimizada para calcular descuento real (evita recalcs repetidos)
    function calcularDescuentoReal() {
        let totalDescuentoReal = 0;
        productos.forEach(producto => {
            if (descuentoPorcentaje > 0 && !producto.sinGanancia) {
                totalDescuentoReal += producto.descuentoMontoTotal;
            }
        });
        return totalDescuentoReal;
    }

    // Función para actualizar tabla (optimizada, elimina recalcs innecesarios)
 function actualizarTabla() {
    const tbody = elementos.productosCotizacion;
    tbody.innerHTML = '';

    if (productos.length === 0) {
        tbody.innerHTML = `
            <tr id="sin-productos">
                <td colspan="11" class="text-center text-muted py-3"> <!-- ✅ Cambiado de 10 a 11 -->
                    <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                    <p>No hay productos agregados</p>
                </td>
            </tr>
        `;
        return;
    }

    productos.forEach((producto, index) => {
        // ✅ CORRECCIÓN: Incluir productos "Sin Ganancia" en el cálculo de descuento
        const tieneDescuento = (producto.porcentajeDescuentoAplicado > 0 && producto.descuentoMonto > 0) || 
                              (producto.sinGanancia && producto.descuentoAplicado);
        
        const precioOriginalSinIva = producto.precioSinIvaFinal;
        const precioOriginalFinal = producto.precioFinalSinDescuento;
        const subtotalOriginal = producto.subtotalSinDescuento;
        const precioConDescuentoSinIva = producto.precioSinIvaFinalConDescuento;
        const precioConDescuentoFinal = producto.precioFinal;
        const subtotalConDescuento = producto.subtotal;
        const ahorroPorUnidadSinIva = precioOriginalSinIva - precioConDescuentoSinIva;
        const ahorroPorUnidadFinal = precioOriginalFinal - precioConDescuentoFinal;
        const ahorroTotal = subtotalOriginal - subtotalConDescuento;
        const porcentajeAhorro = precioOriginalSinIva > 0 ? (ahorroPorUnidadSinIva / precioOriginalSinIva) * 100 : 0;

        const barraProgresoHTML = porcentajeAhorro > 0 ? `
            <div class="progress-bar-descuento mt-1">
                <div class="progress-fill-descuento" style="width: ${porcentajeAhorro}%"></div>
            </div>
            <small class="text-muted d-block text-center">${porcentajeAhorro.toFixed(1)}% de descuento</small>
        ` : '';

        const tr = document.createElement('tr');
        tr.setAttribute('data-producto-id', producto.id);
        tr.setAttribute('draggable', 'true');
        tr.classList.add('fila-arrastrable');
        if (tieneDescuento) tr.classList.add('descuento-visual');
        if (producto.sinGanancia) tr.classList.add('sin-ganancia');

        tr.addEventListener('dragstart', manejarDragStart);
        tr.addEventListener('dragover', manejarDragOver);
        tr.addEventListener('dragenter', manejarDragEnter);
        tr.addEventListener('dragleave', manejarDragLeave);
        tr.addEventListener('drop', manejarDrop);
        tr.addEventListener('dragend', manejarDragEnd);

        // ✅ COLUMNA 1: CLAVE
const columnaClave = `
    <td class="text-center align-middle">
        ${producto.claveProducto ? 
            `<span class="badge bg-secondary">${producto.claveProducto}</span>` : 
            '<span class="text-muted">-</span>'
        }
    </td>
`;

// ✅ COLUMNA 2: PRODUCTO
const columnaProducto = `
    <td class="align-middle">
        <div class="d-flex align-items-center">
            <i class="fas fa-grip-vertical text-muted me-2 grip-handle cursor-grab"></i>
            <span class="product-name-display me-2">${producto.nombre}</span>
            ${tieneDescuento ? `<span class="etiqueta-descuento">-${descuentoPorcentaje}% OFF</span>` : ''}
            <button class="btn btn-sm btn-outline-primary"
                    type="button"
                    onclick="habilitarEdicionNombre(${producto.id})"
                    aria-label="Editar nombre del producto">
                <i class="fas fa-edit"></i>
            </button>
        </div>

        <!-- 🔹 CONTENEDOR DE EDICIÓN MEJORADO -->
        <div class="nombre-edit-container mt-2 p-2 bg-light rounded"
             style="display:none;"
             id="edit-container-${producto.id}">

            <!-- CLAVE -->
            <div class="mb-2">
                <label class="form-label small text-muted mb-1">Clave del producto</label>
                <input type="text"
                       class="form-control form-control-sm w-100"
                       id="edit-input-clave-${producto.id}"
                       value="${producto.claveProducto || ''}"
                       placeholder="Ej. ABC-123">
            </div>

            <!-- NOMBRE -->
            <div class="mb-3">
                <label class="form-label small text-muted mb-1">Nombre del producto</label>
                <input type="text"
                       class="form-control w-100"
                       id="edit-input-nombre-${producto.id}"
                       value="${producto.nombre}"
                       placeholder="Nombre completo del producto">
            </div>

            <!-- BOTONES -->
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-sm btn-success"
                        type="button"
                        onclick="guardarNombre(${producto.id})">
                    <i class="fas fa-check"></i> Guardar
                </button>
                <button class="btn btn-sm btn-outline-secondary"
                        type="button"
                        onclick="cancelarEdicion(${producto.id})">
                    <i class="fas fa-times"></i> Cancelar
                </button>
            </div>
        </div>

        ${tieneDescuento && !producto.sinGanancia ? `
            <div class="desglose-descuento mt-2 p-2 bg-light rounded small">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong class="text-success">🎁 Descuento Aplicado</strong>
                    <span class="badge bg-success">-${descuentoPorcentaje}%</span>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <small class="text-muted">Subtotal original:</small>
                        <div class="fw-bold text-danger text-decoration-line-through">$${subtotalOriginal.toFixed(2)}</div>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Subtotal con descuento:</small>
                        <div class="fw-bold text-success">$${subtotalConDescuento.toFixed(2)}</div>
                    </div>
                </div>
                <div class="row g-2 mt-1">
                    <div class="col-6">
                        <small class="text-muted">Ahorro total:</small>
                        <div class="fw-bold text-primary">$${ahorroTotal.toFixed(2)}</div>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Ahorro unitario:</small>
                        <div class="fw-bold text-info">$${ahorroPorUnidadFinal.toFixed(2)}</div>
                    </div>
                </div>
                ${barraProgresoHTML}
            </div>
        ` : ''}

        ${producto.sinGanancia ? `
            <div class="mt-1">
                <span class="badge bg-warning text-dark">💰 Sin Ganancia</span>
                ${elementos.tipoClienteSelect.value === 'persona_moral'
                    ? '<span class="badge bg-info ms-1">🚫 Exento ISR</span>'
                    : ''}
                ${producto.descuentoAplicado
                    ? `<span class="badge bg-success ms-1">-${descuentoPorcentaje}% OFF</span>`
                    : ''}
            </div>

            <div class="desglose-impuestos mt-2 p-2 bg-info text-white rounded small">
                <strong>💰 Desglose Precio Final ($${(producto.precioFinal * producto.cantidad).toFixed(2)}):</strong>
                <div class="row mt-1">
                    <div class="col-${producto.esPersonaMoral ? '3' : '6'}">
                        <small>Subtotal: $${(producto.precioSinIvaFinalConDescuento * producto.cantidad).toFixed(2)}</small>
                    </div>
                    <div class="col-${producto.esPersonaMoral ? '3' : '6'}">
                        <small>+ IVA: $${producto.ivaFinalTotal.toFixed(2)}</small>
                    </div>
                    ${producto.esPersonaMoral ? `
                        <div class="col-3">
                            <small>- ISR: $${producto.isrTotal.toFixed(2)}</small>
                        </div>
                        <div class="col-3">
                            <small>= Total: $${(producto.precioFinal * producto.cantidad).toFixed(2)}</small>
                        </div>
                    ` : `
                        <div class="col-6">
                            <small>= Total: $${(producto.precioFinal * producto.cantidad).toFixed(2)}</small>
                        </div>
                    `}
                </div>
            </div>
        ` : ''}
    </td>
`;


        // ✅ CONSTRUIR TODAS LAS COLUMNAS EN ORDEN
        tr.innerHTML = `
            ${columnaClave}
            ${columnaProducto}
            
            <!-- COLUMNA 3: CANTIDAD -->
            <td class="align-middle">
                <input type="number" class="form-control form-control-sm" 
                       value="${producto.cantidad}" min="1" 
                       onchange="actualizarCantidad(${producto.id}, this.value)" 
                       aria-label="Cantidad del producto">
                ${tieneDescuento ? `
                    <small class="text-muted d-block mt-1">
                        <i class="fas fa-tag me-1"></i>
                        Desc. total: <span class="text-danger fw-bold">$${producto.descuentoMontoTotal.toFixed(2)}</span>
                    </small>
                ` : ''}
            </td>
            
            <!-- COLUMNA 4: PRECIO COSTO -->
            <td class="align-middle">
                <div class="d-flex flex-column">
                    <span>$${producto.precioCosto.toFixed(2)}</span>
                    ${producto.sinGanancia && producto.descuentoAplicado ? `
                        <small class="text-muted">Precio original: $${producto.precioFinalSinDescuento.toFixed(2)}</small>
                        <small class="text-success">Con ${descuentoPorcentaje}% descuento</small>
                    ` : ''}
                    ${producto.sinGanancia ? `
                        <br><small class="text-muted">(Precio final deseado)</small>
                    ` : ''}
                </div>
            </td>
            
            <!-- ✅ COLUMNA 5: TRANSPORTE MODIFICADA -->
            <td class="align-middle text-center">
                ${producto.sinGanancia ? 
                    // ✅ SI ES SIN GANANCIA: SIEMPRE MOSTRAR $0.00
                    '<span class="text-muted">$0.00</span>' : 
                    // ✅ SI TIENE GANANCIA: MOSTRAR SEGÚN apliqueTransporte
                    (producto.aplicaTransporte ? 
                        '<span class="badge bg-warning">$12.00</span>' : 
                        '<span class="text-muted">$0.00</span>'
                    )
                }
                ${producto.sinGanancia ? 
                    '<br><small class="text-muted">(Sin ganancia)</small>' : 
                    (producto.aplicaTransporte ? 
                        '<br><small class="text-muted">(Precio > $30)</small>' : 
                        ''
                    )
                }
            </td>

            
            <!-- COLUMNA 6: % GANANCIA -->
            <td class="align-middle text-center">
                ${producto.sinGanancia ? 
                    '<span class="badge bg-warning text-dark">0%</span>' : 
                    `<span class="badge bg-info">${producto.porcentajeGanancia}%</span>`
                }
            </td>
            
            <!-- COLUMNA 7: PRECIO SIN IVA -->
            <td class="align-middle ${tieneDescuento ? 'fw-bold text-success' : 'fw-bold text-primary'}">
                <div class="d-flex flex-column">
                    <span>$${precioConDescuentoSinIva.toFixed(2)}</span>
                    ${producto.sinGanancia && producto.descuentoAplicado ? `
                        <small class="text-danger text-decoration-line-through">$${producto.precioSinIvaOriginal.toFixed(2)}</small>
                        <small class="text-primary">
                            <i class="fas fa-piggy-bank me-1"></i>Ahorras: $${(producto.precioSinIvaOriginal - precioConDescuentoSinIva).toFixed(2)}
                        </small>
                    ` : ''}
                    ${tieneDescuento && !producto.sinGanancia ? `
                        <small class="text-danger text-decoration-line-through">$${precioOriginalSinIva.toFixed(2)}</small>
                        <small class="text-primary">
                            <i class="fas fa-piggy-bank me-1"></i>Ahorras: $${ahorroPorUnidadSinIva.toFixed(2)}
                        </small>
                    ` : ''}
                </div>
            </td>
            
            <!-- COLUMNA 8: PRECIO FINAL -->
            <td class="align-middle fw-bold text-success">
                <div class="d-flex flex-column">
                    <span>$${precioConDescuentoFinal.toFixed(2)}</span>
                    ${producto.sinGanancia && producto.descuentoAplicado ? `
                        <small class="text-danger text-decoration-line-through">$${precioOriginalFinal.toFixed(2)}</small>
                        <small class="text-primary">
                            <i class="fas fa-piggy-bank me-1"></i>Ahorras: $${producto.descuentoMonto.toFixed(2)}
                        </small>
                    ` : ''}
                </div>
            </td>
            
            <!-- COLUMNA 9: SUBTOTAL -->
            <td class="align-middle fw-bold">
                <div class="d-flex flex-column">
                    <span>$${(producto.precioFinal * producto.cantidad).toFixed(2)}</span>
                    ${producto.sinGanancia && producto.descuentoAplicado ? `
                        <small class="text-danger text-decoration-line-through">$${(producto.precioFinalSinDescuento * producto.cantidad).toFixed(2)}</small>
                        <small class="text-primary">
                            <i class="fas fa-money-bill-wave me-1"></i>Total ahorrado: $${producto.descuentoMontoTotal.toFixed(2)}
                        </small>
                    ` : ''}
                </div>
            </td>
            
            <!-- COLUMNA 10: MOVER -->
            <td class="align-middle">
                <div class="btn-group-vertical btn-group-sm">
                    <button class="btn btn-outline-primary btn-mover" type="button" 
                            onclick="moverProductoArriba(${producto.id})" 
                            ${index === 0 ? 'disabled' : ''}>
                        <i class="fas fa-arrow-up"></i>
                    </button>
                    <button class="btn btn-outline-primary btn-mover" type="button" 
                            onclick="moverProductoAbajo(${producto.id})" 
                            ${index === productos.length - 1 ? 'disabled' : ''}>
                        <i class="fas fa-arrow-down"></i>
                    </button>
                </div>
            </td>
            
            <!-- COLUMNA 11: ELIMINAR -->
            <td class="align-middle">
                <button class="btn btn-outline-danger btn-sm" type="button" 
                        onclick="eliminarProducto(${producto.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;

        tbody.appendChild(tr);
    });
}
    // Función para resaltar cambios de descuento
    function resaltarCambiosDescuento() {
        const filasConDescuento = document.querySelectorAll('.descuento-visual');
        filasConDescuento.forEach(fila => {
            fila.classList.add('highlight-discount');
            setTimeout(() => fila.classList.remove('highlight-discount'), 2000);
        });
    }

    // Funciones de drag & drop (sin cambios, ya optimizadas)
    function manejarDragStart(e) {
        productoArrastrado = this;
        this.style.opacity = '0.4';
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/html', this.innerHTML);
    }

    function manejarDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    }

    function manejarDragEnter(e) {
        this.classList.add('sobre-arrastrable');
    }

    function manejarDragLeave(e) {
        this.classList.remove('sobre-arrastrable');
    }

    function manejarDrop(e) {
        e.stopPropagation();
        e.preventDefault();
        if (productoArrastrado !== this) {
            const idArrastrado = parseInt(productoArrastrado.getAttribute('data-producto-id'));
            const idObjetivo = parseInt(this.getAttribute('data-producto-id'));
            const indiceArrastrado = productos.findIndex(p => p.id === idArrastrado);
            const indiceObjetivo = productos.findIndex(p => p.id === idObjetivo);
            if (indiceArrastrado !== -1 && indiceObjetivo !== -1) {
                const [productoMovido] = productos.splice(indiceArrastrado, 1);
                productos.splice(indiceObjetivo, 0, productoMovido);
                actualizarTabla();
                calcularTotales();
            }
        }
        this.classList.remove('sobre-arrastrable');
    }

    function manejarDragEnd() {
        document.querySelectorAll('.fila-arrastrable').forEach(fila => {
            fila.classList.remove('sobre-arrastrable');
            fila.style.opacity = '1';
        });
        productoArrastrado = null;
    }

    // Funciones para mover productos
    window.moverProductoArriba = id => {
        const indice = productos.findIndex(p => p.id === id);
        if (indice > 0) {
            [productos[indice - 1], productos[indice]] = [productos[indice], productos[indice - 1]];
            actualizarTabla();
            calcularTotales();
        }
    };

    window.moverProductoAbajo = id => {
        const indice = productos.findIndex(p => p.id === id);
        if (indice < productos.length - 1) {
            [productos[indice], productos[indice + 1]] = [productos[indice + 1], productos[indice]];
            actualizarTabla();
            calcularTotales();
        }
    };

    // Funciones para editar nombre
    window.habilitarEdicionNombre = id => {
        const row = document.querySelector(`tr[data-producto-id="${id}"]`);
        row.querySelector('.product-name-display').style.display = 'none';
        row.querySelector('.nombre-edit-container').style.display = 'block';

        const input = document.getElementById(`edit-input-${id}`);
        const inputClave = document.getElementById(`edit-input-clave-${id}`);
        const inputNombre = document.getElementById(`edit-input-nombre-${id}`);

        input.focus();
        input.select();
    };

    window.guardarNombre = id => {
         const inputClave = document.getElementById(`edit-input-clave-${id}`);
         const inputNombre = document.getElementById(`edit-input-nombre-${id}`);
        const input = document.getElementById(`edit-input-${id}`);

        const nuevaClave = inputClave.value.trim();
        const nuevoNombre = inputNombre.value.trim();

        if (!nuevoNombre) return alert('El nombre del producto no puede estar vacío');
        const producto = productos.find(p => p.id === id);
        if (producto) {
            producto.claveProducto = nuevaClave === '' ? null : nuevaClave;
        producto.nombre = nuevoNombre;
        actualizarTabla();
        }
    };

    window.cancelarEdicion = id => {
        const row = document.querySelector(`tr[data-producto-id="${id}"]`);
        row.querySelector('.product-name-display').style.display = 'inline';
        row.querySelector('.nombre-edit-container').style.display = 'none';
    };

    // Función para actualizar cantidad (optimizada)
    window.actualizarCantidad = (id, cantidad) => {
        const producto = productos.find(p => p.id === id);
        if (producto) {
            const nuevaCantidad = parseInt(cantidad) || 0;
            if (nuevaCantidad <= 0) return alert('La cantidad debe ser mayor a 0');
            producto.cantidad = nuevaCantidad;
            producto.subtotal = producto.precioFinal * nuevaCantidad;
            producto.ivaFinalTotal = producto.ivaFinal * nuevaCantidad;
            producto.descuentoMontoTotal = producto.descuentoMonto * nuevaCantidad;
            producto.subtotalSinDescuento = producto.precioFinalSinDescuento * nuevaCantidad;
            actualizarTabla();
            calcularTotales();
        }
    };

    // Función para eliminar producto
    window.eliminarProducto = id => {
        if (confirm('¿Está seguro de eliminar este producto?')) {
            productos = productos.filter(p => p.id !== id);
            actualizarTabla();
            calcularTotales();
        }
    };

    // Función unificada para recalcular productos al cambiar descuento
// Función unificada para recalcular productos al cambiar descuento (ACTUALIZADA)
// Función unificada para recalcular productos al cambiar descuento (CORREGIDA)
function recalcularProductos(nuevoDescuento) {
    const descuentoAnterior = descuentoPorcentaje;
    descuentoPorcentaje = nuevoDescuento;
    
    productos.forEach(producto => {
        // ✅ CALCULAR CON EL PRECIO COSTO ORIGINAL, NO CON EL PRECIO ACTUAL
        const nuevoCalculo = calcularPrecioFinal(
            producto.precioCosto, 
            producto.sinGanancia, 
            nuevoDescuento,
            false, // ⚠️ NO es nuevo producto
            producto.aplicaTransporte, // ✅ Pasar valores existentes
            producto.transporte
        );
        
        // ✅ ACTUALIZAR TODAS LAS PROPIEDADES CONSISTENTEMENTE
        producto.precioSinIvaFinal = nuevoCalculo.precioSinIvaFinal;
        producto.precioSinIvaFinalConDescuento = nuevoCalculo.precioSinIvaFinalConDescuento;
        producto.precioFinal = nuevoCalculo.precioFinal;
        producto.precioFinalSinDescuento = nuevoCalculo.precioFinalSinDescuento;
        producto.subtotal = nuevoCalculo.precioFinal * producto.cantidad;
        producto.ivaFinal = nuevoCalculo.ivaFinal;
        producto.ivaFinalTotal = nuevoCalculo.ivaFinal * producto.cantidad;
        producto.descuentoAplicado = nuevoCalculo.descuentoAplicado;
        producto.descuentoMonto = nuevoCalculo.descuentoMonto;
        producto.descuentoMontoTotal = nuevoCalculo.descuentoMonto * producto.cantidad;
        producto.subtotalSinDescuento = nuevoCalculo.precioFinalSinDescuento * producto.cantidad;
        producto.porcentajeDescuentoAplicado = nuevoDescuento;
    });
    
    actualizarTabla();
    calcularTotales();
    calcularPreciosEnTiempoReal();
}
    // Funciones para descuento
    window.aplicarDescuento = () => {
        const porcentaje = parseFloat(elementos.descuentoInput.value) || 0;
        if (porcentaje < 0 || porcentaje > 100) return alert('El descuento debe ser un porcentaje entre 0 y 100');
        recalcularProductos(porcentaje);
        elementos.btnAplicarDescuento.style.display = 'none';
        elementos.btnQuitarDescuento.style.display = 'inline-block';
        elementos.descuentoInput.disabled = true;
        setTimeout(resaltarCambiosDescuento, 100);
    };

    window.quitarDescuento = () => {
        recalcularProductos(0);
        elementos.descuentoInput.value = '';
        elementos.descuentoInput.disabled = false;
        elementos.btnAplicarDescuento.style.display = 'inline-block';
        elementos.btnQuitarDescuento.style.display = 'none';
    };

    // Función optimizada para calcular totales (usa reduce para eficiencia)
// ✅ FUNCIÓN CORREGIDA: Calcular totales con precisión
function calcularTotales() {
    // ✅ 1. CALCULAR CON PRECISIÓN EXTENDIDA
    let subtotalSinIva = 0;
    let subtotalProductos = 0;
    let totalTransporte = 0;
    let totalDescuentoAcumulado = 0;
    let subtotalDescuento = 0;

    productos.forEach(producto => {
        const precioSinIvaExacto = producto.precioSinIvaFinalConDescuento;
        
        subtotalSinIva += precioSinIvaExacto * producto.cantidad;
        subtotalProductos += producto.precioCosto * producto.cantidad;
        
        // ✅ SOLO SUMAR TRANSPORTE DE PRODUCTOS CON GANANCIA
        if (!producto.sinGanancia) {
            totalTransporte += producto.transporte * producto.cantidad;
        }
        
        totalDescuentoAcumulado += producto.descuentoMontoTotal || 0;
        subtotalDescuento += producto.precioFinal * producto.cantidad;
    });

    // ✅ 2. REDONDEO CONSISTENTE
    const subtotalSinIvaRedondeado = Math.round(subtotalSinIva * 100) / 100;
    const totalTransporteRedondeado = Math.round(totalTransporte * 100) / 100;
    
    // ✅ 3. CALCULAR IMPUESTOS
    let ivaFinalTotal = 0;
    let isrTotal = 0;
    let total = 0;

    if (elementos.tipoClienteSelect.value === 'persona_moral') {
        ivaFinalTotal = Math.round(subtotalSinIvaRedondeado * IVA * 100) / 100;
        isrTotal = Math.round(subtotalSinIvaRedondeado * ISR * 100) / 100;
        total = Math.round((subtotalSinIvaRedondeado + ivaFinalTotal - isrTotal) * 100) / 100;
    } else {
        ivaFinalTotal = Math.round(subtotalSinIvaRedondeado * IVA * 100) / 100;
        total = Math.round((subtotalSinIvaRedondeado + ivaFinalTotal) * 100) / 100;
    }

    // ✅ 4. ACTUALIZAR DOM
    actualizarElementoDOM('subtotal', `$${subtotalSinIvaRedondeado.toFixed(2)}`);
    actualizarElementoDOM('ivaFinalTotal', `$${ivaFinalTotal.toFixed(2)}`);
    actualizarElementoDOM('totalTransporte', `$${totalTransporteRedondeado.toFixed(2)}`);
    actualizarElementoDOM('total', `$${total.toFixed(2)}`);

    // Mostrar/ocultar descuento
    if (totalDescuentoAcumulado > 0) {
        elementos.totalDescuentoRow.style.display = 'flex';
        elementos.totalDescuento.textContent = `-$${totalDescuentoAcumulado.toFixed(2)}`;
        elementos.porcentajeDescuentoTotal.textContent = `${descuentoPorcentaje}%`;
    } else {
        elementos.totalDescuentoRow.style.display = 'none';
    }

    // Mostrar/ocultar ISR
    const isrRow = document.getElementById('isrRow');
    if (isrRow) {
        isrRow.style.display = elementos.tipoClienteSelect.value === 'persona_moral' ? 'flex' : 'none';
        document.getElementById('isrTotal').textContent = `$${isrTotal.toFixed(2)}`;
    }

    return {
        subtotalSinIva: subtotalSinIvaRedondeado,
        subtotalProductos: Math.round(subtotalProductos * 100) / 100,
        ivaFinalTotal: ivaFinalTotal,
        totalTransporte: totalTransporteRedondeado,
        totalDescuentoAcumulado: Math.round(totalDescuentoAcumulado * 100) / 100,
        isrTotal: isrTotal,
        total: total
    };
}
    // Función auxiliar para actualizar DOM
    function actualizarElementoDOM(id, contenido) {
        const elemento = document.getElementById(id);
        if (elemento) elemento.textContent = contenido;
    }

    // Listener para tipo de cliente
    // Listener para tipo de cliente - ACTUALIZADO
elementos.tipoClienteSelect.addEventListener('change', function() {
    // ✅ RECALCULAR TODOS LOS PRODUCTOS cuando cambie el tipo de cliente
    recalcularTodosLosProductos();
    calcularPreciosEnTiempoReal(); // También actualizar el cálculo en tiempo real
});

    // Agregar con Enter
    elementos.nombreInput.addEventListener('keypress', e => {
        if (e.key === 'Enter') {
            e.preventDefault();
            elementos.btnAgregarProducto.click();
        }
    });

    elementos.descuentoInput.addEventListener('keypress', e => {
        if (e.key === 'Enter') {
            e.preventDefault();
            aplicarDescuento();
        }
    });

    // Función para generar PDF (optimizada con mejor manejo de errores)
  // ✅ MODIFICAR LA FUNCIÓN generarYGuardarPDF
window.generarYGuardarPDF = async () => {
    if (productos.length === 0) return mostrarError('Debe agregar al menos un producto a la cotización');
    
    const cliente = elementos.clienteInput.value.trim();
    const tipoCliente = elementos.tipoClienteSelect.value;
    const validoHasta = elementos.validoHastaInput.value;
    
    if (!cliente) return mostrarError('Complete el campo: Cliente');
    if (!tipoCliente) return mostrarError('Seleccione el Tipo de Cliente');
    if (!validoHasta) return mostrarError('Seleccione la fecha de validez');
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) return mostrarError('Token de seguridad no encontrado. Recarga la página.');
    
    const loadingSwal = Swal.fire({
        title: cotizacionId ? 'Actualizando PDF...' : 'Generando PDF...',
        text: 'Por favor espere...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => Swal.showLoading()
    });
    
    const totales = calcularTotales();
    const datos = {
        cliente,
        tipo_cliente: tipoCliente,
        valido_hasta: validoHasta,
        direccion: elementos.direccionCliente.value.trim() || '',
        telefono: elementos.telefonoCliente.value.trim() || '',
        productos: productos,
        ...totales,
        subtotalDescuento: totales.subtotalDescuento,
        descuentoPorcentaje: descuentoPorcentaje
    };
    
    // ✅ DETERMINAR SI ES CREACIÓN O ACTUALIZACIÓN
    const url = cotizacionId 
    ? `/cotizaciones/${cotizacionId}/actualizar`  // Debe coincidir con tu ruta
    : '/cotizacion/generar-y-guardar-pdf';
    
    const method = cotizacionId ? 'POST' : 'POST';
    // En la función generarYGuardarPDF, justo antes de enviar datos:

productos.forEach((producto, index) => {
   
});
    
    try {
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(datos)
        });
        
        if (response.ok) {
            const pdfBlob = await response.blob();
            const pdfUrl = URL.createObjectURL(pdfBlob);
            window.open(pdfUrl, '_blank');
            
            Swal.fire({
                title: cotizacionId ? '¡Actualizado!' : '¡Éxito!',
                text: cotizacionId ? 'PDF actualizado correctamente' : 'PDF generado correctamente',
                icon: 'success',
                timer: 3000,
                showConfirmButton: false
            });
            
            // Si es nueva cotización, recargar para limpiar
            if (!cotizacionId) {
                setTimeout(() => location.reload(), 2000);
            }
        } else {
            const errorText = await response.text();
            let errorMessage = `Error del servidor: ${response.status}`;
            try {
                const errorData = JSON.parse(errorText);
                errorMessage = errorData.error || errorMessage;
            } catch {}
            throw new Error(errorMessage);
        }
    } catch (error) {
        mostrarError('Error: ' + error.message);
    } finally {
        loadingSwal.close();
    }
};

    async function mostrarError(mensaje) {
        await Swal.fire({ title: 'Error', text: mensaje, icon: 'error', confirmButtonText: 'Aceptar' });
    }

    // Inicialización
    actualizarTabla();
    calcularTotales();
    calcularPreciosEnTiempoReal();
});