<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        // ... otros campos que ya tengas
        'estado_id',
        
    ];
      
    public function recibo()
    {
        return $this->belongsTo(Recibo::class, 'id_recibo');
    }

    public function tipoPago(){
        return $this->belongsTo(TipoPago::class,'id_tipoPago');
    }

    public function concepto(){
        return $this->hasMany(Concepto::class,'id_ticket');
    }

    public function cliente()
    {
        return $this->belongsTo(Clientes::class, 'id_cliente');
    }
    
}
