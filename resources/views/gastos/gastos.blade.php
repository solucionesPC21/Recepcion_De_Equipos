@extends('layouts.gastos.app-master')

@section('content')


<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h4 class="m-0 font-weight-bold text-primary">Registro de Gastos</h4>
            
            <!-- Cambiamos el enlace por un botón que abre la modal -->
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#nuevoGastoModal">
                Agregar Gasto
            </button>
        </div>

<div class="card-body border-bottom">
    <form id="filtroForm" method="GET" action="{{ route('gastos.index') }}" class="row g-3">
        <div class="col-md-4">
            <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" 
                   value="{{ $filtroFechas['inicio'] }}">
        </div>
        <div class="col-md-4">
            <label for="fecha_fin" class="form-label">Fecha Fin</label>
            <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" 
                   value="{{ $filtroFechas['fin'] }}">
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <button type="submit" class="btn btn-primary me-2">
                <i class="fas fa-filter"></i> Filtrar
            </button>
            <a href="{{ route('gastos.index') }}" class="btn btn-secondary">
                <i class="fas fa-times"></i> Limpiar
            </a>
        </div>
    </form>
</div>
        
    <div class="card-body">
        <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th class="text-center" style="width: 10%">#</th>
                            <th class="text-left" style="width: 50%">Descripción</th>
                            <th class="text-center" style="width: 15%">Fecha</th>
                            <th class="text-right" style="width: 15%">Monto</th>  
                            @if(auth()->user()->isAdmin())     
                            <th class="text-center" style="width: 10%">Acciones</th>
                              @endif
                        </tr>
                    </thead>
                    <tbody>
                            @forelse($gastos as $gasto)
                            <tr>
                                <td class="text-center align-middle">{{ $gasto->id }}</td>
                                <td class="text-left align-middle">{{ $gasto->descripcion }}</td>
                                <td class="text-center align-middle">{{ date('d-m-Y', strtotime($gasto->fecha)) }}</td>
                                <td class="text-right align-middle">${{ number_format($gasto->monto, 2) }}</td>
                               
                                 @if(auth()->user()->isAdmin())    
                                <td class="text-center align-middle">
                                    <div class="d-flex justify-content-center">
                                        <!-- Botón Editar -->
                                        <button class="btn btn-sm btn-warning mx-1 edit-gasto" 
                                                data-id="{{ $gasto->id }}"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editarGastoModal">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                        
                                        <!-- Botón Eliminar -->
                                        <form action="{{ route('gastos.destroy', $gasto->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-sm btn-danger delete-gasto">
                                                <i class="fas fa-trash"></i> Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                                 @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center">No se encontraron gastos registrados</td>
                            </tr>
                            @endforelse
                        </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-center mt-4">
                {{ $gastos->appends(request()->query())->onEachSide(1)->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
</div>

<!-- Modal para Nuevo Gasto -->
<div class="modal fade" id="nuevoGastoModal" tabindex="-1" aria-labelledby="nuevoGastoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="nuevoGastoModalLabel">Registrar Nuevo Gasto</h5>
                <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formNuevoGasto" action="{{ route('gastos.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="2" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="monto">Monto</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">$</span>
                            </div>
                            <input type="number" class="form-control" id="monto" name="monto" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="fecha">Fecha</label>
                        <input type="date" class="form-control" id="fecha" name="fecha" value="<?php echo date('Y-m-d'); ?>" required></div>
                    </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Guardar Gasto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Editar Gasto -->
<div class="modal fade" id="editarGastoModal" tabindex="-1" aria-labelledby="editarGastoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="editarGastoModalLabel">Editar Gasto</h5>
                <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditarGasto" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_descripcion">Descripción</label>
                        <textarea class="form-control" id="edit_descripcion" name="descripcion" rows="2" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="edit_monto">Monto</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">$</span>
                            </div>
                            <input type="number" class="form-control" id="edit_monto" name="monto" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit_fecha">Fecha</label>
                        <input type="date" class="form-control" id="edit_fecha" name="fecha" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning">Actualizar Gasto</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection