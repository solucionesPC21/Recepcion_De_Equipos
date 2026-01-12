<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoEquipo extends Model
{
    protected $fillable = [
        'id_cliente', 'id_equipo', 'id_marca', 'modelo', 'ns', 'falla', 'accesorio', 'id_estado', 'fecha', 'hora',
    ];

    
    public $timestamps = false;
    
    use HasFactory;

    public function cliente(){
        return $this->belongsTo(Clientes::class,'id_cliente');
    }

    public function equipo(){
        return $this->belongsTo(Equipo::class,'id_equipo');
    }

    public function marca(){
        return $this->belongsTo(Marca::class,'id_marca');
    }
    
    public function recibo(){
        return $this->belongsTo(Recibo::class,'id_recibo');
    }
}
