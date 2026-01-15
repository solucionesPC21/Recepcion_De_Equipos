<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // ← AÑADE ESTA IMPORTACIÓN

class DevolucionVenta extends Model
{
     use HasFactory;
    
    use HasFactory;

    protected $table = 'devoluciones_ventas';
    
    protected $fillable = [
        'venta_id',
        'nota_abono_id',
        'cliente_id',
        'folio_devolucion',
        'motivo',
        'observaciones',
        'subtotal',
        'iva',
        'isr',
        'total',
        'estado',
        'detalles',
        'user_id'
    ];
    
    protected $casts = [
        'subtotal' => 'decimal:2',
        'iva' => 'decimal:2',
        'isr' => 'decimal:2',
        'total' => 'decimal:2',
        'detalles' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    // Constantes para los motivos
    const MOTIVO_PRODUCTO_DEFECTUOSO = 'producto_defectuoso';
    const MOTIVO_NO_CORRESPONDE = 'no_corresponde_pedido';
    const MOTIVO_CLIENTE_ARREPENTIDO = 'cliente_arrepentido';
    const MOTIVO_ERROR_CANTIDAD = 'error_cantidad';
    const MOTIVO_CAMBIO_PRODUCTO = 'cambio_producto';
    const MOTIVO_OTRO = 'otro';
    
    // Constantes para estados
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_COMPLETADA = 'completada';
    const ESTADO_CANCELADA = 'cancelada';
    
    // Boot events
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->folio_devolucion)) {
                $model->folio_devolucion = self::generarFolio();
            }
        });
    }
    
    /**
     * Obtener todos los motivos disponibles
     */
    public static function getMotivos()
    {
        return [
            self::MOTIVO_PRODUCTO_DEFECTUOSO => 'Producto Defectuoso',
            self::MOTIVO_NO_CORRESPONDE => 'No Corresponde al Pedido',
            self::MOTIVO_CLIENTE_ARREPENTIDO => 'Cliente Arrepentido',
            self::MOTIVO_ERROR_CANTIDAD => 'Error en Cantidad',
            self::MOTIVO_CAMBIO_PRODUCTO => 'Cambio por Otro Producto',
            self::MOTIVO_OTRO => 'Otro'
        ];
    }
    
    /**
     * Obtener nombre legible del motivo
     */
    public function getMotivoTextoAttribute()
    {
        $motivos = self::getMotivos();
        return $motivos[$this->motivo] ?? $this->motivo;
    }
    
    /**
     * Generar folio automáticamente
     */
    public static function generarFolio()
    {
        $ultimaDevolucion = self::latest()->first();
        $numero = $ultimaDevolucion ? (int) str_replace('DEV-', '', $ultimaDevolucion->folio_devolucion) + 1 : 1;
        
        return 'DEV-' . str_pad($numero, 6, '0', STR_PAD_LEFT);
    }
    
    // RELACIONES CORREGIDAS
    
    /**
     * Relación con la venta
     */
    public function venta(): BelongsTo
    {
        return $this->belongsTo(VentaNotaAbono::class, 'venta_id');
    }
    
    /**
     * Relación con la nota de abono
     */
    public function notaAbono(): BelongsTo
    {
        return $this->belongsTo(NotaAbono::class, 'nota_abono_id');
    }
    
    /**
     * Relación con el cliente
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(ClienteAbono::class, 'cliente_id');
    }
    
    /**
     * Relación con el usuario
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    // Métodos útiles
    public function getNombreClienteAttribute()
    {
        return $this->cliente->nombre ?? null;
    }
    
    public function getTicketVentaAttribute()
    {
        return $this->venta->ticket ?? null;
    }
    
    public function getTotalFormateadoAttribute()
    {
        return '$' . number_format($this->total, 2);
    }

}
