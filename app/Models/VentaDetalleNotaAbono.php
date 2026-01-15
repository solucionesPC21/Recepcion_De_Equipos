<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VentaDetalleNotaAbono extends Model
{
    use HasFactory;
      protected $table = 'venta_detallesnotaabono';

    protected $fillable = [
        'venta_id',
        'producto_id',
        'nombre_producto',
        'precio_unitario',
        'cantidad',
        'total',
        'devuelto',
        'cantidad_devuelta',
        'monto_devuelto',
        'fecha_devolucion',
        'motivo_devolucion'
    ];

    protected $casts = [
        'precio_unitario' => 'decimal:2',
        'total' => 'decimal:2',
        'monto_devuelto' => 'decimal:2',
        'cantidad' => 'integer',
        'cantidad_devuelta' => 'integer',
        'devuelto' => 'boolean',
        'fecha_devolucion' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relación con la venta
     */
    public function venta()
    {
        return $this->belongsTo(VentaNotaAbono::class, 'venta_id');
    }

    /**
     * Relación con el producto
     */
    public function producto()
    {
        return $this->belongsTo(NombreConcepto::class, 'producto_id');
    }

    /**
     * Scope para detalles devueltos
     */
    public function scopeDevueltos($query)
    {
        return $query->where('devuelto', true);
    }

    /**
     * Scope para detalles no devueltos
     */
    public function scopeNoDevueltos($query)
    {
        return $query->where('devuelto', false);
    }

    /**
     * Scope para detalles con devolución parcial
     */
    public function scopeConDevolucionParcial($query)
    {
        return $query->where('devuelto', true)
                    ->whereColumn('cantidad_devuelta', '<', 'cantidad');
    }

    /**
     * Calcular el subtotal del detalle
     */
    public function getSubtotalAttribute()
    {
        return $this->precio_unitario * $this->cantidad;
    }

    /**
     * Verificar si el detalle está completamente devuelto
     */
    public function getCompletamenteDevueltoAttribute()
    {
        return $this->devuelto && $this->cantidad_devuelta == $this->cantidad;
    }

    /**
     * Verificar si el detalle está parcialmente devuelto
     */
    public function getParcialmenteDevueltoAttribute()
    {
        return $this->devuelto && $this->cantidad_devuelta < $this->cantidad;
    }

    /**
     * Obtener la cantidad neta vendida (cantidad - cantidad_devuelta)
     */
    public function getCantidadNetaAttribute()
    {
        return $this->cantidad - $this->cantidad_devuelta;
    }

    /**
     * Obtener el monto neto (total - monto_devuelto)
     */
    public function getMontoNetoAttribute()
    {
        return $this->total - $this->monto_devuelto;
    }

    /**
     * Procesar devolución del detalle
     */
    public function procesarDevolucion($cantidadADevolver, $motivo = null)
    {
        if ($cantidadADevolver > ($this->cantidad - $this->cantidad_devuelta)) {
            throw new \Exception('La cantidad a devolver excede la cantidad disponible');
        }

        $this->update([
            'devuelto' => true,
            'cantidad_devuelta' => $this->cantidad_devuelta + $cantidadADevolver,
            'monto_devuelto' => $this->precio_unitario * ($this->cantidad_devuelta + $cantidadADevolver),
            'fecha_devolucion' => now(),
            'motivo_devolucion' => $motivo
        ]);

        // Actualizar stock del producto si existe
        if ($this->producto) {
            $this->producto->increment('cantidad', $cantidadADevolver);
        }

        return $this;
    }

    /**
     * Obtener el estado de devolución formateado
     */
    public function getEstadoDevolucionAttribute()
    {
        if (!$this->devuelto) {
            return 'No devuelto';
        }

        if ($this->completamente_devuelto) {
            return 'Completamente devuelto';
        }

        if ($this->parcialmente_devuelto) {
            return 'Parcialmente devuelto';
        }

        return 'Estado desconocido';
    }

    /**
     * Obtener la clase CSS para el estado de devolución
     */
    public function getClaseEstadoDevolucionAttribute()
    {
        if (!$this->devuelto) {
            return 'badge bg-success';
        }

        if ($this->completamente_devuelto) {
            return 'badge bg-danger';
        }

        if ($this->parcialmente_devuelto) {
            return 'badge bg-warning';
        }

        return 'badge bg-secondary';
    }
}
