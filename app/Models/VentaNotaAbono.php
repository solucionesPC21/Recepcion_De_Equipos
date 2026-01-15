<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // ← FALTABA ESTA IMPORTACIÓN


class VentaNotaAbono extends Model
{
    use HasFactory;
    
    protected $table = 'ventas_notaabono';

    protected $fillable = [
        'ticket',
        'nota_abono_id',
        'cliente_id',
        'responsable_id',
        'subtotal',
        'iva_calculado',
        'isr_calculado',
        'total',
        'saldo_antes',
        'saldo_despues',
        'total_items',
        'ticket_pdf',
        'estado',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'saldo_antes' => 'decimal:2',
        'saldo_despues' => 'decimal:2',
        'total_items' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Estados permitidos para la venta
     */
    const ESTADO_COMPLETADA = 'completada';
    const ESTADO_CANCELADA = 'cancelada';
    const ESTADO_PARCIALMENTE_DEVUELTA = 'parcialmente_devuelta';
    const ESTADO_TOTALMENTE_DEVUELTA = 'totalmente_devuelta';

    /**
     * Relación con la nota de abono
     */
    public function notaAbono()
    {
        return $this->belongsTo(NotaAbono::class, 'nota_abono_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(ResponsableAbono::class, 'responsable_id');
    }
    /**
     * Relación con el cliente
     */
    public function cliente()
    {
        return $this->belongsTo(ClienteAbono::class, 'cliente_id');
    }
    //
    public function devoluciones()
{
    return $this->hasMany(DevolucionVenta::class, 'venta_id');
}
    /**
     * Relación con los detalles de venta
     */
    public function detalles()
    {
        return $this->hasMany(VentaDetalleNotaAbono::class, 'venta_id');
    }

    /**
     * Relación con los movimientos
     */
    public function movimientos()
    {
        return $this->hasMany(MovimientoAbono::class, 'venta_id');
    }

    /**
     * Scope para ventas completadas
     */
    public function scopeCompletadas($query)
    {
        return $query->where('estado', self::ESTADO_COMPLETADA);
    }

    /**
     * Scope para ventas canceladas
     */
    public function scopeCanceladas($query)
    {
        return $query->where('estado', self::ESTADO_CANCELADA);
    }

    /**
     * Scope para ventas de una nota de abono específica
     */
    public function scopePorNotaAbono($query, $notaAbonoId)
    {
        return $query->where('nota_abono_id', $notaAbonoId);
    }

    /**
     * Generar número de ticket automático
     */
    // En tu modelo VentaNotaAbono
    public static function generarTicket()
    {
        $prefix = 'TICKET-';
        $lastTicket = self::orderBy('id', 'desc')->first();
        
        if ($lastTicket) {
            $lastNumber = (int) str_replace($prefix, '', $lastTicket->ticket);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }
    /**
     * Calcular el total de la venta basado en los detalles
     */
    public function calcularTotal()
    {
        return $this->detalles()->sum('total');
    }

    /**
     * Obtener productos vendidos
     */
    public function getProductosVendidosAttribute()
    {
        return $this->detalles()->with('producto')->get();
    }

    /**
     * Verificar si la venta tiene devoluciones
     */
    public function getTieneDevolucionesAttribute()
    {
        return $this->detalles()->where('devuelto', true)->exists();
    }

    /**
     * Obtener el estado formateado
     */
    public function getEstadoFormateadoAttribute()
    {
        $estados = [
            self::ESTADO_COMPLETADA => 'Completada',
            self::ESTADO_CANCELADA => 'Cancelada',
            self::ESTADO_PARCIALMENTE_DEVUELTA => 'Parcialmente Devuelta',
            self::ESTADO_TOTALMENTE_DEVUELTA => 'Totalmente Devuelta'
        ];
        
        return $estados[$this->estado] ?? $this->estado;
    }

    /**
     * Obtener la clase CSS para el estado
     */
    public function getClaseEstadoAttribute()
    {
        $clases = [
            self::ESTADO_COMPLETADA => 'badge bg-success',
            self::ESTADO_CANCELADA => 'badge bg-danger',
            self::ESTADO_PARCIALMENTE_DEVUELTA => 'badge bg-warning',
            self::ESTADO_TOTALMENTE_DEVUELTA => 'badge bg-info'
        ];
        
        return $clases[$this->estado] ?? 'badge bg-secondary';
    }

    //
    /**
 * Verifica si la venta está cancelada
 */
public function getEsCanceladaAttribute(): bool
{
    return $this->estado === self::ESTADO_CANCELADA;
}

/**
 * Subtotal visible (0 si está cancelada)
 */
public function getSubtotalVisibleAttribute()
{
    return $this->es_cancelada ? 0 : $this->subtotal;
}

/**
 * IVA visible (0 si está cancelada)
 */
public function getIvaVisibleAttribute()
{
    return $this->es_cancelada ? 0 : $this->iva_calculado;
}

/**
 * ISR visible (0 si está cancelada)
 */
public function getIsrVisibleAttribute()
{
    return $this->es_cancelada ? 0 : $this->isr_calculado;
}

/**
 * Total visible (0 si está cancelada)
 */
public function getTotalVisibleAttribute()
{
    return $this->es_cancelada ? 0 : $this->total;
}

public function cierreNota()
{
    return $this->hasOne(CierreNotaAbono::class, 'venta_id');
}

}
