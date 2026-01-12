<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estado extends Model
{
    use HasFactory;

    public function recibo(){
        return $this->hasMany('App\Models\Recibo','id_estado');
    }

    /**
     * Relación con ventas
     */
    public function ventas()
    {
        return $this->hasMany(VentaAbono::class, 'estado_id');
    }
}
