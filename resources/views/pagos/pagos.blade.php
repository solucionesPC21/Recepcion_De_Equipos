@extends('layouts.pagos.app-master')
@section('content')

    <div class="card-header py-3 d-flex flex-wrap justify-content-between align-items-center">
    <h4 class="m-0 font-weight-bold text-primary mb-2 mb-md-0">Listado de Tickets</h4>
    
    <div class="d-flex flex-wrap gap-2">
        <!-- Botón de Gastos -->
        <a href="{{ route('abonos.index') }}" class="btn btn-info">
            <i class="fa-regular fa-money-bill-1"></i> Abonos
        </a>
        <!-- Botón de Gastos -->
        <a href="{{ route('gastos.index') }}" class="btn btn-info">
            <i class="fas fa-money-bill-wave mr-1"></i> Gastos
        </a>
        
        @auth
        @if(auth()->user()->isAdmin())
            <form  id="generarCorteForm" action="{{ route('generar.reporte') }}" method="POST" target="_blank" class="d-inline">
                @csrf
                <input type="hidden" name="fechaInicio" value="{{ now()->startOfDay()->toDateString() }}">
                <input type="hidden" name="fechaFin" value="{{ now()->endOfDay()->toDateString() }}">
                <button type="submit" class="btn btn-success">Generar Corte</button>
            </form>
            
            <button id="btnGenerarCorteDinamico" class="btn btn-primary" data-toggle="modal" data-target="#modalCorte">
                Generar Corte Dinámico
            </button>
        @endif
        @endauth
    </div>
</div>

  <div class="input-group mb-3" style="max-width: 700px;">
        <!-- Ajusta el ancho máximo según tus necesidades -->
        <input type="text" id="searchInput" class="form-control" placeholder="Buscar Cliente..." onkeyup="buscarRecibos()">
        
        <div class="input-group-append">
            <button class="btn btn-outline-secondary" type="button">Buscar</button>
        </div>
    </div>

<!-- Filtro de fechas -->
<div class="card-body border-bottom">
    <form id="filtroForm" method="GET" action="{{ route('pagos.index') }}" class="row g-3">
        <div class="col-md-4">
            <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" 
                   value="{{ $filtroFechas['inicio'] ?? '' }}">
        </div>
        <div class="col-md-4">
            <label for="fecha_fin" class="form-label">Fecha Fin</label>
            <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" 
                   value="{{ $filtroFechas['fin'] ?? '' }}">
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <button type="submit" class="btn btn-primary me-2">
                <i class="fas fa-filter"></i> Filtrar
            </button>
            <a href="{{ route('pagos.index') }}" class="btn btn-secondary">
                <i class="fas fa-times"></i> Limpiar
            </a>
        </div>
    </form>

</div>

 <div class="d-flex justify-content-end mb-3">
        <form method="GET" action="{{ route('pagos.index') }}" class="form-inline">

            <!-- Mantener filtros si existen -->
            <input type="hidden" name="fecha_inicio" value="{{ request('fecha_inicio') }}">
            <input type="hidden" name="fecha_fin" value="{{ request('fecha_fin') }}">

            <label class="mr-2">Resultados por página:</label>

            <select name="perPage" class="form-control" onchange="this.form.submit()">
                @foreach([5,10,20,30,40,50] as $size)
                    <option value="{{ $size }}" {{ request('perPage', 10) == $size ? 'selected' : '' }}>
                        {{ $size }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>

<br>

        <div class="card-body"> <!-- Cuerpo de la tarjeta -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th class="text-center" style="width: 8%">#</th>
                            <th class="text-right" style="width: 25%">Nombre</th>
                            <th class="text-right" style="width: 10%">Total</th>
                            <th class="text-center" style="width: 10%">Fecha Pago</th>
                            <th class="text-center" style="width: 10%">Recibo</th>
                            @if(auth()->user()->isAdmin())
                                <th class="text-center" style="width: 15%">Acciones</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody id="recibosBody">
                        @foreach($tickets as $ticket)
                        <tr>
                            <td class="text-center align-middle">{{ $ticket->id }}</td>
                             <td class="text-left align-middle">
                                {{ $ticket->cliente->nombre ?? $ticket->recibo->tipoEquipo[0]->cliente->nombre ?? 'Venta Al Público En General' }}
                            </td>
                            <td class="text-right align-middle">${{ number_format($ticket->total, 2) }}</td>
                            <td class="text-center align-middle">{{ $ticket->fecha ? date('d-m-Y', strtotime($ticket->fecha)) : 'N/A' }}</td>
                            <td class="text-center align-middle">
                                <form action="{{ route('pagos.pdf', ['id' => $ticket->id]) }}" method="GET" target="_blank">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary" title="Descargar PDF">
                                        <i class="fas fa-file-pdf"></i> PDF
                                    </button>
                                </form>
                            </td>

                            @if(auth()->user()->isAdmin())
                            <td class="text-center align-middle">
                                <div class="d-flex justify-content-center">
                                    <form id="cancelarForm-{{ $ticket->id }}" action="{{ route('pagos.cancelar', $ticket->id) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="button" class="btn btn-sm btn-danger cancelar-btn" 
                                                data-id="{{ $ticket->id }}" 
                                                data-estado="{{ $ticket->estado_id }}"
                                                title="Cancelar Pago">
                                            <i class="fas fa-ban"></i> Cancelar
                                        </button>
                                    </form>
                                </div>
                            </td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                    @if($tickets->isNotEmpty())
                    <tfoot>
                        @if(isset($filtroFechas['inicio']))
                        <tr>
                            <td colspan="{{ auth()->user()->isAdmin() ? 5 : 4 }}" class="text-right small text-muted">
                                Mostrando pagos desde {{ date('d/m/Y', strtotime($filtroFechas['inicio'])) }} 
                                hasta {{ date('d/m/Y', strtotime($filtroFechas['fin'])) }}
                            </td>
                        </tr>
                        @endif
                    </tfoot>
                    @endif
                </table>
            </div>
            
            <div class="card-footer bg-light d-flex justify-content-center" id="paginacion">
                {{ $tickets->appends(request()->query())->onEachSide(1)->links('pagination::bootstrap-4') }}
            </div>

        </div>
    </div>

    <!-- Modal -->
    @auth
    @if(auth()->user()->isAdmin())
    <div class="modal fade" id="modalCorte" tabindex="-1" role="dialog" aria-labelledby="modalCorteLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg">
                <form action="{{ route('generar.reporte') }}" method="POST" target="_blank">
                @csrf
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title font-weight-bold" id="modalCorteLabel">Generar Reporte de Corte</h5>
                        
                    </div>
                    <div class="modal-body py-4">
                        <div class="form-group">
                            <label for="fechaInicio" class="font-weight-bold text-primary">Fecha de Inicio</label>
                            <input type="date" id="fechaInicio" name="fechaInicio" class="form-control border-primary rounded-lg" required>
                        </div>
                        <div class="form-group">
                            <label for="fechaFin" class="font-weight-bold text-primary">Fecha de Fin</label>
                            <input type="date" id="fechaFin" name="fechaFin" class="form-control border-primary rounded-lg" required>
                        </div>
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle mr-2"></i> Seleccione el rango de fechas para generar el reporte.
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">
                            <i class="fas fa-file-pdf mr-2"></i> Generar Reporte
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endauth
</div>

@endsection 