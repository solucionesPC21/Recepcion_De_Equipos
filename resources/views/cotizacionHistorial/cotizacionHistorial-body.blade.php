  @foreach($cotizaciones as $index => $cotizacion)
                                <tr class="border-bottom">
                                    <td class="ps-4 fw-bold text-primary">
                                        {{ $loop->iteration }}
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                                                <i class="fas fa-user text-primary"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold">{{ $cotizacion->nombre_cliente }}</h6>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2">
                                            <i class="fas fa-calendar me-1"></i>
                                            {{ \Carbon\Carbon::parse($cotizacion->fecha_creacion)->format('d/m/Y') }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-3 py-2">
                                            <i class="fas fa-clock me-1"></i>
                                            {{ \Carbon\Carbon::parse($cotizacion->fecha_creacion)->format('H:i') }}
                                        </span>
                                    </td>
                                    <td class="text-center pe-4">
                                        <div class="btn-group" role="group">
                                            @if($cotizacion->pdf_cliente)
                                            <button type="button" 
                                                    class="btn btn-outline-primary btn-sm px-3" 
                                                    onclick="verPDF({{ $cotizacion->id }}, 'cliente')"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    title="Abrir PDF para Cliente en nueva pestaña">
                                                <i class="fas fa-user-tie me-1"></i> Cliente
                                            </button>
                                            @endif
                                            
                                            @if($cotizacion->pdf_interno)
                                            <button type="button" 
                                                    class="btn btn-outline-info btn-sm px-3" 
                                                    onclick="verPDF({{ $cotizacion->id }}, 'interno')"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    title="Abrir PDF Interno en nueva pestaña">
                                                <i class="fas fa-building me-1"></i> Interno
                                            </button>
                                            @endif
                                            
                                             @if(auth()->user()->isAdmin())
                                            <button type="button" 
                                                    class="btn btn-outline-danger btn-sm px-3" 
                                                    onclick="eliminarCotizacion({{ $cotizacion->id }})"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    title="Eliminar Cotización">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach