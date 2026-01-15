<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Regimen extends Model
{
    use HasFactory;
    public $timestamps = false; // Esto evita que busque las columnas created_at y updated_at
    
    protected $table = 'regimenes';

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'nombre',
        'iva',
        'isr',
    ];

    /**
     * Relación con clientes_abonos
     * Un régimen puede tener muchos clientes
     */
    public function clientesAbonos()
    {
        return $this->hasMany(ClienteAbono::class, 'regimen_id');
    }
}
