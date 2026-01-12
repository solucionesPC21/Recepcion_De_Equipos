@extends('layouts.completados.app-master')

@section('content')


    <h1 class="titulo">Trabajos Completados</h1>
    
    @if (session('success'))
    <div id="success-alert-modal" class="modal-alert">
        <div class="modal-alert-content alert alert-success alert-dismissible fade-out custom-alert" role="alert">
            {{ session('success') }}
            <div class="progress-bar" id="success-progress-bar"></div>
        </div>
    </div>
    @endif

    @if ($errors->any())
        <div id="error-alert-modal" class="modal-alert">
            <div class="modal-alert-content custom-error-message">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                    <div class="progress-bar" id="error-progress-bar"></div>
                </ul>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div id="error-alert" class="modal-alert alert alert-danger alert-dismissible fade-out custom-alert" role="alert">
            {{ session('error') }}
            <div class="progress-bar" id="error-progress-bar"></div>
        </div>
    @endif
     <!-- Input para búsqueda en tiempo real con estilos de Bootstrap -->
     <div class="input-group mb-3" style="max-width: 700px;">
        <!-- Ajusta el ancho máximo según tus necesidades -->
        <input type="text" id="searchInput" class="form-control" placeholder="Buscar recibo..." onkeyup="buscarRecibos()">
        
        <div class="input-group-append">
            <button class="btn btn-outline-secondary" type="button">Buscar</button>
        </div>
    </div>
    
    <div class="d-flex justify-content-between align-items-center mb-3">
    <strong><h2>Total De Ordenes Completadas: {{ $totalRecibos }}</h2></strong>

</div>


    <table class="tabla">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Fecha de Recibido</th>
                <th>Fecha de Reparado</th>
                <th>Nota De Recepción</th>
                <th>Ticket</th>
                <th>Comentarios</th>
                <th>Archivos</th> 
            </tr>
        </thead>
        <tbody id="recibosBody">
            @foreach($recibos as $recibo)
            <tr>
                <td>
                    @if(isset($recibo->tipoEquipo[0]->cliente))
                        {{ $recibo->tipoEquipo[0]->cliente->nombre}}
                    @else
                        Cliente No Encontrado
                    @endif
                </td>
                </td>
                <td>{{ date('d-m-Y', strtotime($recibo->created_at)) }}</td>
                <td>{{ date('d-m-Y', strtotime($recibo->fechaReparacion)) }}</td>

                <td>
                    <form action="{{ route('recibos.pdf', ['id' => $recibo->id]) }}" method="GET" target="_blank">
                        @csrf
                        <button type="submit" style="border: none; background-color: transparent; padding: 0;">
                            <img src="{{ url('assets/iconos/file-earmark-arrow-down-fill.svg') }}" width="24" height="24" style="display: block;">
                        </button>
                    </form>
                </td>

                <td>
                @if($recibo->ticket)
                        <form action="{{ route('completados.pdf', ['id' => $recibo->ticket->id]) }}" method="GET" target="_blank">
                            @csrf
                            <button type="submit" style="border: none; background-color: transparent; padding: 0;">
                                <img src="{{ url('assets/iconos/file-earmark-arrow-down-fill1.svg') }}" width="24" height="24" style="display: block;">
                            </button>
                        </form>
                  @else
                        <span class="text-muted">Sin ticket</span>
                    @endif

                </td>

                <td>
                    <button type="button"  onclick="abrirNotaModal({{ $recibo->id }})" style="border: none; background-color: transparent; padding: 0;">
                        <img src="{{ url('assets/iconos/journals.svg') }}" width="24" height="24" style="display: block;">
                    </button>   
                </td>
                
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
            <ul class="pagination" id="paginacion">
                {{ $recibos->links() }}
            </ul>
    </nav>

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
