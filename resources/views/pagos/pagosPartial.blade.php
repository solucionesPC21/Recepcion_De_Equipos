
            @foreach($tickets as $ticket)
            <tr>
                <td class="text-center align-middle">{{ $ticket->id }}</td>
                <td class="text-left align-middle">
                    {{ $ticket->cliente->nombre ?? $ticket->recibo->tipoEquipo[0]->cliente->nombre ?? 'Venta Al Público En General' }}
                </td>
                <td class="text-right align-middle">${{ number_format($ticket->total, 2) }}</td>
                <td class="text-center align-middle">{{ $ticket->fecha ? date('d-m-Y', strtotime($ticket->fecha)) : 'N/A' }}</td>
                <td class="text-center align-middle">
                    <form action="{{ route('pagos.pdf', ['id' => $ticket->id]) }}" method="GET" target="_blank">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-primary" title="Descargar PDF">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                    </form> 
                </td>
                @if(auth()->user()->isAdmin())
                <td class="text-center align-middle">
                    <div class="d-flex justify-content-center">
                        <form id="cancelarForm-{{ $ticket->id }}" action="{{ route('pagos.cancelar', $ticket->id) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="button" class="btn btn-sm btn-danger cancelar-btn" 
                                    data-id="{{ $ticket->id }}" 
                                    data-estado="{{ $ticket->estado_id }}"
                                    title="Cancelar Pago">
                                <i class="fas fa-ban"></i> Cancelar
                            </button>
                        </form>
                    </div>
                </td>
                @endif
            </tr>
            @endforeach
       

