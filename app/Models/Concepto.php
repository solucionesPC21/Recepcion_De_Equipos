<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Concepto extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = [
        'cantidad',
        'total',
        'id_ticket',
        'id_nombreConcepto'
        // Agrega aquí todos los campos que necesites asignar masivamente
    ];
    
    
    public function ticket(){
        return $this->belongsTo(Ticket::class,'id_ticket');
    }

    public function nombreConcepto(){
        return $this->belongsTo(NombreConcepto::class,'id_nombreConcepto');
    }
   
}
