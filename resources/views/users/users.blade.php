@extends('layouts.users.app-master')
@section('content')
<h1 class="titulo">Registro De Usuarios</h1>

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


<!-- Botón para mostrar modal de crear elemento -->
<a id="registrarUserBtn" class="fcc-btn">Registrar Usuarios</a>
 <!-- Input para búsqueda en tiempo real con estilos de Bootstrap -->
    <div class="input-group mb-3" style="max-width: 700px;">
            <!-- Ajusta el ancho máximo según tus necesidades -->
        <input type="text" id="searchInput" class="form-control" placeholder="Buscar Usuarios..." onkeyup="buscarRecibos()">
    </div>
    <br>
<table class="tabla">
    <thead>
        <tr>
            <th>#</th>
            <th>Nombre</th>
            <th>Usuario</th>
            <th></th>
            <th>Acciones</th>
        </tr>
    </thead>
    
    <tbody  id="recibosBody">
        @foreach($users as $user)
        <tr>
            <td>{{ $user->id }}</td>
            <td>{{ $user->nombre }}</td>
            <td>{{ $user->usuario }}</td>
            <td></td>
            <td>
                <form id="formBorrarUsuario_{{ $user->id }}" action="{{ url('/users/'.$user->id) }}" method="post" style="display: inline;">
                    @csrf
                    {{ method_field('DELETE') }}
                    <input type="submit" value="Borrar" class="btn btn-danger" onclick="return confirm('¿Quieres Borrar?')">
                </form>
                <a class="btn btn-info editar-user" data-user-id="{{ $user->id }}" href="#" onclick="editarUser(event, {{ $user->id }})">Editar</a>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
        <nav aria-label="...">
            <ul class="pagination">
                {{ $users->links() }}
            </ul>
        </nav>

<br>
<br>


<!-- Incluir el modal de creación -->
@include('users.form-registrar')
@include('users.form-editar')

@endsection
