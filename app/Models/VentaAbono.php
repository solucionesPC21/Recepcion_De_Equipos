<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VentaAbono extends Model
{
    use HasFactory;
    
    protected $table = 'venta_abono';
    public $timestamps = false; 
    
    protected $fillable = [
        'id_cliente',
        'total',
        'saldo_restante',
        'usuario',
        'estado_id',
        'fecha_venta'
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'saldo_restante' => 'decimal:2',
        'fecha_venta' => 'datetime'
    ];

    /**
     * Relación con el cliente
     */
    public function cliente()
    {
        return $this->belongsTo(Clientes::class, 'id_cliente');
    }

    /**
     * Relación con el estado
     */
    public function estado()
    {
        return $this->belongsTo(Estado::class, 'estado_id');
    }

    /**
     * Relación con los detalles de la venta (productos/servicios)
     */
    public function detalles()
    {
        return $this->hasMany(VentaAbonoDetalle::class, 'venta_abono_id');
    }

    /**
     * Relación con los abonos/pagos
     */
    public function abonos()
    {
        return $this->hasMany(Abono::class, 'venta_abono_id');
    }

    /**
     * Verifica si la venta está completamente pagada
     */
    public function getPagadaAttribute()
    {
        return $this->saldo_restante <= 0;
    }

    /**
     * Calcula el total de abonos recibidos
     */
    public function getTotalAbonadoAttribute()
    {
        return $this->abonos->sum('monto');
    }
}
