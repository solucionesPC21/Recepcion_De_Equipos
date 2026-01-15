<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotaAbono extends Model
{
    use HasFactory;
    
    protected $table = 'notas_abono';

    protected $fillable = [
        'folio',
        'cliente_id',
        'abono_inicial',
        'saldo_actual',
        'subtotal_acumulado',
        'iva_calculado',
        'isr_calculado',
        'total_con_impuestos',
        'estado',
        'fecha_apertura',
        'fecha_cierre',
        'observaciones',
        'estado_cierre'
    ];

    protected $casts = [
        'fecha_apertura' => 'date',
        'fecha_cierre' => 'date',
        'abono_inicial' => 'decimal:2',
        'saldo_actual' => 'decimal:2',
        'subtotal_acumulado' => 'decimal:2',
        'iva_calculado' => 'decimal:2',
        'isr_calculado' => 'decimal:2',
        'total_con_impuestos' => 'decimal:2',
    ];

    /**
     * Relación con el cliente
     */
    public function cliente()
    {
        return $this->belongsTo(ClienteAbono::class, 'cliente_id');
    }

    /**
     * Relación con las ventas
     */
    public function ventas()
    {
        return $this->hasMany(VentaNotaAbono::class, 'nota_abono_id');
    }

    /**
     * Relación con los movimientos
     */
    public function movimientos()
    {
        return $this->hasMany(MovimientoAbono::class, 'nota_abono_id');
    }

    /**
     * Scope para notas activas
     */
    public function scopeActivas($query)
    {
        return $query->where('estado', 'activa');
    }

    /**
     * Generar folio automático
     */
    public static function generarFolio()
    {
        $ultimoFolio = self::orderBy('id', 'desc')->value('folio');
        
        if ($ultimoFolio) {
            $numero = (int) preg_replace('/[^0-9]/', '', $ultimoFolio) + 1;
        } else {
            $numero = 1;
        }
        
        return 'ABONO-' . str_pad($numero, 4, '0', STR_PAD_LEFT);
    }
    public function cierre()
{
    return $this->hasOne(CierreNotaAbono::class, 'nota_abono_id');
}

public function ventaCierre()
{
    return $this->belongsTo(VentaNotaAbono::class, 'venta_cierre_id');
}

/**
 * Verificar si la nota puede ser cerrada
 */
public function puedeSerCerrada()
{
    if ($this->estado_cierre === 'finalizada') {
        return false;
    }
    
    // Verificar si hay saldo positivo (no puede cerrar con deuda)
    return $this->saldo_actual <= 0;
}

/**
 * Cerrar la nota de abono
 */
public function cerrar($ventaId, $tipoPagoId, $datosPago = [])
{
    DB::transaction(function () use ($ventaId, $tipoPagoId, $datosPago) {
        // 1. Obtener la venta
        $venta = VentaNotaAbono::findOrFail($ventaId);
        
        // 2. Crear registro en cierre_nota_abonos
        $cierre = CierreNotaAbono::create([
            'nota_abono_id' => $this->id,
            'venta_id' => $ventaId,
            'tipo_pago_id' => $tipoPagoId,
            'monto_saldo_usado' => $venta->pago_saldo ?? $venta->total,
            'monto_efectivo' => $datosPago['efectivo'] ?? 0,
            'monto_transferencia' => $datosPago['transferencia'] ?? 0,
            'saldo_anterior' => $venta->saldo_antes,
            'saldo_despues' => max(0, $venta->saldo_despues), // No negativo
            'referencia_pago' => $datosPago['referencia'] ?? null,
            'observaciones' => $datosPago['observaciones'] ?? null,
            'cerrado_por' => auth()->id()
        ]);
        
        // 3. Actualizar la venta
        $venta->update(['es_cierre_nota' => true]);
        
        // 4. Actualizar la nota de abono
        $this->update([
            'estado_cierre' => 'finalizada',
            'fecha_cierre' => now(),
            'venta_cierre_id' => $ventaId,
            'saldo_actual' => 0 // Forzar a 0 al cerrar
        ]);
        
        // 5. Registrar movimiento
        MovimientoAbono::create([
            'nota_abono_id' => $this->id,
            'tipo' => 'cierre',
            'monto' => $cierre->monto_total_pagado,
            'saldo_anterior' => $venta->saldo_antes,
            'nuevo_saldo' => 0,
            'concepto' => 'Cierre definitivo de nota de abono',
            'venta_id' => $ventaId,
            'observaciones' => sprintf(
                'Cierre realizado por venta %s. %s: $%s',
                $venta->ticket,
                $cierre->monto_efectivo > 0 ? 'Efectivo' : 'Transferencia',
                number_format($cierre->monto_total_pagado, 2)
            ),
            'user_id' => auth()->id()
        ]);
        
        Log::info('Nota de abono cerrada', [
            'nota_id' => $this->id,
            'venta_id' => $ventaId,
            'cierre_id' => $cierre->id,
            'monto_pagado' => $cierre->monto_total_pagado
        ]);
    });
}
}
