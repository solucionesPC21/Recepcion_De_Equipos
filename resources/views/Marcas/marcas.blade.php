@extends('layouts.marcas.app-master')

@section('content')
<h1 class="titulo">Registro De Marcas</h1>
<!-- Botón para mostrar modal de crear elemento -->
<a id="registrarMarcaBtn" class="fcc-btn">Registrar Marcas</a>

<table class="tabla">
    <thead>
        <tr>
            <th>#</th>
            <th>Marca</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        @foreach($marcas as $marca)
        <tr>
            <td>{{ $marca->id }}</td>
            <td>{{ $marca->marca }}</td>
            <td>
            <form id="formBorrarMarca{{ $marca->id }}" action="{{ url('/marcas/'.$marca->id) }}" method="post" style="display: inline;">
                    @csrf
                    {{ method_field('DELETE') }}
                    <input type="submit" value="Borrar" class="btn btn-danger" onclick="return confirm('¿Quieres Borrar?')">
                </form>

                <a class="btn btn-info editar-marca" data-marca-id="{{ $marca->id }}" href="#" onclick="editarMarca(event, {{ $marca->id }})">Editar</a>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<!-- Incluir el modal de creación -->
@include('Marcas.form-registrar')
@include('Marcas.form-editar')
@endsection
