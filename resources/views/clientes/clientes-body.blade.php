 @foreach($clientes as $cliente)
                        <tr class="fade-in-up">
                            <td><span class="badge bg-primary">{{ $cliente->id }}</span></td>
                            <td><strong>{{ $cliente->nombre }}</strong></td>
                            <td>{{ $cliente->telefono }}</td>
                            <td>{{ $cliente->telefono2 ?: '-' }}</td>
                            <td>{{ $cliente->rfc ?: '-' }}</td>
                            <td>
                                <span class="status-badge {{ $cliente->colonia}}">
                                    {{ $cliente->colonia ? $cliente->colonia->colonia : 'Sin colonia' }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    @if(auth()->user()->isAdmin())
                                    <form action="{{ url('/clientes/'.$cliente->id) }}" method="post" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm me-2" 
                                                title="Eliminar cliente">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                    <button class="btn btn-info btn-sm editarClienteBtn" 
                                            data-cliente-id="{{ $cliente->id }}"
                                            title="Editar cliente">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach