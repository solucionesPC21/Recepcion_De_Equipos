<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recibo extends Model
{
    use HasFactory;

    public function tipoEquipo(){
        return $this->hasMany(TipoEquipo::class,'id_recibo');
    }

    public function estado(){
        return $this->belongsTo(Estado::class,'id_estado');
    }

    public function ticket()
    {
        return $this->hasOne(Ticket::class, 'id_recibo');
    }

    public function nota()
    {
        return $this->belongsTo(Nota::class, 'id_nota');
    }

    public function archivos()
    {
        return $this->hasMany(ReciboArchivo::class);
    }

}
