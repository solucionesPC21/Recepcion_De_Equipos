@extends('layouts.app-master')

@section('content')
    <h1 class="titulo">Registro De Recepción De Equipos</h1>

    <!-- Cuadro de búsqueda y botón de Registrar Cliente -->
    @if (!session('cliente'))
    <div class="search-header">
        <h2>Buscar Cliente: </h2>
        <div class="search-container">
            <input type="text" name="search" id="search" placeholder="Buscar cliente por nombre">
            <div class="dropdown">
                <ul id="searchResults" class="dropdown-menu hidden">
                    <!-- Aquí se insertarán las coincidencias de la búsqueda en tiempo real -->
                </ul>
            </div>
        </div>
        <a class="fcc-btn" id="registrarClienteBtn">Registrar Cliente</a>
    </div>
    @else
        <div class="search-header">
            <h2>Buscar Cliente: </h2>
            <div class="search-container">
                <input type="text" name="search" id="search" placeholder="Buscar cliente por nombre" disabled>
                <div class="dropdown">
                    <ul id="searchResults" class="dropdown-menu hidden">
                        <!-- Aquí se insertarán las coincidencias de la búsqueda en tiempo real -->
                    </ul>
                </div>
            </div>
            <a class="fcc-btn" id="registrarClienteBtn">Registrar Cliente</a>
        </div>
    @endif

    
    <div id="cliente-info" style="display: block;"></div>
    
    @if (session('success'))
    <div id="success-alert-modal" class="modal-alert">
        <div class="modal-alert-content alert alert-success alert-dismissible fade-out custom-alert" role="alert">
            {{ session('success') }}
            <div class="progress-bar" id="success-progress-bar"></div>
        </div>
    </div>
    @endif

    

    @if (session('error'))
    <div id="error-alert" class="modal-alert alert alert-danger alert-dismissible fade-out custom-alert" role="alert">
        {{ session('error') }}
        <div class="progress-bar" id="error-progress-bar"></div>
    </div>
    @endif
    @include('clientes.busqueda')        
    @include('clientes.form-registrar')
@endsection
