
        @foreach($recibos as $recibo)
        <div class="recibo-card {{ $recibo->estado->id == 3 ? 'completado' : 'pendiente' }}" 
             id="recibo-{{ $recibo->id }}"
             onclick="seleccionarRecibo({{ $recibo->id }})">
            
            <!-- Header de la Tarjeta -->
            <div class="recibo-header">
                
                <div class="recibo-actions">
                    <button class="btn-action" onclick="event.stopPropagation(); abrirNotaModal({{ $recibo->id }})" 
                            title="Ver comentarios">
                        <i class="fas fa-comment"></i>
                    </button>
        
                    <button class="btn-action" onclick="event.stopPropagation(); abrirCompletadoConfirmar({{ $recibo->id }})" 
                            title="Marcar completado">
                        <i class="fas fa-check-circle"></i>
                    </button>
                  

                     <!-- Ver archivos -->
                    <button class="btn-action" onclick="event.stopPropagation(); abrirModalSubirArchivos({{ $recibo->id }})"
                            title="Subir archivos">
                            <i class="fas fa-cloud-upload-alt fa-lg" style="color: #007bff;"></i>
                    </button>

                    <button class="btn-action" onclick="event.stopPropagation(); abrirModalVerArchivos({{ $recibo->id }})"
                                title="Ver archivos">
                            <i class="fas fa-folder-open fa-lg" style="color: #ffc107;"></i>
                    </button>
                </div>
            </div>

            <!-- Información del Cliente -->
            <div class="client-info">
                <div class="client-avatar">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="client-details">
                    <h6 class="client-name">
                        @if(isset($recibo->tipoEquipo[0]->cliente))
                            {{ $recibo->tipoEquipo[0]->cliente->nombre }}
                        @else
                            Cliente No Encontrado
                        @endif
                    </h6>
                    <p class="client-meta">Recibo #{{ $recibo->id }}</p>
                </div>
            </div>

            <!-- Línea de Tiempo -->
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <span class="timeline-date">{{ date('d M Y', strtotime($recibo->created_at)) }}</span>
                        <span class="timeline-label">Recibido</span>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-marker {{ $recibo->estado->id == 3 ? 'completed' : '' }}"></div>
                    <div class="timeline-content">
                        <span class="timeline-date">{{ date('d M Y', strtotime($recibo->fechaReparacion)) }}</span>
                        <span class="timeline-label">Reparado</span>
                    </div>
                </div>
            </div>

            <!-- Acciones Rápidas -->
            <div class="recibo-actions-footer">
                <button class="btn-quick-action" 
                        onclick="event.stopPropagation(); descargarNotaRecepcion({{ $recibo->id }})"
                        title="Descargar nota de recepción">
                    <i class="fas fa-file-pdf"></i>
                    PDF
                </button>
                <button class="btn-quick-action primary" 
                        onclick="event.stopPropagation(); confirmarGenerarTicket({{ $recibo->id }})"
                        title="Generar ticket de pago">
                    <i class="fas fa-receipt"></i>
                    Ticket
                </button>
            </div>

            <!-- Indicador de Selección -->
            <div class="selection-indicator">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
        @endforeach