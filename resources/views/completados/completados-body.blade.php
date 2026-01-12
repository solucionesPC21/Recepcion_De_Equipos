@foreach($recibos as $recibo)
            <tr>
                <td>
                    @if(isset($recibo->tipoEquipo[0]->cliente))
                        {{ $recibo->tipoEquipo[0]->cliente->nombre}}
                    @else
                        Cliente No Encontrado
                    @endif
                </td>
                </td>
                <td>{{ date('d-m-Y', strtotime($recibo->created_at)) }}</td>
                <td>{{ date('d-m-Y', strtotime($recibo->fechaReparacion)) }}</td>

                <td>
                    <form action="{{ route('recibos.pdf', ['id' => $recibo->id]) }}" method="GET" target="_blank">
                        @csrf
                        <button type="submit" style="border: none; background-color: transparent; padding: 0;">
                            <img src="{{ url('assets/iconos/file-earmark-arrow-down-fill.svg') }}" width="24" height="24" style="display: block;">
                        </button>
                    </form>
                </td>

                <td>
                @if($recibo->ticket)
                        <form action="{{ route('completados.pdf', ['id' => $recibo->ticket->id]) }}" method="GET" target="_blank">
                            @csrf
                            <button type="submit" style="border: none; background-color: transparent; padding: 0;">
                                <img src="{{ url('assets/iconos/file-earmark-arrow-down-fill1.svg') }}" width="24" height="24" style="display: block;">
                            </button>
                        </form>
                @else
                    <span class="text-muted">Sin ticket</span>
                @endif
                </td>
                <td>
                    <button type="button"  onclick="abrirNotaModal({{ $recibo->id }})" style="border: none; background-color: transparent; padding: 0;">
                        <img src="{{ url('assets/iconos/journals.svg') }}" width="24" height="24" style="display: block;">
                    </button>   
                </td>
                
                <td>
                    <button type="button" onclick="abrirModalSubirArchivos({{ $recibo->id }})"
                                style="border: none; background-color: transparent; padding: 0; margin-right: 6px;"
                                title="Subir archivos">
                            <i class="fas fa-cloud-upload-alt fa-lg" style="color: #007bff;"></i>
                    </button>

                    <button type="button" onclick="abrirModalVerArchivos({{ $recibo->id }})"
                                style="border: none; background-color: transparent; padding: 0;"
                                title="Ver archivos">
                            <i class="fas fa-folder-open fa-lg" style="color: #ffc107;"></i>
                    </button>
                </td>
            </tr>
            @endforeach