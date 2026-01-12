<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clientes extends Model
{
    protected $fillable = [
        'nombre', 
        'telefono',
        'telefono2',
        'rfc',
        'id_colonia',
    ];

    use HasFactory;

    //relacion uno a muchos inversa

    public function colonia(){
        return $this->belongsTo(Colonias::class,'id_colonia');
    }

    public function tipoEquipo(){
        return $this->hasMany('App\Models\TipoEquipo','id_cliente');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'id_cliente');
    }

     /**
     * Relación con ventas a crédito
     */
    public function ventas()
    {
        return $this->hasMany(VentaAbono::class, 'id_cliente');
    }
} 
