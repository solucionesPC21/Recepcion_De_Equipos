@forelse($clientes as $cliente)
<tr>
    <td>{{ $cliente->id }}</td>
    <td>{{ $cliente->nombre }}</td>
    <td>{{ $cliente->telefono ?? 'N/A' }}</td>
    <td>{{ $cliente->correo ?? 'N/A' }}</td>
    <td>
        @if($cliente->regimen)
            {{ $cliente->regimen->nombre }}
        @else
            N/A
        @endif
    </td>
    <td>
        <div class="btn-group" role="group">
            <button class="btn btn-warning btn-sm" title="Ver cliente" onclick="NotasAbonoApp.verCliente({{ $cliente->id }})">
                <i class="fas fa-eye"></i>
            </button>
            <button class="btn btn-warning btn-sm" title="Editar cliente" onclick="NotasAbonoApp.editarCliente({{ $cliente->id }})">
                <i class="fas fa-edit"></i>
            </button>
        </div>
    </td>
    <td>
    <div class="btn-group" role="group">
        <a href="{{ route('abonos-abonar.index', ['cliente_id' => $cliente->id]) }}" 
           class="btn btn-info btn-sm" title="Ver abonos">
            <i class="fas fa-coins me-1"></i> 
        </a>
    </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="7" class="text-center py-4">
        <i class="fas fa-users fa-2x text-muted mb-3"></i>
        <p class="text-muted">No se encontraron clientes que coincidan con la búsqueda.</p>
    </td>
</tr>
@endforelse