<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClienteAbono extends Model
{
    use HasFactory;
    protected $table = 'clientes_abonos';

    protected $fillable = [
        'nombre',
        'correo',
        'telefono',
        'direccion',
        'observaciones',
        'rfc',
        'regimen_id',
        'saldo_global',
    ];

    /**
     * Relación con regimenes
     * Un cliente pertenece a un régimen
     */
    public function regimen()
    {
        return $this->belongsTo(Regimen::class, 'regimen_id');
    }

    public function notasAbono()
    {
        return $this->hasMany(NotaAbono::class, 'cliente_id');
    }

    public function ventas()
    {
        return $this->hasMany(VentaNotaAbono::class, 'cliente_id');
    }

     /**
     * Obtener los responsables asociados al cliente.
     */
    public function responsables(): HasMany
    {
        return $this->hasMany(ResponsableAbono::class, 'cliente_id');
    }


     /**
     * Obtener los responsables activos (que han realizado ventas).
     */
    public function responsablesActivos()
    {
        return $this->responsables()
            ->whereHas('notasAbono')
            ->withCount('notasAbono')
            ->orderBy('notas_abono_count', 'desc');
    }
}
