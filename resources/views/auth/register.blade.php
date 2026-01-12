@extends('layouts.auth-master') 
@section('content')

<div class="container">
    <div class="form-container">
        <form action="/register" method="POST">
        @csrf
        <h1>Crear Usuarios</h1>
        @include('layouts.partials.messages')
        <div class="form-floating mb-3">
            <input type="text" name="nombre" class="form-control" id="exampleInputEmail1" placeholder="Nombre">
            <label for="exampleInputEmail1" >Nombre</label>
        </div>
        <div class="form-floating mb-3">
            <input type="text" name="usuario" class="form-control" id="exampleInputEmail2" placeholder="UserName">
            <label for="exampleInputEmail2" >UserName</label>   
        </div>

        <div class="form-floating mb-3">
            <input type="password" name="password" class="form-control" id="exampleInputPassword1" placeholder="Password">
            <label for="exampleInputPassword1">Password</label>
        </div>
       
        <div class="form-floating mb-3">
            <input type="password" name="password_confirmation" class="form-control" id="exampleInputPassword2" placeholder="Repetir Password">
            <label for="exampleInputPassword2">Repetir Password</label>
        </div>
       
        <button type="submit" class="btn btn-primary">Crear Usuario</button>
        </form>
    </div>
</div>

@endsection
