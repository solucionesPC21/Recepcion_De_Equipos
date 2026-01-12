document.addEventListener("DOMContentLoaded", function () {
    // Elementos del DOM
    const searchInput = document.getElementById('search');
    const searchResults = document.getElementById('searchResults');
    const clientInfo = document.getElementById('clientInfo');
    const clientName = document.getElementById('clientName');
    const clientPhone = document.getElementById('clientPhone');
    const clearClientButton = document.getElementById('clearClient');
    const productList = document.getElementById("productList");
    const saveClientBtn = document.getElementById('saveClient');
    const cartTableBody = document.querySelector("#cartTable tbody");
    const totalElement = document.getElementById("total");
    const checkoutModal = new bootstrap.Modal(document.getElementById("checkoutModal"));
    const checkoutForm = document.getElementById("checkoutForm");
    const clearCartButton = document.getElementById("clearCart");
    const checkoutButton = document.getElementById("checkout");
    const confirmCheckoutButton = document.getElementById("confirmCheckout");
    const paymentMethodSelect = document.getElementById('paymentMethodSelect');
    
    // Variables de estado
    let cart = [];
    let debounceTimer;

    //bloquear enter en buscar producto
      const searchProductInput = document.getElementById("searchProduct");

    searchProductInput.addEventListener("keydown", function (e) {
        if (e.key === "Enter") {
            e.preventDefault(); // Bloquear Enter
        }
    });

    // Función para mostrar errores
    function mostrarError(mensaje) {
        searchResults.innerHTML = `<li>${mensaje}</li>`;
        document.querySelector('.dropdown1').classList.remove('hidden');
    }

//habilitar boton de cobro
    
    // Escuchar cambios en el select
    paymentMethodSelect.addEventListener('change', function() {
        // Habilitar el botón solo si se ha seleccionado un método de pago válido
        confirmCheckoutButton.disabled = this.value === '';
    });

    // También debemos manejar cuando se abre el modal
    document.getElementById('checkoutModal').addEventListener('show.bs.modal', function() {
        // Resetear el select y deshabilitar el botón cada vez que se abre el modal
        paymentMethodSelect.value = '';
        confirmCheckoutButton.disabled = true;
    });
    // Actualizar la UI del carrito
    function updateCartUI() {
        cartTableBody.innerHTML = "";
        let subtotal = 0;

        cart.forEach((product, index) => {
            const total = product.price * product.quantity;
            subtotal += total;

            const row = document.createElement("tr");
            row.innerHTML = `
                <td>${product.name}</td>
                <td>${product.model || '-'}</td>
                <td>
                    <input type="number" class="form-control text-center update-quantity" 
                           data-index="${index}" value="${product.quantity}" 
                           min="1" max="${product.maxQuantity}">
                </td>
                <td>$${product.price.toFixed(2)}</td>
                <td>$${total.toFixed(2)}</td>
                <td>
                    <button class="btn btn-danger btn-sm remove-from-cart" data-index="${index}">
                        <i class="bi bi-trash-fill"></i>
                    </button>
                </td>
            `;
            cartTableBody.appendChild(row);
        });

        totalElement.textContent = `$${subtotal.toFixed(2)}`;
    }

    // Buscar clientes
    searchInput.addEventListener('input', function () {
        const inputValue = this.value.trim();
        clearTimeout(debounceTimer);

        debounceTimer = setTimeout(() => {
            if (inputValue) {
                fetch(`/buscarClienteVentas?term=${inputValue}`)
                    .then(response => response.json())
                    .then(data => {
                        searchResults.innerHTML = '';
                        if (data.length > 0) {
                            data.forEach(cliente => {
                                const li = document.createElement('li');
                                li.textContent = `${cliente.nombre} - ${cliente.telefono}`;
                                li.addEventListener('click', () => seleccionarCliente(cliente.id));
                                searchResults.appendChild(li);
                            });
                            document.querySelector('.dropdown1').classList.remove('hidden');
                        } else {
                            mostrarError('No se encontraron clientes');
                        }
                    })
                    .catch(() => mostrarError('Error al buscar clientes'));
            } else {
                searchResults.innerHTML = '';
                document.querySelector('.dropdown1').classList.add('hidden');
            }
        }, 300);
    });

    // Seleccionar cliente
    function seleccionarCliente(clienteId) {
        fetch(`/seleccionarClienteVenta/${clienteId}`)
            .then(response => response.json())
            .then(data => {
                clientInfo.classList.remove('d-none');
                clientName.textContent = data.nombre;
                clientPhone.textContent = data.telefono || 'No proporcionado';
                document.querySelector('.dropdown1').classList.add('hidden');
                searchInput.value = '';
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarError('Error al cargar cliente');
            });
    }

    // Limpiar selección de cliente
    clearClientButton.addEventListener('click', function () {
        clientInfo.classList.add('d-none');
        clientName.textContent = '';
        clientPhone.textContent = '';
    });

    //
     // Registro de clientes
     saveClientBtn.addEventListener('click', async function () {
        const nombre = clientNameInput.value.trim();
        const telefono = clientPhoneInput.value.trim();
    
        // Validación básica
        if (!nombre) {
            Swal.fire({
                title: 'Campo requerido',
                text: 'El nombre del cliente es obligatorio',
                icon: 'warning',
                confirmButtonText: 'Entendido'
            });
            return;
        }
    
        try {
            // Mostrar loader
            Swal.fire({
                title: 'Registrando cliente',
                html: 'Por favor espere...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
    
            const response = await fetch('/ventas/registrar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ nombre, telefono }),
            });
    
            const data = await response.json();
    
            if (!response.ok) {
                // Verificar si es error de nombre duplicado
                if (data.errors?.nombre) {
                    throw new Error('Ya existe un cliente registrado con este nombre');
                }
                throw new Error(data.message || 'Error al registrar cliente');
            }
    
            // Éxito - Mostrar confirmación
            Swal.fire({
                title: '¡Registro exitoso!',
                text: 'El cliente ha sido registrado correctamente',
                icon: 'success',
                confirmButtonText: 'Aceptar',
                willClose: () => {
                    // Actualizar UI
                    clientInfo.classList.remove('d-none');
                    clientName.textContent = data.cliente.nombre;
                    clientPhone.textContent = data.cliente.telefono || 'No proporcionado';
                    
                    // Cerrar modal y limpiar formulario
                    bootstrap.Modal.getInstance(document.getElementById('registerClientModal')).hide();
                    clientForm.reset();
                }
            });
    
        } catch (error) {
            console.error('Error:', error);
            
            // Mensaje específico para nombre duplicado
            if (error.message.includes('Ya existe')) {
                Swal.fire({
                    title: 'Cliente existente',
                    html: `
                        <div class="text-left">
                            <p>${error.message}</p>
                            <p class="text-muted small mt-2">Por favor, verifique el nombre e intente con uno diferente.</p>
                        </div>
                    `,
                    icon: 'warning',
                    confirmButtonText: 'Entendido'
                });
            } else {
                // Otros errores
                Swal.fire({
                    title: 'Error',
                    text: error.message || 'Ocurrió un error al registrar el cliente',
                    icon: 'error',
                    confirmButtonText: 'Entendido'
                });
            }
        }
    });

    // Buscar productos
    document.getElementById("searchProduct").addEventListener("input", function () {
        clearTimeout(debounceTimer);
        const query = this.value.trim();
        if (query.length < 2) {
            productList.innerHTML = "";
            return;
        }
    
        // Mostrar indicador de carga
        productList.innerHTML = '<div class="col-12 text-center"><div class="spinner-border text-primary" role="status"></div></div>';
    
        debounceTimer = setTimeout(() => {
           Debug
            
            fetch(`/buscar-productos?query=${encodeURIComponent(query)}`)
                .then(response => {
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(productos => {
                 
                    console.assert(Array.isArray(productos), 'Response should be an array');
                    productList.innerHTML = "";
                    
                    if (!productos || productos.length === 0) {
                     
                        productList.innerHTML = "<p class='text-center text-muted'>No se encontraron productos</p>";
                        return;
                    }
        
                    productos.forEach(producto => {
                        // Debug específico para cantidad
                      
                        
                        const cantidad = parseInt(producto.cantidad) || 0;
                        const disponible = cantidad > 0;
                        
                        const productCard = document.createElement("div");
                        productCard.className = "col-md-4 mb-3";
                        productCard.innerHTML = `
                            <div class="card product-card h-100">
                                <div class="card-body">
                                    <h6 class="card-title">${producto.nombre}</h6>
                                    ${producto.descripcion ? `<p class="card-text text-muted small">${producto.descripcion}</p>` : ''}
                                    ${producto.modelo ? `<p class="card-text text-muted small">${producto.modelo}</p>` : ''}
                                    ${producto.marca ? `<p class="card-text text-muted small">${producto.marca}</p>` : ''}
                                    <p class="card-text">$${parseFloat(producto.precio || 0).toFixed(2)}</p>
                                    <p class="card-text ${disponible ? 'text-success' : 'text-danger'}">
                                        ${disponible ? `Disponible: ${cantidad}` : 'AGOTADO'}
                                    </p>
                                    <button type="button" class="btn btn-${disponible ? 'primary' : 'secondary'} btn-sm w-100 add-to-cart" 
                                            data-id="${producto.id}"
                                            data-name="${producto.nombre}"
                                            data-model="${producto.modelo || ''}"
                                            data-price="${producto.precio || 0}"
                                            data-stock="${cantidad}"
                                            ${!disponible ? 'disabled' : ''}>
                                        <i class="fas fa-cart-plus me-2"></i> ${disponible ? 'Agregar' : 'No Disponible'}
                                    </button>
                                </div>
                            </div>
                        `;
                        productList.appendChild(productCard);
                    });
                })
                .catch(error => {
                    console.error("Error completo:", error); // ← Debug detallado
                    productList.innerHTML = `
                        <div class="col-12">
                            <p class="text-center text-danger">Error al cargar productos</p>
                            <p class="text-center small">${error.message}</p>
                        </div>
                    `;
                });
        }, 300);
    });

   
    // Agregar al carrito
    document.addEventListener("click", function (event) {
        if (event.target.classList.contains("add-to-cart")) {
            const button = event.target;
            const product = {
                id: button.getAttribute("data-id"),
                name: button.getAttribute("data-name"),
                model: button.getAttribute("data-model"),
                price: parseFloat(button.getAttribute("data-price")),
                maxQuantity: parseInt(button.getAttribute("data-stock"))
            };

            addToCart(product);
        }

        if (event.target.classList.contains("remove-from-cart")) {
            const index = event.target.getAttribute("data-index");
            event.preventDefault(); // Esto evita cualquier comportamiento por defecto
            event.stopPropagation(); // Evita la propagación del evento
            cart.splice(index, 1);
            updateCartUI();
        }
    });

    // Función para agregar al carrito
    function addToCart(product) {
        const existingItem = cart.find(item => item.id === product.id);
        const requestedQuantity = (existingItem?.quantity || 0) + 1;

        if (requestedQuantity > product.maxQuantity) {
            alert(`No hay suficiente stock de ${product.name}. Máximo disponible: ${product.maxQuantity}`);
            return;
        }

        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            cart.push({
                ...product,
                quantity: 1
            });
        }

        updateCartUI();
    }

    // Actualizar cantidad en carrito
    document.addEventListener("change", function (event) {
        if (event.target.classList.contains("update-quantity")) {
            const index = event.target.getAttribute("data-index");
            const newQuantity = parseInt(event.target.value);
            const product = cart[index];

            if (newQuantity > product.maxQuantity) {
                alert(`No puedes agregar más de ${product.maxQuantity} unidades de ${product.name}`);
                event.target.value = product.quantity;
                return;
            }

            if (newQuantity < 1) {
                event.target.value = 1;
                cart[index].quantity = 1;
            } else {
                cart[index].quantity = newQuantity;
            }

            updateCartUI();
        }
    });

    // Limpiar carrito
    clearCartButton.addEventListener("click", function () {
        if (cart.length > 0 && confirm("¿Estás seguro de vaciar el carrito?")) {
            cart = [];
            updateCartUI();
        }
    });

    // Procesar cobro
    checkoutButton.addEventListener("click", function () {
        if (cart.length === 0) {
            alert("El carrito está vacío");
            return;
        }

        

        document.getElementById("modalTotal").textContent = totalElement.textContent;
        checkoutModal.show();

        
    });

    // Confirmar cobro
    async function prepareAndSubmitForm() {
        // Validaciones
    
        if (cart.length === 0) {
            Swal.fire({
                title: 'Carrito vacío',
                text: 'Agregue productos antes de cobrar',
                icon: 'warning',
                confirmButtonText: 'Entendido'
            });
            return false;
        }
    
        // Mostrar confirmación antes de proceder
        const confirmResult = await Swal.fire({
            title: '¿Confirmar venta?',
            text: '¿Desea proceder con el cobro de esta venta?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, cobrar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        });
    
        if (!confirmResult.isConfirmed) {
            return false;
        }
    
        // Obtener valores
        const paymentMethod = document.getElementById('paymentMethodSelect').value;
        const totalValue = parseFloat(document.getElementById('total').textContent.replace('$', '').trim());
    
        // Preparar FormData
        const formData = new FormData();

         // Solo agregar datos del cliente si existe
    if (clientName.textContent.trim()) {
        formData.append('client_name', clientName.textContent.trim());
        formData.append('client_phone', clientPhone.textContent.trim());
    } else {
        formData.append('client_name', '');
        formData.append('client_phone', '');
    }
    //
        formData.append('payment_method', paymentMethod);
        formData.append('total', totalValue);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
        
        // Enviar el carrito como JSON string
        formData.append('cart', JSON.stringify(cart));
    
        // También mantener los conceptos individuales si el servidor los necesita
        cart.forEach((item, index) => {
            formData.append(`concepto[${index}]`, item.id);
            formData.append(`precio_unitario[${index}]`, item.price);
            formData.append(`cantidad[${index}]`, item.quantity);
        });
    
        try {
            // Mostrar loader mientras se procesa
            Swal.fire({
                title: 'Procesando venta',
                html: 'Por favor espere mientras se registra la venta...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
    
            const response = await fetch('/ventas/realizar-cobro', {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
    
            const data = await response.json();
        
            if (!response.ok) {
                throw new Error(data.message || 'Error al procesar el pago');
            }
    
            // Cerrar loader
            Swal.close();
    
            // Mostrar SweetAlert de éxito
            Swal.fire({
                title: '¡Venta completada!',
                text: 'La venta se ha registrado e impreso correctamente',
                icon: 'success',
                confirmButtonText: 'Aceptar',
                timer: 3000,
                timerProgressBar: true,
                willClose: () => {
                    // Limpiar el carrito después de cerrar la alerta
                    cart = [];
                    updateCartUI();
                    // Limpiar selección de cliente
                    clientInfo.classList.add('d-none');
                    clientName.textContent = '';
                    clientPhone.textContent = '';
                    // Cerrar modal de checkout si está abierto
                    checkoutModal.hide();
                }
            });
            
        } catch (error) {
            console.error('Error:', error);
            // Mostrar SweetAlert de error
            Swal.fire({
                title: 'Error',
                text: error.message || 'Ocurrió un error al procesar la venta',
                icon: 'error',
                confirmButtonText: 'Entendido'
            });
        }
    }
    
    // Modificar el evento del botón de confirmación
    confirmCheckoutButton.addEventListener("click", prepareAndSubmitForm);
});