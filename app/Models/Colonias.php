<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Colonias extends Model
{
    use HasFactory;
    //relacion uno a  muchos
    public function clientes(){
        return $this->hasMany('App\Models\Clientes','id_colonia');
    }
     
}

