@extends('layouts.tipoEquipos.app-master')

@section('content')
<h1 class="titulo">Registro De Tipo De Equipos</h1>
<!-- Botón para mostrar modal de crear elemento -->
<a id="registrarTipoEquipoBtn" class="fcc-btn">Registrar Tipo De Equipo</a>

<table class="tabla">
    <thead>
        <tr>
            <th>#</th>
            <th>Tipo De Equipo</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        @foreach($equipos as $equipo)
        <tr>
            <td>{{ $equipo->id }}</td>
            <td>{{ $equipo->equipo }}</td>
            <td>
                <form id="formBorrarTipoEquipo{{ $equipo->id }}" action="{{ url('/tipo_equipos/'.$equipo->id) }}" method="post" style="display: inline;">
                    @csrf
                    {{ method_field('DELETE') }}
                    <input type="submit" value="Borrar" class="btn btn-danger" onclick="return confirm('¿Quieres Borrar?')">
                </form>
                <a class="btn btn-info editar-TipoEquipo" data-marca-id="{{ $equipo->id }}" href="#" onclick="editarTipoEquipo(event, {{ $equipo->id }})">Editar</a>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<!-- Incluir el modal de creación -->
@include('tipoEquipos.form-registrar')
@include('tipoEquipos.form-editar')
@endsection
