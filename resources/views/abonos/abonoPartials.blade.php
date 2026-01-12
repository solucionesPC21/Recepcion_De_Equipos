  @foreach($ventas as $venta)
                        <tr data-venta-id="{{ $venta->id }}">
                            <td class="text-center">{{ $venta->id }}</td>
                            <td>{{ $venta->cliente->nombre }}</td>
                            <td>{{ $venta->cliente->telefono }}</td>
                            <td class="text-center">
                                <a href="{{ route('ventas-abonos.pdf', $venta->id) }}" 
                                   target="_blank" 
                                   class="btn btn-sm btn-outline-primary"
                                   title="Ver PDF de venta">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </a>
                            </td>
                            <td class="text-right font-weight-bold">
                                <span id="total-display-{{ $venta->id }}">${{ number_format($venta->total, 2) }}</span>
                                @if(auth()->user()->isAdmin())
                                <button class="btn btn-sm btn-outline-secondary ms-1 btn-editar-total" 
                                        data-venta-id="{{ $venta->id }}"
                                        data-total-actual="{{ $venta->total }}"
                                        data-cliente="{{ $venta->cliente->nombre }}"
                                        data-saldo-restante="{{ $venta->saldo_restante }}"
                                        title="Editar total">
                                    <i class="fas fa-edit"></i>
                                </button>
                                @endif
                            </td>
                            <td class="text-right">
                                <span class="px-2 py-1 rounded text-white saldo-display 
                                      bg-{{ $venta->saldo_restante > 0 ? 'warning' : 'success' }}" 
                                      id="saldo-display-{{ $venta->id }}">
                                    ${{ number_format($venta->saldo_restante, 2) }}
                                </span>
                            </td>
                            <td class="text-center">{{ $venta->fecha_venta->format('d/m/Y H:i') }}</td>
                            <td class="text-center">
                                <span class="px-2 py-1 rounded text-white 
                                      bg-{{ $venta->estado->id == 1 ? 'danger' : ($venta->estado->id == 2 ? 'success' : 'secondary') }}">
                                    {{ $venta->estado->id == 1 ? 'Pendiente' : ($venta->estado->id == 2 ? 'Pagado' : $venta->estado->nombre) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    @if($venta->saldo_restante > 0)
                                    <button class="btn btn-sm btn-primary btn-abonar" 
                                            data-venta-id="{{ $venta->id }}"
                                            data-saldo="{{ $venta->saldo_restante }}"
                                            data-cliente="{{ $venta->cliente->nombre }}"
                                            data-productos="{{ $venta->detalles->map(function($item) {
                                                return $item->cantidad.'x '.$item->concepto->nombre;
                                            })->implode(', ') }}"
                                            title="Registrar abono">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </button>
                                    @endif
                                    
                                    <button class="btn btn-sm btn-info btn-historial" 
                                            data-venta-id="{{ $venta->id }}"
                                            title="Ver historial de abonos">
                                        <i class="fas fa-history"></i>
                                    </button>
                                    
                                    @if(auth()->user()->isAdmin())
                                    <button class="btn btn-sm btn-warning btn-editar-productos" 
                                            data-venta-id="{{ $venta->id }}"
                                            data-cliente="{{ $venta->cliente->nombre }}"
                                            title="Editar productos">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    
                                    <button class="btn btn-sm btn-danger btn-eliminar-venta" 
                                            data-venta-id="{{ $venta->id }}"
                                            data-cliente="{{ $venta->cliente->nombre }}"
                                            data-total="${{ number_format($venta->total, 2) }}"
                                            title="Eliminar venta">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach