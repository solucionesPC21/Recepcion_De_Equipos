@extends('layouts.colonias.app-master')

@section('content')
<h1 class="titulo">Registro De Colonias</h1>
<!-- Botón para mostrar modal de crear elemento -->
<a id="registrarColoniaBtn" class="fcc-btn">Registrar Colonias</a>

<table class="tabla">
    <thead>
        <tr>
            <th>#</th>
            <th>Colonias</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        @foreach($colonias as $colonia)
        <tr>
            <td>{{ $colonia->id }}</td>
            <td>{{ $colonia->colonia }}</td>
            <td>

                <form id="formBorrarColonia_{{ $colonia->id }}" action="{{ url('/colonias/'.$colonia->id) }}" method="post" style="display: inline;">
                    @csrf
                    {{ method_field('DELETE') }}
                    <input type="submit" value="Borrar" class="btn btn-danger" onclick="return confirm('¿Quieres Borrar?')">
                </form>

                <a class="btn btn-info editar-colonia" data-colonia-id="{{ $colonia->id }}" href="#" onclick="editarColonia(event, {{ $colonia->id }})">Editar</a>


            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<!-- Incluir el modal de creación -->
@include('colonias.form-registrar')

<!-- Incluir el modal de edición -->
@include('colonias.form-editar')
@endsection
