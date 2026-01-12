@extends('layouts.app-master')

@section('content')
<div class="container-recepcion">
    <h1 class="titulo-principal">Registro De Recepción De Equipos</h1>

    <!-- Búsqueda de Cliente -->
    <div class="search-section">
    <div class="search-header">
        <h2>Buscar Cliente</h2>
        <div class="search-container">
            <input type="text" name="search" id="search" placeholder="Buscar cliente por nombre">
            <div class="dropdown">
                <ul id="searchResults" class="dropdown-menu hidden"></ul>
            </div>
        </div>
        <button class="btn btn-primary" id="registrarClienteBtn">Registrar Cliente</button>
    </div>
</div>
    <!-- Información del Cliente Seleccionado -->
    <div id="cliente-info" class="cliente-info-section" style="display: none;">
        <div class="card cliente-card">
            <div class="card-header">
                <h3>Información del Cliente</h3>
            </div>
            <div class="card-body">
                <div class="row cliente-details" id="cliente-details">
                    <!-- Los datos del cliente se cargarán aquí dinámicamente -->
                </div>
            </div>
        </div>
    </div>

    <!-- Formulario de Equipos -->
    <form id="formGenerarRecibo" action="{{ url('/home/registroEquipoCliente') }}" method="post" class="equipos-form" style="display: none;">   
        @csrf

        <input type="hidden" name="nombre_cliente" id="nombre_cliente">
        <!-- Mantener también el cliente_id si lo necesitas para otra cosa -->
        <input type="hidden" name="cliente_id" id="cliente_id">

        <div class="equipos-section">
            <div class="section-header">
                <h3>Información del Equipo</h3>
            </div>

            <!-- Equipo Principal -->
            <div class="equipo-card" id="equipo-principal">
                <div class="equipo-header">
                    <h4>Equipo #1</h4>
                </div>
                <div class="equipo-body">
                    <div class="row">
                        <!-- Columna 1 -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="tipo_equipo" class="form-label">Tipo de Equipo *</label>
                                <select class="form-select" name="tipo_equipo[]" required>
                                    <option value="" disabled selected>Selecciona una opción</option>
                                    @foreach($equipos as $equipo)
                                        <option value="{{ $equipo->id }}">{{ $equipo->equipo }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="marca" class="form-label">Marca *</label>
                                <select class="form-select" name="marca[]" required>
                                    <option value="" disabled selected>Selecciona una opción</option>
                                    @foreach($marcas as $marca)
                                        <option value="{{ $marca->id }}">{{ $marca->marca }}</option>
                                    @endforeach
                                    <option value="nueva_marca">Agregar nueva marca</option>
                                </select>
                            </div>
                            <div class="form-group nueva-marca" style="display:none;">
                                <label for="nueva_marca" class="form-label">Nueva Marca *</label>
                                <input type="text" class="form-control" name="nueva_marca[]" 
                                       id="nueva_marca" placeholder="Ingrese una nueva marca">
                            </div>
                        </div>

                        <!-- Columna 2 -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="modelo" class="form-label">Modelo *</label>
                                <input type="text" class="form-control" name="modelo[]" 
                                       id="modelos" placeholder="Ingrese el modelo" required>
                            </div>
                            <div class="form-group">
                                <label for="ns" class="form-label">Número de Serie</label>
                                <input type="text" class="form-control" name="ns[]" 
                                       placeholder="Ingrese el número de serie">
                            </div>
                        </div>

                        <!-- Columna 3 -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="falla" class="form-label">Falla *</label>
                                <textarea class="form-control" name="falla[]" rows="3" 
                                          placeholder="Describe la falla" required></textarea>
                            </div>
                            <div class="form-group">
                                <label for="accesorios" class="form-label">Accesorios</label>
                                <textarea class="form-control" name="accesorios[]" rows="3" 
                                          placeholder="Lista de accesorios incluidos"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenedor para equipos adicionales -->
            <div id="equipos-adicionales"></div>

            <!-- Botones de acción -->
            <div class="form-actions">
                <button type="button" id="duplicarCampo" class="btn btn-secondary">
                    <i class="fas fa-plus"></i> Agregar Otro Equipo
                </button>
                <button type="submit" class="btn btn-success btn-enviar">
                    <i class="fas fa-receipt"></i> Generar Recibo
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Modal Registrar Cliente -->
<div id="modalRegistrarCliente" class="modal">
    <div class="modal-content">
        <span id="cerrarModal" class="close">&times;</span>
        <h2 class="modal-title">Registrar Cliente</h2>
        
        <form id="formRegistrarCliente">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="nombre" class="form-label">Nombre *</label>
                        <input type="text" name="nombre" id="nombre" class="form-control" required>
                        <div id="error-nombre" class="error-message"></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="telefono" class="form-label">Teléfono *</label>
                        <input type="text" name="telefono" id="telefono" class="form-control" required maxlength="10">
                        <div id="error-telefono" class="error-message"></div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="telefono2" class="form-label">Teléfono 2</label>
                        <input type="text" name="telefono2" id="telefono2" class="form-control" maxlength="10">
                        <div id="error-telefono2" class="error-message"></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="rfc" class="form-label">RFC</label>
                        <input type="text" name="rfc" id="rfc" class="form-control" maxlength="14">
                        <div id="error-rfc" class="error-message"></div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="buscarColonia" class="form-label">Colonia</label>
                        <div class="colonia-search-container">
                            <input type="text" 
                                   name="buscarColonia" 
                                   id="buscarColonia" 
                                   class="form-control" 
                                   placeholder="Escribe para buscar colonia..."
                                   autocomplete="off">
                            <div class="colonia-results hidden" id="coloniaResults"></div>
                        </div>
                        <!-- Campo hidden para almacenar el ID de la colonia seleccionada -->
                        <input type="hidden" name="id_colonia" id="colonia_id">
                        <div id="error-colonia" class="error-message"></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Colonia Seleccionada</label>
                        <div id="colonia-seleccionada" class="colonia-seleccionada">
                            <span class="no-colonia">Ninguna colonia seleccionada</span>
                        </div>
                    </div>
                </div>
            </div>
            
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="cancelarRegistro">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Cliente</button>
            </div>
        </form>
    </div>
</div>

@endsection
