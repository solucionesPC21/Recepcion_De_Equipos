<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoAbono extends Model
{
    use HasFactory;
    protected $table = 'movimientos_abono';

    protected $fillable = [
        'nota_abono_id',
        'tipo',
        'monto',
        'saldo_anterior',
        'nuevo_saldo',
        'concepto',
        'venta_id',
        'observaciones',
        'user_id'
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'saldo_anterior' => 'decimal:2',
        'nuevo_saldo' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Tipos de movimiento permitidos
     */
    const TIPO_ABONO = 'abono';
    const TIPO_COMPRA = 'compra';
    const TIPO_AJUSTE = 'ajuste';
    const TIPO_CIERRE = 'cierre';

    /**
     * Relación con la nota de abono
     */
    // Relación con NotaAbono
    public function notaAbono()
    {
        return $this->belongsTo(NotaAbono::class, 'nota_abono_id');
    }

    /**
     * Relación con la venta (opcional)
     */
    public function venta()
    {
        return $this->belongsTo(VentaNotaAbono::class, 'venta_id');
    }

    /**
     * Relación con el usuario que realizó el movimiento
     */
       public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
      // Relación con User si existe
    

    /**
     * Scope para movimientos de abono
     */
    public function scopeAbonos($query)
    {
        return $query->where('tipo', self::TIPO_ABONO);
    }

    /**
     * Scope para movimientos de compra
     */
    public function scopeCompras($query)
    {
        return $query->where('tipo', self::TIPO_COMPRA);
    }

    /**
     * Scope para movimientos por fecha
     */
    public function scopePorFecha($query, $fechaInicio, $fechaFin = null)
    {
        $query->whereBetween('created_at', [
            $fechaInicio, 
            $fechaFin ?: $fechaInicio
        ]);
        
        return $query;
    }

    /**
     * Obtener el tipo de movimiento formateado
     */
    public function getTipoFormateadoAttribute()
    {
        $tipos = [
            self::TIPO_ABONO => 'Abono',
            self::TIPO_COMPRA => 'Compra',
            self::TIPO_AJUSTE => 'Ajuste',
            self::TIPO_CIERRE => 'Cierre'
        ];
        
        return $tipos[$this->tipo] ?? $this->tipo;
    }

    /**
     * Obtener el icono según el tipo de movimiento
     */
    public function getIconoTipoAttribute()
    {
        $iconos = [
            self::TIPO_ABONO => 'fas fa-plus-circle text-success',
            self::TIPO_COMPRA => 'fas fa-shopping-cart text-primary',
            self::TIPO_AJUSTE => 'fas fa-adjust text-warning',
            self::TIPO_CIERRE => 'fas fa-lock text-secondary'
        ];
        
        return $iconos[$this->tipo] ?? 'fas fa-exchange-alt';
    }

}
