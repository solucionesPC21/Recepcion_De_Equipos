@extends('layouts.clientes.app-master')

@section('content')
<div class="container-fluid py-4">
    <h1 class="titulo fade-in-up">Gestión de Clientes</h1>

   <!-- Búsqueda Elegante -->
    <div class="row justify-content-center mb-4">
        <div class="col-lg-8">
            <div class="input-group input-group-lg shadow-lg">
                <span class="input-group-text">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" id="searchInput" class="form-control" 
                    placeholder="Buscar clientes por nombre, teléfono o RFC...">

            </div>
        </div>
    </div>

    <!-- Tabla Responsive -->
    <div class="card shadow-lg border-0 slide-in">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><i class="fas fa-user me-2"></i>Nombre</th>
                            <th><i class="fas fa-phone me-2"></i>Teléfono</th>
                            <th><i class="fas fa-mobile-alt me-2"></i>Teléfono 2</th>
                            <th><i class="fas fa-id-card me-2"></i>RFC</th>
                            <th><i class="fas fa-map-marker-alt me-2"></i>Colonia</th>
                            <th><i class="fas fa-cogs me-2"></i>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="recibosBody">
                        @foreach($clientes as $cliente)
                        <tr class="fade-in-up">
                            <td><span class="badge bg-primary">{{ $cliente->id }}</span></td>
                            <td><strong>{{ $cliente->nombre }}</strong></td>
                            <td>{{ $cliente->telefono }}</td>
                            <td>{{ $cliente->telefono2 ?: '-' }}</td>
                            <td>{{ $cliente->rfc ?: '-' }}</td>
                            <td>
                                <span class="status-badge {{ $cliente->colonia}}">
                                    {{ $cliente->colonia ? $cliente->colonia->colonia : 'Sin colonia' }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    @if(auth()->user()->isAdmin())
                                    <form action="{{ url('/clientes/'.$cliente->id) }}" method="post" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm me-2" 
                                                title="Eliminar cliente">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                    <button class="btn btn-info btn-sm editarClienteBtn" 
                                            data-cliente-id="{{ $cliente->id }}"
                                            title="Editar cliente">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

 <!-- Paginación Mejorada -->
<nav aria-label="Navegación de clientes" class="mt-4">
    <ul class="pagination justify-content-center">
        {{ $clientes->onEachSide(1)->links('pagination::bootstrap-5') }}
    </ul>
    <div class="text-center text-muted small mt-2">
        Mostrando {{ $clientes->firstItem() }} - {{ $clientes->lastItem() }} de {{ $clientes->total() }} clientes
    </div>
</nav>
</div>
@endsection
