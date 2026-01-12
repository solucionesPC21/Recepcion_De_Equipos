<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipo extends Model
{
    use HasFactory;

    public function tipoEquipo(){
        return $this->hasMany('App\Models\TipoEquipo','id_equipo');
    }
}
