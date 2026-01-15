<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoPago extends Model
{
    use HasFactory;

    public function ticket(){
        return $this->hasMany('App\Models\Ticket','id_tipoPago');
    }

      /**
     * Relación con los abonos
     */
    public function abonos()
    {
        return $this->hasMany(Abono::class, 'tipo_pago_id');
    }
}
