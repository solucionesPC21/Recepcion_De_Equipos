<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CierreNotaAbono extends Model
{
    use HasFactory;
    protected $table = 'cierre_nota_abonos';
    const UPDATED_AT = null;

    protected $fillable = [
        'nota_abono_id',
        'venta_id',
        'tipo_pago_id',
        'monto_saldo_usado',
        'monto_efectivo',
        'monto_transferencia',
        'saldo_anterior',
        'saldo_despues',
        'referencia_pago',
        'observaciones',
        'cerrado_por'
    ];
    
    protected $casts = [
        'monto_saldo_usado' => 'decimal:2',
        'monto_efectivo' => 'decimal:2',
        'monto_transferencia' => 'decimal:2',
        'saldo_anterior' => 'decimal:2',
        'saldo_despues' => 'decimal:2',
        'created_at' => 'datetime'
    ];
    
    /**
     * Relación con la nota de abono
     */
    public function notaAbono()
    {
        return $this->belongsTo(NotaAbono::class, 'nota_abono_id');
    }
    
    /**
     * Relación con la venta que generó el cierre
     */
    public function venta()
    {
        return $this->belongsTo(VentaNotaAbono::class, 'venta_id');
    }
    
    /**
     * Relación con el tipo de pago
     */
    public function tipoPago()
    {
        return $this->belongsTo(TipoPago::class, 'tipo_pago_id');
    }
    
    /**
     * Relación con el usuario que cerró
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'cerrado_por');
    }
    
    /**
     * Calcular el monto total pagado
     */
    public function getMontoTotalPagadoAttribute()
    {
        return $this->monto_efectivo + $this->monto_transferencia;
    }
    
    /**
     * Verificar si el cierre fue con saldo suficiente
     */
    public function getFuePagoCompletoAttribute()
    {
        return $this->saldo_despues <= 0;
    }
}
