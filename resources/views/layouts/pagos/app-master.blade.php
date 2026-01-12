<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soluciones PC</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-store" />
   <link rel="stylesheet" href="{{url('assets/css/bootstrap.min.css')}}">
   <link rel="stylesheet" href="{{url('assets/sweetalert2/dist/sweetalert2.min.css')}}">
   <link rel="stylesheet" href="{{url('assets/fontawesome/css/fontawesome.css')}}">
   <link rel="stylesheet" href="{{url('assets/fontawesome/css/regular.css')}}">
   <link rel="stylesheet" href="{{url('assets/fontawesome/css/solid.css')}}">

</head>
<body>
    @include('layouts.partials.navbar')
    <main class="container">
       @yield('content')
    </main>
<script src="{{url('assets/js/jquery-3.7.1.min.js')}}"></script>
<script src="{{url('assets/js/bootstrap.bundle.min.js')}}"></script>
<script src="{{url('assets/sweetalert2/dist/sweetalert2.min.js')}}"></script>
<script src="{{url('assets/js/pago/js.js')}}"></script>




</body>
</html>
 