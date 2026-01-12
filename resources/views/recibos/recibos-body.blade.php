 @foreach($recibos as $recibo)
            <tr class="recibo-row" data-estado="{{ $recibo->estado }}">
                 @auth
                   @if(auth()->user()->isAdmin())
                <td class="text-center">
                    <button type="button"
                            onclick="abrirRevision({{ $recibo->id }})"
                            style="border: none; background-color: transparent; padding: 0;">
                        <img src="{{ url('assets/iconos/card-checklist.svg') }}" width="24" height="24">
                    </button>
                </td>
                 @endif
                @endauth
                <td>
                    @if(isset($recibo->tipoEquipo[0]->cliente))
                        {{ $recibo->tipoEquipo[0]->cliente->nombre}}
                    @else
                        Cliente No Encontrado
                    @endif
                </td>
                <td>{{ date('d-m-Y', strtotime($recibo->created_at)) }}</td>
                <td>
                    <form action="{{ route('recibos.pdf', ['id' => $recibo->id]) }}" method="GET" target="_blank">
                        @csrf
                        <button type="submit" style="border: none; background-color: transparent; padding: 0;">
                            <img src="{{ url('assets/iconos/file-earmark-arrow-down-fill.svg') }}" width="24" height="24">
                        </button>
                    </form>
                </td>
                 <td class="text-center">
                    <div class="d-flex align-items-center justify-content-center gap-2">

                        
                        @if($recibo->id_estado == 5)
                            <img src="{{ url('assets/iconos/gear.svg') }}" width="24" height="24">
                        @endif

                        
                        <button type="button"
                                onclick="abrirModalConfirmacion({{ $recibo->id }})"
                                style="border: none; background-color: transparent; padding: 0;">
                            <img src="{{ url('assets/iconos/tools.svg') }}" width="24" height="24">
                        </button>

                    </div>
                </td>

                <td>
                    <button type="button" onclick="abrirNotaModal({{ $recibo->id }})" style="border: none; background-color: transparent; padding: 0;">
                        <img src="{{ url('assets/iconos/journals.svg') }}" width="24" height="24">
                    </button>
                </td>
                
            </tr>
            @endforeach