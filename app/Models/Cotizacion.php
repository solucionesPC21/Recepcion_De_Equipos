<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    use HasFactory;

    protected $table = 'cotizaciones';
    public $timestamps = false;
    
    protected $fillable = [
        'nombre_cliente',
        'pdf_cliente',
        'pdf_interno',
         'datos_json',           // NUEVO
        'tipo_cliente',         // NUEVO
        'total',               // NUEVO
        'valido_hasta',        // NUEVO
        'direccion',           // NUEVO
        'telefono',            // NUEVO
        'descuento_porcentaje', // NUEVO
        'fecha_creacion',
        'created_at',          // NUEVO
        'updated_at'           // NUEVO
    ];

    protected $casts = [
        'fecha_creacion' => 'datetime',
        'valido_hasta' => 'date',
        'datos_json' => 'array',  
        'total' => 'decimal:2',
        'descuento_porcentaje' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

     // ✅ Método para obtener datos decodificados
    public function getDatosAttribute()
    {
        return $this->datos_json ? json_decode($this->datos_json, true) : [];
    }

}
