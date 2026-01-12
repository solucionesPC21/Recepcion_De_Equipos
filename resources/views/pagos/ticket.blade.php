<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{public_path('assets/css/pdfTicket/css.css')}}" type="text/css">
    <title>TICKET</title>
   
</head>
<body>
    <div class="ticket">
        <div class="header">
            <p>Soluciones PC</p>
            <p>RFC: ZARE881013I12</p>
            <p>Tel. 6161362976</P>   

        
        </div>
         
        <div class="fecha">
                 <p><strong>Fecha: </strong>{{ date('d/m/Y', strtotime($ticket->fecha)) }}</p>
            </div>

            <div class="info">
                @if($ticket->cliente)
                    <p><strong>Cliente: </strong>{{ $ticket->cliente->nombre }}</p>
                    
                    @if($ticket->cliente->colonia)
                        <p><strong>Colonia: </strong>{{ $ticket->cliente->colonia->colonia }}</p>
                    @endif
                @endif
            </div>
        
        <table class="ticket-table">
            <tr>
                <th class="cantidad-column">Cant.</th>
                <th class="concepto-column">Concepto</th>
                <th class="total-column">Total</th>
            </tr>
            @foreach ($conceptos as $concepto)
            <tr>
                <td class="cantidad-column">{{ $concepto->cantidad }}</td>
                <td>
                    {{ $concepto->nombreConcepto?->nombre ?? '' }}
                    {{ $concepto->nombreConcepto?->marca ? ' ' . $concepto->nombreConcepto->marca : '' }}
                     {{ $concepto->nombreConcepto?->modelo ? ' ' . $concepto->nombreConcepto->modelo : '' }}
                       {{ $concepto->nombreConcepto?->descripcion ? ' - ' . $concepto->nombreConcepto->descripcion : '' }}
                </td>
                <td class="total-column">${{ number_format($concepto->total, 2) }}</td>
                <!-- Otras columnas del concepto -->
            </tr>
            @endforeach
        </table>
 
        <div class="total">
            <p><strong>Total</strong>: ${{ number_format($ticket->total, 2) }}</p>
        </div>

        <div class="Pago">
            <p><strong>Pago</strong>: {{ $ticket->tipoPago->tipoPago}}</p>
        </div>

        <div class="caja">
          <p><strong>Cobrado Por: </strong>: {{ $ticket->usuario}}</p>
        </div>
    </div>
</body>
</html>
