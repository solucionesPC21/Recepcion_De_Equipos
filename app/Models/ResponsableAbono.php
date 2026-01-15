<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo; 


class ResponsableAbono extends Model
{
    use HasFactory;

    protected $table = 'responsables';

    protected $fillable = [
        'nombre',
        'cliente_id',
    ];
     protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Obtener el cliente al que pertenece el responsable.
     */
     public function cliente(): BelongsTo // ← CORREGIDO
    {
        return $this->belongsTo(ClienteAbono::class, 'cliente_id');
    }

    /**
     * Obtener las ventas (tickets) asociadas a este responsable.
     */
    public function ventasNotaAbono(): HasMany
    {
        return $this->hasMany(VentaNotaAbono::class, 'responsable_id');
    }

    /**
     * Scope para buscar responsables por nombre.
     */
    public function scopeWhereNombre($query, $nombre)
    {
        return $query->where('nombre', 'LIKE', "%{$nombre}%");
    }

    /**
     * Scope para filtrar por cliente.
     */
    public function scopeWhereCliente($query, $clienteId)
    {
        return $query->where('cliente_id', $clienteId);
    }

    /**
     * Obtener el número de ventas realizadas por este responsable.
     */
    public function getTotalVentasAttribute(): int
    {
        return $this->ventasNotaAbono()->count();
    }

    
}
