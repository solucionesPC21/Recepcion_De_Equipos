<nav class="navbar navbar-expand-lg" style="background-color: #419BCE;padding: 20px;">
  <div class="container-fluid">
    <a class="navbar-brand" href="{{ route('home.index') }}">Soluciones PC</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent"  style="color: white;">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0" >
      <li class="nav-item" style="margin-right: 10px;">
          <a class="nav-link active" aria-current="page" href="{{ route('home.index') }}" style="color: white;font-size: 20px;"" >Inicio</a>
      </li>

      <li class="nav-item" style="margin-right: 10px;">
          <a class="nav-link active" aria-current="page" href="{{ route('ventas.index') }}" style="color: white;font-size: 20px;"">Punto De Venta</a>
      </li>

      <li class="nav-item" style="margin-right: 10px;">
      <a class="nav-link active" aria-current="page" href="{{ route('recibos.index') }}" style="color: white;font-size: 20px;"">Recibidos</a>
      </li>

      <li class="nav-item" style="margin-right: 10px;">
      <a class="nav-link active" aria-current="page" href="{{ route('ticket.index') }}" style="color: white;font-size: 20px;"">Generar Ticket</a>
      </li>

      <li class="nav-item" style="margin-right: 10px;">
      <a class="nav-link active" aria-current="page" href="{{ route('pagos.index') }}" style="color: white;font-size: 20px;">Pagos</a>
      </li>

      <li class="nav-item" style="margin-right: 10px;">
      <a class="nav-link active" aria-current="page" href="{{ route('nota-abonos.index') }}" style="color: white;font-size: 20px;">Notas De Abonos</a>
      </li>

      <li class="nav-item" style="margin-right: 10px;">
      <a class="nav-link active" aria-current="page" href="{{ route('cotizacion.index') }}" style="color: white;font-size: 20px;">Cotizacion</a>
      </li>

      </ul>
      @auth
      <form class="d-flex" role="search">
        <ul class="navbar-nav me-5 mb-2 mb-lg-0">
       
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: black;font-size: 17px;">
          {{auth()->user()->usuario}}
          </a>
          <ul class="dropdown-menu">
          <li><a class="dropdown-item" aria-current="page" href="{{ route('clientes.index') }}">Clientes</a></li>
          <li><a class="dropdown-item" aria-current="page" href="{{ route('productos.index') }}">Productos</a></li>
          <li><a class="dropdown-item" aria-current="page" href="{{ route('servicios.index') }}">Servicios</a></li>
          <li><a class="dropdown-item" aria-current="page" href="{{ route('marcas.index') }}">Marcas</a></li>
          <li><a class="dropdown-item" aria-current="page" href="{{ route('tipo_equipos.index') }}">Tipo De Equipos</a>
          <li><a class="dropdown-item" aria-current="page" href="{{ route('colonias.index') }}">Colonias</a></li> 
          <li><a class="dropdown-item" aria-current="page" href="{{ route('completados.index') }}">Trabajos Completados </a></li>
          <li><a class="dropdown-item" aria-current="page" href="{{ route('recibos.rechazado') }}">Cancelados</a></li>  
          <li><a class="dropdown-item" aria-current="page" href="{{ route('impresoras.index') }}">Configuracion</a></li>    
          @auth
              @if(auth()->user()->isAdmin())
                  <li><a class="dropdown-item" aria-current="page" href="{{ route('users.index') }}">Usuarios</a></li>
              @endif
          @endauth
          <li><a class="dropdown-item" href="\logout">Salir</a></li>
      </li>
          </ul>
        </li>
     
      </ul>
      </form>
      @endauth
    </div>
  </div>
</nav>
<br>
