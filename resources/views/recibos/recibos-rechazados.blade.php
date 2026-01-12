@extends('layouts.recibos.app-master')

@section('content')

    <h1 class="titulo">Recibos Cancelados</h1>

    <!-- Filtro y búsqueda en tiempo real con estilos de Bootstrap -->
    <div class="d-flex justify-content-between mb-3" style="max-width: 900px;">
        <!-- Ajusta el ancho máximo según tus necesidades -->
        <div class="input-group" style="max-width: 700px;">
            <input type="text" id="searchInput" class="form-control" placeholder="Buscar recibo..." onkeyup="buscarRechazado()">
        </div>

    </div>

    <strong><h2>Total De Ordenes De Reparación Recibidos: {{ $totalRecibos }}</h2></strong><br>
    <table class="tabla">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Fecha de Recibido</th>
                <th>Nota De Recepción</th>
                <th>Comentarios</th>
                @auth
                    @if(auth()->user()->isAdmin())
                        <th>Estado</th>
                    @endif
                @endauth
                <th>Archivos</th> 
            </tr>
        </thead>
        <tbody id="recibosBody">
            @foreach($recibos as $recibo)
            <tr class="recibo-row" data-estado="{{ $recibo->estado }}">
                <td>
                @if(isset($recibo->tipoEquipo[0]->cliente))
                    {{ $recibo->tipoEquipo[0]->cliente->nombre}}
                @else
                    Cliente No Encontrado
                @endif
                </td>
                <td>{{ date('d-m-Y', strtotime($recibo->created_at)) }}</td>
                <td>
                    <form action="{{ route('recibos.pdf', ['id' => $recibo->id]) }}" method="GET" target="_blank">
                        @csrf
                        <button type="submit" style="border: none; background-color: transparent; padding: 0;">
                            <img src="{{ url('assets/iconos/file-earmark-arrow-down-fill.svg') }}" width="24" height="24" style="display: block;">
                        </button>
                    </form> 
                </td>
                <td>
                    <button type="button"  onclick="abrirNotaModal({{ $recibo->id }})" style="border: none; background-color: transparent; padding: 0;">
                        <img src="{{ url('assets/iconos/journals.svg') }}" width="24" height="24" style="display: block;">
                    </button>   
                </td>

                     @auth
                        @if(auth()->user()->isAdmin())
                            <td>
                                <button type="button" onclick="abrirModalConfirmacion({{ $recibo->id }})" style="border: none; background-color: transparent; padding: 0;">
                                    <img src="{{ url('assets/iconos/tools.svg') }}" width="24" height="24" style="display: block;">
                                </button>
                            </td>
                        @endif
                    @endauth
                   <td>
                    <button type="button" onclick="abrirModalSubirArchivos({{ $recibo->id }})"
                                style="border: none; background-color: transparent; padding: 0; margin-right: 6px;"
                                title="Subir archivos">
                            <i class="fas fa-cloud-upload-alt fa-lg" style="color: #007bff;"></i>
                    </button>

                    <button type="button" onclick="abrirModalVerArchivos({{ $recibo->id }})"
                                style="border: none; background-color: transparent; padding: 0;"
                                title="Ver archivos">
                            <i class="fas fa-folder-open fa-lg" style="color: #ffc107;"></i>
                    </button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <nav aria-label="...">
        <ul class="pagination">
            {{ $recibos->links() }}
        </ul>
    </nav>

<div id="confirmacionModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="cerrarModalConfirmacion()">&times;</span>

        <h2 class="modal-title">¿La Reparación Ya Fue Completada?</h2>

        <div class="botones">
            <button id="confirmarReparacionButton" onclick="confirmarReparacion1()" data-id="" class="btn-confirmar">
                Regresar al apartado de recibidos
            </button>
        </div>
    </div>
</div>

    <!-- Modal para mostrar y editar la nota -->
<div class="modal fade" id="notaModal" tabindex="-1" role="dialog" aria-labelledby="notaModalLabel" aria-hidden="true">
    <div class="modal-dialog custom-modal-width" role="document">
        <div class="modal-content">
            <div class="modal-header d-flex justify-content-between align-items-center">
                <h4 class="modal-title font-weight-bold" id="notaModalLabel">Nota del Recibo</h4>
            </div>
            <div class="modal-body">
                <p id="notaContent"></p>
                <textarea id="notaInput" style="display: none;"></textarea>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="cerrarNotaModal()">Cerrar</button>
                
                <button type="button" id="guardarNotaButton" class="btn btn-primary" style="display: none;" onclick="guardarNota()">Guardar</button>
                <button type="button" id="editNotaButton" class="btn btn-success" onclick="habilitarEdicionNota()">Editar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para subir archivos -->
<div class="modal fade" id="modalSubirArchivos" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Subir Archivos</h5>
            </div>

            <div class="modal-body">

                <!-- Input con validación de tipo -->
                <input 
                    type="file" 
                    id="inputArchivos" 
                    multiple 
                    class="form-control"
                    onchange="validarArchivos(event)"
                >

            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button class="btn btn-primary" onclick="guardarArchivos()">Subir</button>
            </div>

        </div>
    </div>
</div>


<!-- Modal para ver archivos -->
<div class="modal fade" id="modalVerArchivos" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Archivos del Recibo</h5>
            </div>

            <div class="modal-body">
                <ul id="listaArchivos"></ul>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>

        </div>
    </div>
</div>

@endsection

