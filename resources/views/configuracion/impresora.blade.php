@extends('layouts.app-master')

@section('content')
<div class="container">
    <h4 class="mb-4">Configuración de Impresoras</h4>

    {{-- Mensajes --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- FORMULARIO --}}
    <div class="card mb-4">
        <div class="card-header">Agregar impresora</div>
        <div class="card-body">
            <form method="POST" action="{{ route('impresoras.store') }}">
                @csrf

                <div class="row">

                    <div class="col-md-4">
                        <label>Nombre de la impresora (Windows)</label>
                        <input type="text" name="nombre_sistema" class="form-control" required>
                        <small class="text-muted">
                            Debe coincidir exactamente con el nombre de la impresora en Windows
                        </small>
                    </div>

                    <div class="col-md-3">
                        <label>Categoría</label>
                        <select name="tipo" id="tipo_impresora" class="form-control" required>
                            <option value="">Seleccione</option>
                            <option value="termica">Impresora térmica</option>
                            <option value="hojas">Impresora de hojas</option>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="activa" checked>
                            <label class="form-check-label">Activa</label>
                        </div>
                    </div>

                </div>

                {{-- Ruta Sumatra (solo hojas) --}}
                <div class="row mt-3 d-none" id="sumatra_container">
                    <div class="col-md-6">
                        <label>Ruta SumatraPDF</label>
                        <input
                            type="text"
                            name="sumatra_path"
                            class="form-control"
                            placeholder="C:\Users\...\SumatraPDF.exe"
                        >
                        <small class="text-muted">
                            Requerido solo para impresoras de hojas
                        </small>
                    </div>
                </div>

                <button class="btn btn-primary mt-3">
                    Guardar impresora
                </button>
            </form>
        </div>
    </div>

    {{-- LISTADO --}}
    <div class="card">
        <div class="card-header">Impresoras registradas</div>
        <div class="card-body p-0">
            <table class="table table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Nombre sistema</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($impresoras as $impresora)
                        <tr>
                            <td>{{ $impresora->id }}</td>
                            <td>{{ $impresora->nombre_sistema }}</td>
                            <td>
                                @if($impresora->tipo === 'termica')
                                    <span class="badge bg-warning text-dark">Térmica</span>
                                @else
                                    <span class="badge bg-primary">Hojas</span>
                                @endif
                            </td>
                            <td>
                                @if($impresora->activa)
                                    <span class="badge bg-success">Activa</span>
                                @else
                                    <span class="badge bg-secondary">Inactiva</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <form action="{{ route('impresoras.destroy', $impresora->id) }}"
                                      method="POST"
                                      onsubmit="return confirm('¿Seguro que deseas eliminar esta impresora?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger">
                                        Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">
                                No hay impresoras registradas
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- JS --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tipoSelect = document.getElementById('tipo_impresora');
    const sumatraContainer = document.getElementById('sumatra_container');
    const sumatraInput = sumatraContainer.querySelector('input');

    tipoSelect.addEventListener('change', function () {
        if (this.value === 'hojas') {
            sumatraContainer.classList.remove('d-none');
        } else {
            sumatraContainer.classList.add('d-none');
            sumatraInput.value = '';
        }
    });
});
</script>
@endsection
