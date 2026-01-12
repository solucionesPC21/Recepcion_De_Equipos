<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de Equipo de Computo</title>
    <link rel="stylesheet" href="{{public_path('assets/css/pdf/css.css')}}" type="text/css">
</head>
<body>
@if ($cantidadTiposEquipo <=2)
    <div class="recibo">
        <div class="orden">
        <p><strong>ORDEN DE REPARACIÓN: {{$recibo->id}}<br>
            SOLUCIONES PC</strong></p>
            <p><strong>C. Adolfo Lopez Mateos #110, Ejido Nuevo Mexicali
            San Quintin, CP:22930<br>
            Teléfono: 6161362976<br>
            </strong></p>
        </div>

        <div class="info-ingreso">
    <div class="usuario">
        <p><strong>Fecha De Ingreso:</strong> {{date('d/m/Y', strtotime($recibo->tipoEquipo[0]->fecha))}}<br>
        <strong>Hora De Ingreso:</strong> {{ $recibo->tipoEquipo[0]->hora }}<br>
        <strong>Recepcionado Por:</strong> {{ $recibo->tipoEquipo[0]->usuario }}</p>
    </div>
    <div class="nota">
        <h6 class="orden"><strong>NOTA</strong><br>- No nos hacemos responsables pasado tres días<br>de haber informado al cliente sobre la finalización de la reparación de su equipo.
        <br>-El equipo se reparará según el orden de llegada al taller<br>asegurando un servicio eficiente y justo para todos nuestros clientes.</h6>
    </div>
</div>


        <div class="cliente-info" style="margin-top: -12px;">  
            <ul class="cliente-list">
            <p><strong>Información Del Cliente</strong></p>
<li>
    <p><strong>Nombre:</strong> 
        @if(isset($recibo->tipoEquipo[0]->cliente))
            {{ $recibo->tipoEquipo[0]->cliente->nombre}}
        @else
            Cliente No Encontrado
        @endif
    </p>
</li>
<li>
    <p><strong>Dirección:</strong>       
        @if(isset($recibo->tipoEquipo[0]->cliente->colonia))
            {{ $recibo->tipoEquipo[0]->cliente->colonia->colonia}}
        @else
            <!-- Si no hay una colonia registrada, mostrar en blanco -->
        @endif
    </p>
</li>
<li>
    <p><strong>Teléfono:</strong> 
        @if(isset($recibo->tipoEquipo[0]->cliente))
            {{ $recibo->tipoEquipo[0]->cliente->telefono}}
        @else
            Cliente No Encontrado
        @endif
    </p>
</li>

            </ul></p>
        </div>
        <br>
        <div class="cliente-info" style="margin-top: -25px;">
        @if ($cantidadTiposEquipo == 1)
        <p><strong>Información Del Equipo</strong><br></p>
        @else
            <p><strong>Información De Los Equipos</strong><br></p>
        @endif
    @foreach ($recibo->tipoEquipo as $equipo)
    <div class="cliente-list">
        <div class="cliente-list-column1">
            <li><p><strong>Tipo De Equipo: </strong>{{ $equipo->equipo->equipo }}</p></li>
            <li><p><strong>Marca: </strong>{{ $equipo->marca->marca }}</p></li>
            <li><p><strong>Modelo: </strong>{{ $equipo->modelo }}</p></li>
        </div>
        <div class="cliente-list-column1">
            <li><p><strong>NS: </strong>{{ $equipo->ns }}</p></li>
            <li class="accesorios"><p><strong>Accesorios: </strong>{{ $equipo->accesorio }}</p></li>
        </div>
    </div>
    <p class="falla1"><strong>Falla: </strong>{{ $equipo->falla }}</p>
@endforeach
<br>
<div class="footer">
    <p style="display: inline;"><strong>Firma Del Cliente:</strong></p>
    <hr style="display: inline-block; border-top: 0 solid black; margin-left: 5px; width: 200px; margin-top: 0; margin-bottom: 0;">
</div>
<br>
<br>
@if ($cantidadTiposEquipo <=1)
<br>
<br>
<br>
<br>
<br>
@endif
<div class="recibo">
        <div class="orden">
        <p><strong>ORDEN DE REPARACIÓN: {{$recibo->id}}<br>
            SOLUCIONES PC</strong></p>
            <p><strong>C. Adolfo Lopez Mateos #110, Ejido Nuevo Mexicali
            San Quintin, CP:22930<br>
            Teléfono: 6161362976<br>
            </strong></p>
        </div>

        <div class="info-ingreso">
    <div class="usuario">
        <p><strong>Fecha De Ingreso:</strong> {{date('d/m/Y', strtotime($recibo->tipoEquipo[0]->fecha))}}<br>
        <strong>Hora De Ingreso:</strong> {{ $recibo->tipoEquipo[0]->hora }}<br>
        <strong>Recepcionado Por:</strong> {{ $recibo->tipoEquipo[0]->usuario }}</p>
    </div>
    <div class="nota">
        <h6 class="orden"><strong>NOTA</strong><br>- No nos hacemos responsables pasado tres días<br>de haber informado al cliente sobre la finalización de la reparación de su equipo.
        <br>-El equipo se reparará según el orden de llegada al taller<br>asegurando un servicio eficiente y justo para todos nuestros clientes.</h6>
    </div>
</div>


        <div class="cliente-info" style="margin-top: -12px;">  
            <ul class="cliente-list">
            <p><strong>Información Del Cliente</strong></p>
<li>
    <p><strong>Nombre:</strong> 
        @if(isset($recibo->tipoEquipo[0]->cliente))
            {{ $recibo->tipoEquipo[0]->cliente->nombre}}
        @else
            Cliente No Encontrado
        @endif
    </p>
</li>
<li>
    <p><strong>Dirección:</strong>       
        @if(isset($recibo->tipoEquipo[0]->cliente->colonia))
            {{ $recibo->tipoEquipo[0]->cliente->colonia->colonia}}
        @else
            <!-- Si no hay una colonia registrada, mostrar en blanco -->
        @endif
    </p>
</li>
<li>
    <p><strong>Teléfono:</strong> 
        @if(isset($recibo->tipoEquipo[0]->cliente))
            {{ $recibo->tipoEquipo[0]->cliente->telefono}}
        @else
            Cliente No Encontrado
        @endif
    </p>
</li>

            </ul></p>
        </div>
        <br>
        <div class="cliente-info" style="margin-top: -25px;">
        @if ($cantidadTiposEquipo == 1)
        <p><strong>Información Del Equipo</strong><br></p>
        @else
            <p><strong>Información De Los Equipos</strong><br></p>
        @endif
    @foreach ($recibo->tipoEquipo as $equipo)
    <div class="cliente-list">
        <div class="cliente-list-column1">
            <li><p><strong>Tipo De Equipo: </strong>{{ $equipo->equipo->equipo }}</p></li>
            <li><p><strong>Marca: </strong>{{ $equipo->marca->marca }}</p></li>
            <li><p><strong>Modelo: </strong>{{ $equipo->modelo }}</p></li>
        </div>
        <div class="cliente-list-column1">
            <li><p><strong>NS: </strong>{{ $equipo->ns }}</p></li>
            <li class="accesorios"><p><strong>Accesorios: </strong>{{ $equipo->accesorio }}</p></li>
        </div>
    </div>
    <p class="falla1"><strong>Falla: </strong>{{ $equipo->falla }}</p>
@endforeach
<br>

<div class="footer">
    <p style="display: inline;"><strong>Firma Del Cliente:</strong></p>
    <hr style="display: inline-block; border-top: 0 solid black; margin-left: 5px; width: 200px; margin-top: 0; margin-bottom: 0;">
</div>

@endif

@if ($cantidadTiposEquipo >=3)
<div class="recibo">
        <div class="orden">
            <p><strong>ORDEN DE REPARACIÓN: {{$recibo->id}}<br>
            SOLUCIONES PC<br>
            C. Adolfo Lopez Mateos #110, Ejido Nuevo Mexicali
            San Quintin, CP:22930<br>
            Teléfono: 6161362976<br>
        </strong> </p>
        </div>

        <div class="info-ingreso">
    <div class="usuario">
        <p><strong>Fecha De Ingreso:</strong> {{date('d/m/Y', strtotime($recibo->tipoEquipo[0]->fecha))}}<br>
        <strong>Hora De Ingreso:</strong> {{ $recibo->tipoEquipo[0]->hora }}<br>
        <strong>Recepcionado Por:</strong> {{ $recibo->tipoEquipo[0]->usuario }}</p>
    </div>
    <div class="nota">
        <h6 class="orden"><strong>NOTA</strong><br>- No nos hacemos responsables pasado tres días<br>de haber informado al cliente sobre la finalización de la reparación de su equipo.
        <br>-El equipo se reparará según el orden de llegada al taller<br>asegurando un servicio eficiente y justo para todos nuestros clientes.</h6>
    </div>
</div>


        <div class="cliente-info" style="margin-top: -12px;">  
            <ul class="cliente-list">
            <p><strong>Información Del Cliente</strong></p>
<li>
    <p><strong>Nombre:</strong> 
        @if(isset($recibo->tipoEquipo[0]->cliente))
            {{ $recibo->tipoEquipo[0]->cliente->nombre}}
        @else
            Cliente No Encontrado
        @endif
    </p>
</li>
<li>
    <p><strong>Dirección:</strong>       
        @if(isset($recibo->tipoEquipo[0]->cliente->colonia))
            {{ $recibo->tipoEquipo[0]->cliente->colonia->colonia}}
        @else
            <!-- Si no hay una colonia registrada, mostrar en blanco -->
        @endif
    </p>
</li>
<li>
    <p><strong>Teléfono:</strong> 
        @if(isset($recibo->tipoEquipo[0]->cliente))
            {{ $recibo->tipoEquipo[0]->cliente->telefono}}
        @else
            Cliente No Encontrado
        @endif
    </p>
</li>

            </ul></p>
        </div>
        <br>
        <div class="cliente-info" style="margin-top: -25px;">
        @if ($cantidadTiposEquipo == 1)
        <p><strong>Información Del Equipo</strong><br></p>
        @else
            <p><strong>Información De Los Equipos</strong><br></p>
        @endif
    @foreach ($recibo->tipoEquipo as $equipo)
    <div class="cliente-list">
        <div class="cliente-list-column">
            <li><p><strong>Tipo De Equipo: </strong>{{ $equipo->equipo->equipo }}</p></li>
            <li><p><strong>Marca: </strong>{{ $equipo->marca->marca }}</p></li>
            <li><p><strong>Modelo: </strong>{{ $equipo->modelo }}</p></li>
        </div>
        <div class="cliente-list-column">
            <li><p><strong>NS: </strong>{{ $equipo->ns }}</p></li>
            <li class="accesorios"><p><strong>Accesorios: </strong>{{ $equipo->accesorio }}</p></li>
        </div>
    </div>
    <p class="falla"><strong>Falla: </strong>{{ $equipo->falla }}</p>
    <hr class="linea-separadora"> <!-- Agregar una línea separadora entre cada equipo -->
@endforeach

<div class="footer">
    <p style="display: inline;"><strong>Firma Del Cliente:</strong></p>
    <hr style="display: inline-block; border-top: 0 solid black; margin-left: 5px; width: 200px; margin-top: 0; margin-bottom: 0;">
</div>
<br>
<br>
<div class="recibo">
        <div class="orden">
            <p><strong>ORDEN DE REPARACIÓN: {{$recibo->id}}<br>
            SOLUCIONES PC<br>
            C. Adolfo Lopez Mateos #110, Ejido Nuevo Mexicali
            San Quintin, CP:22930<br>
            Teléfono: 6161362976<br>
        </strong> </p>
        </div>

        <div class="info-ingreso">
    <div class="usuario">
        <p><strong>Fecha De Ingreso:</strong> {{date('d/m/Y', strtotime($recibo->tipoEquipo[0]->fecha))}}<br>
        <strong>Hora De Ingreso:</strong> {{ $recibo->tipoEquipo[0]->hora }}<br>
        <strong>Recepcionado Por:</strong> {{ $recibo->tipoEquipo[0]->usuario }}</p>
    </div>
    <div class="nota">
        <h6 class="orden"><strong>NOTA</strong><br>- No nos hacemos responsables pasado tres días<br>de haber informado al cliente sobre la finalización de la reparación de su equipo.
        <br>-El equipo se reparará según el orden de llegada al taller<br>asegurando un servicio eficiente y justo para todos nuestros clientes.</h6>
    </div>
</div>


        <div class="cliente-info" style="margin-top: -12px;">  
            <ul class="cliente-list">
            <p><strong>Información Del Cliente</strong></p>
<li>
    <p><strong>Nombre:</strong> 
        @if(isset($recibo->tipoEquipo[0]->cliente))
            {{ $recibo->tipoEquipo[0]->cliente->nombre}}
        @else
            Cliente No Encontrado
        @endif
    </p>
</li>
<li>
    <p><strong>Dirección:</strong>       
        @if(isset($recibo->tipoEquipo[0]->cliente->colonia))
            {{ $recibo->tipoEquipo[0]->cliente->colonia->colonia}}
        @else
            <!-- Si no hay una colonia registrada, mostrar en blanco -->
        @endif
    </p>
</li>
<li>
    <p><strong>Teléfono:</strong> 
        @if(isset($recibo->tipoEquipo[0]->cliente))
            {{ $recibo->tipoEquipo[0]->cliente->telefono}}
        @else
            Cliente No Encontrado
        @endif
    </p>
</li>

            </ul></p>
        </div>
        <br>
        <div class="cliente-info" style="margin-top: -25px;">
        @if ($cantidadTiposEquipo == 1)
        <p><strong>Información Del Equipo</strong><br></p>
        @else
            <p><strong>Información De Los Equipos</strong><br></p>
        @endif
    @foreach ($recibo->tipoEquipo as $equipo)
    <div class="cliente-list">
        <div class="cliente-list-column">
            <li><p><strong>Tipo De Equipo: </strong>{{ $equipo->equipo->equipo }}</p></li>
            <li><p><strong>Marca: </strong>{{ $equipo->marca->marca }}</p></li>
            <li><p><strong>Modelo: </strong>{{ $equipo->modelo }}</p></li>
        </div>
        <div class="cliente-list-column">
            <li><p><strong>NS: </strong>{{ $equipo->ns }}</p></li>
            <li class="accesorios"><p><strong>Accesorios: </strong>{{ $equipo->accesorio }}</p></li>
        </div>
    </div>
    <p class="falla"><strong>Falla: </strong>{{ $equipo->falla }}</p>
    <hr class="linea-separadora"> <!-- Agregar una línea separadora entre cada equipo -->
@endforeach

<div class="footer">
    <p style="display: inline;"><strong>Firma Del Cliente:</strong></p>
    <hr style="display: inline-block; border-top: 0 solid black; margin-left: 5px; width: 200px; margin-top: 0; margin-bottom: 0;">
</div>
@endif
</body>
</html>
