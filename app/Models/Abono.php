<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Abono extends Model
{
    use HasFactory;

    protected $table = 'abonos';
    public $timestamps = false;
    // En tu modelo Abono.php
    protected $appends = ['tiene_pdf'];
    
    protected $fillable = [
        'venta_abono_id',
        'monto',
        'fecha_abono',
        'tipo_pago_id',
        'pdf_abonos'
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_abono' => 'datetime'
    ];

    /**
     * Relación con la venta a crédito
     */
    public function venta()
    {
        return $this->belongsTo(VentaAbono::class, 'venta_abono_id');
    }

    /**
     * Formatea la fecha legiblemente
     */
    public function getFechaFormateadaAttribute()
    {
        return $this->fecha_abono->format('d/m/Y H:i');
    }

     /**
     * Relación con el tipo de pago
     */
    public function tipoPago()
    {
        return $this->belongsTo(TipoPago::class, 'tipo_pago_id');
    }

      /**
     * Accesor para el método de pago
     */
    public function getMetodoPagoAttribute()
    {
        return optional($this->tipoPago)->nombre ?? 'No especificado';
    }

    public function getTienePdfAttribute()
    {
        return !empty($this->pdf_abonos);
    }
}
