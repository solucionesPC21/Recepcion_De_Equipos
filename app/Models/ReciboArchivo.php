<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReciboArchivo extends Model
{
    use HasFactory;
     protected $table = 'recibo_archivos';

    protected $fillable = [
        'recibo_id',
        'nombre',
        'tipo',
        'archivo',
    ];

    /**
     * Relación con recibo.
     * Un archivo pertenece a un recibo.
     */
    public function recibo()
    {
        return $this->belongsTo(Recibo::class);
    }
}
