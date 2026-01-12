@extends('layouts.auth-master')
@section('navbar')
<!-- No incluyes nada aquí para excluir la barra de navegación -->
@endsection

@section('content')
<form action="/login" method="POST">
    @csrf
    <h1>Login</h1>
    @include('layouts.partials.messages')
    <div class="mb-3">
        <label for="exampleInputEmail1" class="form-label">Usuario</label>
        <input type="text" name="usuario" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp">
     
    </div>
    <div class="mb-3">
        <label for="exampleInputPassword1" class="form-label">Password</label>
        <input type="password" name="password" class="form-control" id="exampleInputPassword1">
    </div>
    <button type="submit" class="btn btn-primary">Iniciar Sesion</button>
    </form>
@endsection
    