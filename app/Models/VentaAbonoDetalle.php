<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VentaAbonoDetalle extends Model
{
    use HasFactory;

    protected $table = 'venta_abono_detalle';
    
    public $timestamps = false;
    
    protected $fillable = [
        'venta_abono_id',
        'nombreconcepto_id',
        'cantidad',
        'precio_unitario',
        'subtotal'
    ];

    protected $casts = [
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2'
    ];

    /**
     * Relación con la venta principal
     */
    public function venta()
    {
        return $this->belongsTo(VentaAbono::class, 'venta_abono_id');
    }

    /**
     * Relación con el concepto (producto/servicio)
     */
    public function concepto()
    {
        return $this->belongsTo(NombreConcepto::class, 'nombreconcepto_id');
    }

    /**
     * Calcula el subtotal automáticamente antes de guardar
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->subtotal = $model->cantidad * $model->precio_unitario;
        });
    }
}
