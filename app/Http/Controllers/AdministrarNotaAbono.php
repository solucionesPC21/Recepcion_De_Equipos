<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NotaAbono;
use App\Models\ClienteAbono;
use App\Models\VentaNotaAbono;
use App\Models\VentaDetalleNotaAbono;
use App\Models\MovimientoAbono;
use App\Models\NombreConcepto;
use App\Models\ResponsableAbono;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\CapabilityProfile;
use App\Models\DevolucionVenta;
use App\Models\CierreNotaAbono;
use App\Models\TipoPago;
use App\Models\Impresoras;
use PDF;


class AdministrarNotaAbono extends Controller
{
    public function administrar($id)
{
    try {
        // Cargar la nota de abono con relaciones
        $notaAbono = NotaAbono::with(['cliente', 'cliente.regimen'])->find($id);
        $tipoPagos = TipoPago::all(); // ← Cambiar a singular
        
        if (!$notaAbono) {
            return redirect()->back()->with('error', 'Nota de abono no encontrada');
        }

         // Calcular el total de abonos (movimientos tipo 'abono')
        $totalAbonos = MovimientoAbono::where('nota_abono_id', $id)
            ->where('tipo', 'abono')
            ->sum('monto');


        // Cargar ventas relacionadas
        $ventas = VentaNotaAbono::where('nota_abono_id', $id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('administrarNotaAbono.administrarNotaAbono', compact(
            'notaAbono', 
            'ventas',
            'tipoPagos', // ← Ahora coincide
            'totalAbonos' // ← Agregar esta variable
        ));

    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Error al cargar la vista de administración');
    }
}
    /**
     * Registrar una nueva venta en la nota de abono
     */

public function registrarVenta(Request $request, $notaAbonoId)
    {
        try {
            DB::beginTransaction();
            
            // ==============================================
            // 1. VALIDACIÓN COMPLETA
            // ==============================================
            $request->validate([
            'responsable_id' => 'required|integer|exists:responsables,id',
            'responsable_nombre' => 'sometimes|string|max:255',
            'productos' => 'required|array|min:1',
          
            'productos.*.nombre' => 'required|string|max:500',
            'productos.*.precio' => 'required|numeric|min:0',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.subtotal' => 'sometimes|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'iva_calculado' => 'required|numeric|min:0',
            'isr_calculado' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'saldo_antes' => 'required|numeric|min:0',
            'saldo_despues' => 'required|numeric',
            'total_items' => 'required|integer|min:1',
            'cierre_datos' => 'sometimes|nullable|array', // ← Agregar nullable
        ]);
            
            // ==============================================
            // 2. OBTENER DATOS BÁSICOS
            // ==============================================
            $notaAbono = NotaAbono::with(['cliente', 'cliente.regimen'])->findOrFail($notaAbonoId);
            $cliente = $notaAbono->cliente;
            
            // ==============================================
            // 3. MANEJAR PAGO MIXTO
            // ==============================================
            $pagoMixto = $request->input('pago_mixto', null);
            $cierreDatos = $request->input('cierre_datos', null);
            
            $montoADescontar = $request->total;
            $pagoEfectivo = 0;
            $usaPagoMixto = false;
            $cierreNota = false;
            
            if ($pagoMixto && isset($pagoMixto['habilitado']) && $pagoMixto['habilitado']) {
                $usaPagoMixto = true;
                $montoADescontar = $pagoMixto['pago_saldo'] ?? 0;
                $pagoEfectivo = $pagoMixto['pago_efectivo'] ?? 0;
                
                
                // Validar que la suma sea igual al total (con margen de error de 0.01)
                $sumaPagos = $montoADescontar + $pagoEfectivo;
                if (abs($sumaPagos - $request->total) > 0.01) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => sprintf(
                            'La suma del saldo ($%s) y efectivo ($%s) = $%s no coincide con el total $%s',
                            number_format($montoADescontar, 2),
                            number_format($pagoEfectivo, 2),
                            number_format($sumaPagos, 2),
                            number_format($request->total, 2)
                        )
                    ], 400);
                }
                
                // Si hay pago en efectivo, verificar si debe cerrar la nota
                if ($pagoEfectivo > 0 && $request->saldo_despues <= 0) {
                    $cierreNota = true;
                
                }
            }
            
            // ==============================================
            // 4. VALIDAR SALDO SUFICIENTE
            // ==============================================
            if ($notaAbono->saldo_actual < $montoADescontar) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => sprintf(
                        'Saldo insuficiente. Saldo actual: $%s, Monto a descontar: $%s',
                        number_format($notaAbono->saldo_actual, 2),
                        number_format($montoADescontar, 2)
                    )
                ], 400);
            }
            
            // ==============================================
            // 5. GENERAR TICKET
            // ==============================================
            $ticket = 'TICKET-' . str_pad(VentaNotaAbono::max('id') + 1, 6, '0', STR_PAD_LEFT);
            
            // ==============================================
            // 6. CREAR REGISTRO DE VENTA
            // ==============================================
            $venta = VentaNotaAbono::create([
                'ticket' => $ticket,
                'nota_abono_id' => $notaAbono->id,
                'cliente_id' => $cliente->id,
                'responsable_id' => $request->responsable_id,
                'subtotal' => $request->subtotal,
                'iva_calculado' => $request->iva_calculado,
                'isr_calculado' => $request->isr_calculado,
                'total' => $request->total,
                'saldo_antes' => $request->saldo_antes,
                'saldo_despues' => $request->saldo_despues,
                'total_items' => $request->total_items,
                'estado' => 'completada',
                'pago_mixto' => $usaPagoMixto,
                'pago_saldo' => $montoADescontar,
                'pago_efectivo' => $pagoEfectivo,
                'observaciones_pago' => $pagoMixto['observaciones'] ?? null,
                'es_cierre_nota' => $cierreNota,
                'user_id' => auth()->id()
            ]);
            
            
            // ==============================================
            // 7. PROCESAR DETALLES DE VENTA Y VALIDAR STOCK
            // ==============================================
            $productosSinStock = [];
            
            foreach ($request->productos as $productoVenta) {
                $producto = NombreConcepto::find($productoVenta['id']);
                
                if (!$producto) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Producto no encontrado: ID ' . $productoVenta['id']
                    ], 400);
                }
                
                // Validar stock
                if ($producto->cantidad < $productoVenta['cantidad']) {
                    $productosSinStock[] = [
                        'nombre' => $producto->nombre,
                        'solicitado' => $productoVenta['cantidad'],
                        'disponible' => $producto->cantidad
                    ];
                }
            }
            
            // Si hay productos sin stock, cancelar transacción
            if (!empty($productosSinStock)) {
                DB::rollBack();
                $mensaje = 'Stock insuficiente para los siguientes productos:<br>';
                foreach ($productosSinStock as $prod) {
                    $mensaje .= "- {$prod['nombre']}: Solicitado {$prod['solicitado']}, Disponible {$prod['disponible']}<br>";
                }
                
                return response()->json([
                    'success' => false,
                    'message' => $mensaje
                ], 400);
            }
            
            // Registrar detalles y actualizar stock
            foreach ($request->productos as $productoVenta) {
                $producto = NombreConcepto::find($productoVenta['id']);
                
                // Calcular valores unitarios según régimen del cliente
                $precioConIVA = $productoVenta['precio'];
                $tasaIVA = $cliente->regimen->iva ?? 0;
                
                // Calcular precio sin IVA
                $precioSinIVA = $tasaIVA > 0 
                    ? $precioConIVA / (1 + ($tasaIVA / 100))
                    : $precioConIVA;
                
                // Calcular IVA e ISR unitarios
                $ivaUnitario = $precioConIVA - $precioSinIVA;
                $tasaISR = $cliente->regimen->isr ?? 0;
                $isrUnitario = $tasaISR > 0 ? $precioSinIVA * ($tasaISR / 100) : 0;
                
                VentaDetalleNotaAbono::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $producto->id,
                    'nombre_producto' => $productoVenta['nombre'],
                    'precio_unitario' => $precioConIVA,
                    'precio_sin_iva' => $precioSinIVA,
                    'iva_unitario' => $ivaUnitario,
                    'isr_unitario' => $isrUnitario,
                    'cantidad' => $productoVenta['cantidad'],
                    'subtotal' => $precioSinIVA * $productoVenta['cantidad'],
                    'iva' => $ivaUnitario * $productoVenta['cantidad'],
                    'isr' => $isrUnitario * $productoVenta['cantidad'],
                    'total' => $precioConIVA * $productoVenta['cantidad']
                ]);
                
                // Actualizar stock
                $producto->decrement('cantidad', $productoVenta['cantidad']);
            
            }
            
            // ==============================================
            // 8. ACTUALIZAR SALDOS (SOLO LA PARTE DEL SALDO)
            // ==============================================
            $nuevoSaldo = $notaAbono->saldo_actual - $montoADescontar;
            
            $notaAbono->update([
                'saldo_actual' => $nuevoSaldo,
                'subtotal_acumulado' => $notaAbono->subtotal_acumulado + $request->subtotal,
                'iva_calculado' => $notaAbono->iva_calculado + $request->iva_calculado,
                'isr_calculado' => $notaAbono->isr_calculado + $request->isr_calculado,
                'total_con_impuestos' => $notaAbono->total_con_impuestos + $request->total,
                'ultima_venta' => now()
            ]);
            
            // Actualizar saldo global del cliente
            $cliente->update([
                'saldo_global' => $cliente->saldo_global - $montoADescontar
            ]);
            
            // ==============================================
            // 9. REGISTRAR MOVIMIENTO
            // ==============================================
            $concepto = 'Venta - Ticket: ' . $ticket;
            $observaciones = 'Responsable: ' . ($request->responsable_nombre ?: 'No asignado');
            
            if ($usaPagoMixto) {
                $concepto .= ' (Pago Mixto)';
                $observaciones .= ' | Pago mixto: Saldo $' . number_format($montoADescontar, 2) . 
                                 ', Efectivo $' . number_format($pagoEfectivo, 2);
                
                if (!empty($pagoMixto['observaciones'])) {
                    $observaciones .= ' | ' . $pagoMixto['observaciones'];
                }
            }
            
            MovimientoAbono::create([
                'nota_abono_id' => $notaAbono->id,
                'tipo' => 'compra',
                'monto' => $request->total,
                'saldo_anterior' => $request->saldo_antes,
                'nuevo_saldo' => $nuevoSaldo,
                'concepto' => $concepto,
                'venta_id' => $venta->id,
                'observaciones' => $observaciones,
                'user_id' => auth()->id()
            ]);
            
            // ==============================================
            // 10. PROCESAR CIERRE DE NOTA SI CORRESPONDE
            // ==============================================
              if ($cierreNota && $request->has('cierre_datos')) {
                    // Pasar el array completo, no validar aquí
                    $this->procesarCierreNota($notaAbono, $venta, $request->input('cierre_datos'), $pagoMixto);
                }
                    
            // ==============================================
            // 11. GENERAR PDF (OPCIONAL - NO BLOQUEANTE)
            // ==============================================
            try {
                if (method_exists($this, 'generarPDFDesdeVista')) {
                    $pdfBlob = $this->generarPDFDesdeVista($venta->id);
                    
                    $venta->update([
                        'ticket_pdf' => $pdfBlob
                    ]);
                    
                    
                }
            } catch (\Exception $e) {
                Log::warning('Error generando PDF (no crítico): ' . $e->getMessage());
                // No fallar la transacción si el PDF falla
            }
            
            // ==============================================
            // 12. IMPRIMIR TICKET TÉRMICO (OPCIONAL)
            // ==============================================
            try {
                if (method_exists($this, 'imprimirTicketTermica')) {
                    // Imprimir original y copia
                    for ($i = 1; $i <= 2; $i++) {
                        $this->imprimirTicketTermica($venta->id);
                       
                        
                        // Pequeña pausa entre impresiones
                        if ($i < 2) {
                            usleep(500000); // 0.5 segundos
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Error imprimiendo ticket (no crítico): ' . $e->getMessage());
                // No fallar la transacción si la impresión falla
            }
            
            DB::commit();
            
            // ==============================================
            // 13. RETORNAR RESPUESTA
            // ==============================================
            return response()->json([
                'success' => true,
                'message' => 'Venta registrada exitosamente' . ($cierreNota ? ' y nota cerrada' : ''),
                'venta_id' => $venta->id,
                'ticket' => $venta->ticket,
                'saldo_actual' => $cierreNota ? 0 : $nuevoSaldo,
                'pago_mixto_aplicado' => $usaPagoMixto,
                'nota_cerrada' => $cierreNota,
                'detalles' => [
                    'subtotal' => $request->subtotal,
                    'iva' => $request->iva_calculado,
                    'isr' => $request->isr_calculado,
                    'total' => $request->total,
                    'productos_vendidos' => count($request->productos),
                    'total_items' => $request->total_items
                ]
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('Error de validación en registrarVenta: ' . json_encode($e->errors()));
            
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
                'received_data' => $request->all()
            ], 422);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en registrarVenta: ' . $e->getMessage() . 
                      ' - Linea: ' . $e->getLine() . 
                      ' - Archivo: ' . $e->getFile());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }
//
/*
private function generarTicketTxt($ventaId)
{
    try {
        // Cargar datos completos de la venta
        $venta = VentaNotaAbono::with([
            'cliente.regimen',
            'notaAbono',
            'responsable',
            'detalles',
            'detalles.producto'
        ])->findOrFail($ventaId);
        
        // Obtener tasas
        $tasaIva = $venta->cliente->regimen->iva ?? 0;
        $tasaIsr = $venta->cliente->regimen->isr ?? 0;
        $esPersonaMoral = ($tasaIsr > 0);
        
        // Calcular precios para impresión térmica
        $detallesConImpuestos = $venta->detalles->map(function($detalle) use ($tasaIva, $tasaIsr, $esPersonaMoral) {
            $precioConIVA = $detalle->precio_unitario;
            $precioBase = $tasaIva > 0 ? $precioConIVA / (1 + ($tasaIva/100)) : $precioConIVA;

            
            // CONCATENAR MARCA Y MODELO - USANDO LA RELACIÓN 'producto'
            $nombreCompleto = $detalle->nombre_producto;
            
            // Verificar si existe la relación 'producto' y tiene marca/modelo
            if ($detalle->producto) {
                $producto = $detalle->producto;
                
                // Agregar marca si existe y no está vacía
                if (!empty(trim($producto->marca ?? ''))) {
                    $nombreCompleto .= ' ' . $producto->marca;
                }
                
                // Agregar modelo si existe y no está vacío
                if (!empty(trim($producto->modelo ?? ''))) {
                    $nombreCompleto .= ' ' . $producto->modelo;
                }
            }
            
            // Limpiar espacios extras
            $nombreCompleto = trim($nombreCompleto);
            
            // Si por alguna razón queda vacío, usar el nombre del producto
            if (empty($nombreCompleto)) {
                $nombreCompleto = $detalle->nombre_producto;
            }
            
            
            if ($esPersonaMoral) {
                $ivaUnitario = $precioBase * ($tasaIva / 100);
                $isrUnitario = $precioBase * ($tasaIsr / 100);
                $precioFinal = $precioBase + $ivaUnitario - $isrUnitario;
                
                return [
                    'nombre' => $nombreCompleto,
                    'cantidad' => $detalle->cantidad,
                    'precio_final' => $precioFinal,
                    'total' => $precioFinal * $detalle->cantidad,
                    'precio_base' => $precioBase
                ];
            } else {
                $ivaUnitario = $precioBase * ($tasaIva / 100);
                $precioFinal = $precioBase + $ivaUnitario;
                
                return [
                    'nombre' => $nombreCompleto,
                    'cantidad' => $detalle->cantidad,
                    'precio_final' => $precioFinal,
                    'total' => $precioFinal * $detalle->cantidad,
                    'precio_base' => $precioBase
                ];
            }
        });
        
        // ==============================================
        // GENERAR CONTENIDO PARA TXT
        // ==============================================
        $ticketTxt = "";
        
        // Función auxiliar para agregar líneas
        $agregar = function($texto) use (&$ticketTxt) {
            $ticketTxt .= $texto . "\n";
        };
        
        $agregar(str_pad("", 40, " ", STR_PAD_BOTH) . "Soluciones PC");
        $agregar(str_pad("", 40, " ", STR_PAD_BOTH) . "RFC: ZARE881013I12");
        $agregar(str_pad("", 40, " ", STR_PAD_BOTH) . "Tel: 6161362976");
        $agregar(str_pad("", 40, " ", STR_PAD_BOTH) . "BLVD ADOLFO LOPEZ MATEOS 110");
        $agregar(str_pad("", 40, " ", STR_PAD_BOTH) . "EJIDO NUEVO MEXICALI, SAN QUINTÍN B.C");
        
        $agregar("");
        $agregar(str_repeat("=", 48));
        $agregar(str_pad("TICKET DE VENTA", 48, " ", STR_PAD_BOTH));
        $agregar(str_pad($venta->ticket, 48, " ", STR_PAD_BOTH));
        $agregar(str_repeat("=", 48));
        $agregar("");
        
        // FECHA
        $agregar("Fecha: " . $venta->created_at->format('d/m/Y H:i'));
        
        // CLIENTE
        $agregar("Cliente: " . strtoupper($venta->cliente->nombre));
        $agregar("Nota: " . $venta->notaAbono->folio);
        
        if ($venta->responsable) {
            $agregar("Responsable: " . $venta->responsable->nombre);
        }
        
        $agregar("");
        $agregar(str_repeat("-", 48));
        
        // CABECERA DE PRODUCTOS
        $agregar(
            str_pad("CANT", 4) . " " . 
            str_pad("DESCRIPCIÓN", 30) . " " . 
            str_pad("TOTAL", 10, " ", STR_PAD_LEFT)
        );
        $agregar(str_repeat("-", 48));
        
        // DETALLE DE PRODUCTOS CON MARCA Y MODELO
        foreach ($detallesConImpuestos as $detalle) {
            $cantidad = str_pad($detalle['cantidad'], 4);
            
            // Usar el nombre completo con marca y modelo
            $nombreCompleto = $detalle['nombre'];
            
            // TRUNCAR PARA LA PRIMERA LÍNEA
            $nombreTruncado = substr($nombreCompleto, 0, 30);
            $nombreTruncado = str_pad($nombreTruncado, 30);
            
            $total = str_pad('$' . number_format($detalle['total'], 2), 10, " ", STR_PAD_LEFT);
            
            // Primera línea
            $agregar("$cantidad $nombreTruncado $total");
            
            // Si el nombre completo es más largo, imprimir el resto
            $longitudNombre = strlen($nombreCompleto);
            if ($longitudNombre > 30) {
                $inicio = 30;
                while ($inicio < $longitudNombre) {
                    $segmento = substr($nombreCompleto, $inicio, 30);
                    if (!empty(trim($segmento))) {
                        $agregar(str_pad("", 5) . $segmento);
                    }
                    $inicio += 30;
                }
            }
            
            // Precio unitario con impuestos
            $precioUnit = '$' . number_format($detalle['precio_final'], 2) . " c/u";
            $agregar(str_pad("", 5) . $precioUnit);
        }
        
        $agregar(str_repeat("-", 48));
        $agregar("");
        
       // RESUMEN DE IMPUESTOS - ALINEADO A LA DERECHA
        $agregar(str_pad(str_repeat("-", 32), 48, " ", STR_PAD_LEFT));
        $agregar(str_pad("Subtotal: $" . number_format($venta->subtotal, 2), 48, " ", STR_PAD_LEFT));
        $agregar(str_pad("IVA {$tasaIva}%: $" . number_format($venta->iva_calculado, 2), 48, " ", STR_PAD_LEFT));

        if ($esPersonaMoral && $venta->isr_calculado > 0) {
            $agregar(str_pad("ISR {$tasaIsr}%: -$" . number_format($venta->isr_calculado, 2), 48, " ", STR_PAD_LEFT));
        }

        $agregar(str_pad(str_repeat("-", 32), 48, " ", STR_PAD_LEFT));
        $agregar(str_pad("TOTAL: $" . number_format($venta->total, 2), 48, " ", STR_PAD_LEFT));

        // CONTROL DE SALDOS - ALINEADO A LA DERECHA
        $agregar("");
        $agregar(str_pad("CONTROL DE SALDO", 48, " ", STR_PAD_LEFT));
        $agregar(str_pad(str_repeat("-", 48), 48, " ", STR_PAD_LEFT));

        $agregar(str_pad("Saldo anterior:  $" . number_format($venta->saldo_antes, 2), 48, " ", STR_PAD_LEFT));
        $agregar(str_pad("- Compra:  -$" . number_format($venta->total, 2), 48, " ", STR_PAD_LEFT));
        $agregar(str_pad("Saldo actual:  $" . number_format($venta->notaAbono->saldo_actual, 2), 48, " ", STR_PAD_LEFT));
                
        // PIE DE TICKET
        $agregar(str_pad("¡GRACIAS POR SU COMPRA!", 48, " ", STR_PAD_BOTH));
        $agregar("");
        $agregar("Usuario: " . (auth()->user()->name ?? 'Sistema'));
        $agregar("Impreso: " . date('d/m/Y H:i:s'));
        
        $agregar("");
        $agregar("");
        $agregar(str_repeat("─", 48));
        $agregar(str_pad("FIRMA DEL RESPONSABLE", 48, " ", STR_PAD_BOTH));
        $agregar(str_pad("", 48, " ", STR_PAD_BOTH));
        
        // Simular feed y corte
        $agregar("");
        $agregar("");
        $agregar("");
        $agregar("[CORTE DE PAPEL AQUÍ]");
        
        // ==============================================
        // GUARDAR ARCHIVO TXT
        // ==============================================
        $nombreArchivo = 'ticket_' . $venta->ticket . '_' . date('Ymd_His') . '.txt';
        $rutaArchivo = storage_path('app/tickets/' . $nombreArchivo);
        
        // Crear directorio si no existe
        if (!is_dir(dirname($rutaArchivo))) {
            mkdir(dirname($rutaArchivo), 0755, true);
        }
        
        // Guardar archivo
        file_put_contents($rutaArchivo, $ticketTxt);
        
        \Log::info("Ticket TXT generado: {$rutaArchivo}");
        
        // También mostrar en consola
        echo "=== PREVIEW DEL TICKET TXT ===\n";
        echo $ticketTxt;
        echo "================================\n";
        
        return $ticketTxt;
        
    } catch (\Exception $e) {
        \Log::error('Error generando ticket TXT: ' . $e->getMessage());
        return false;
    }
}
*/
/**
 * Reimprimir un ticket existente (SOLO UNA COPIA)
 */
public function reimprimir($id)
{
    try {
        // Buscar la venta
        $venta = VentaNotaAbono::with([
            'cliente.regimen',
            'notaAbono',
            'responsable',
            'detalles'
        ])->findOrFail($id);
        
        // Verificar permisos (opcional)
        // if (auth()->user()->cannot('reimprimir', $venta)) {
        //     return redirect()->back()->with('error', 'No tienes permisos para reimprimir este ticket');
        // }
        
        // Imprimir en impresora térmica (SOLO 1 COPIA)
        $impreso = false;
        
        try {
            $this->imprimirTicketTermica($venta->id);
            $impreso = true;
        
            
        } catch (\Exception $e) {
         
            
            // Intentar con método alternativo
            try {
                $this->imprimirRespaldo($venta->id);
                $impreso = true;
              
            } catch (\Exception $e2) {
              
            }
        }
        
        // Registrar log de reimpresión
      
        
        // Redirigir con mensaje apropiado
        if ($impreso) {
            return redirect()->back()->with([
                'success' => 'Ticket ' . $venta->ticket . ' reimpreso exitosamente (1 copia)'
            ]);
        } else {
            return redirect()->back()->with([
                'warning' => 'Ticket ' . $venta->ticket . ' no se pudo imprimir, pero la operación fue registrada'
            ]);
        }
        
    } catch (\Exception $e) {
   
        
        return redirect()->back()->with('error', 
            'Error al reimprimir ticket: ' . $e->getMessage()
        );
    }
}

/**
 * Imprimir ticket en impresora térmica usando Mike42/Escpos
 */
private function imprimirTicketTermica($ventaId)
{
    try {
        // Nombre de tu impresora térmica (ajusta según tu configuración)
       /* $nombreImpresora = "Bixolon"; // Cambia esto por el nombre de tu impresora
        
        // Crear conexión a la impresora
        $connector = new WindowsPrintConnector($nombreImpresora);
        $printer = new Printer($connector);*/
         $impresora = Impresoras::where('tipo', 'termica')
            ->where('activa', 1)
            ->first();

        if (!$impresora) {
            return redirect()->back()
                ->with('error', 'No hay una impresora térmica activa configurada.');
        }

           // $nombreImpresora="Bixolon";
            $connector= new WindowsPrintConnector($impresora->nombre_sistema);
            $printer= new Printer($connector);

        
        // Cargar datos completos de la venta
        $venta = VentaNotaAbono::with([
            'cliente.regimen',
            'notaAbono',
            'responsable',
            'detalles',
            'detalles.producto',
            'cierreNota'
        ])->findOrFail($ventaId);
        
        // Obtener tasas
        $tasaIva = $venta->cliente->regimen->iva ?? 0;
        $tasaIsr = $venta->cliente->regimen->isr ?? 0;
        $esPersonaMoral = ($tasaIsr > 0);
        
        // Calcular precios para impresión térmica
        $detallesConImpuestos = $venta->detalles->map(function($detalle) use ($tasaIva, $tasaIsr, $esPersonaMoral) {
            $precioConIVA = $detalle->precio_unitario;
            $precioBase = $tasaIva > 0 ? $precioConIVA / (1 + ($tasaIva/100)) : $precioConIVA;

            
            // CONCATENAR MARCA Y MODELO - USANDO LA RELACIÓN 'producto'
            $nombreCompleto = $detalle->nombre_producto;
            
            // Verificar si existe la relación 'producto' y tiene marca/modelo
            if ($detalle->producto) {
                $producto = $detalle->producto;
                
                // Agregar marca si existe y no está vacía
                if (!empty(trim($producto->marca ?? ''))) {
                    $nombreCompleto .= ' ' . $producto->marca;
                }
                
                // Agregar modelo si existe y no está vacío
                if (!empty(trim($producto->modelo ?? ''))) {
                    $nombreCompleto .= ' ' . $producto->modelo;
                }
                
                // (Opcional) Agregar descripción si quieres
                // if (!empty(trim($producto->descripcion ?? ''))) {
                //     $nombreCompleto .= ' - ' . $producto->descripcion;
                // }
            }
            
            // Limpiar espacios extras
            $nombreCompleto = trim($nombreCompleto);
            
            // Si por alguna razón queda vacío, usar el nombre del producto
            if (empty($nombreCompleto)) {
                $nombreCompleto = $detalle->nombre_producto;
            }
            
            
            if ($esPersonaMoral) {
                $ivaUnitario = $precioBase * ($tasaIva / 100);
                $isrUnitario = $precioBase * ($tasaIsr / 100);
                $precioFinal = $precioBase + $ivaUnitario - $isrUnitario;
                
                return [
                    'nombre' => $nombreCompleto,
                    'cantidad' => $detalle->cantidad,
                    'precio_final' => $precioFinal,
                    'total' => $precioFinal * $detalle->cantidad,
                    'precio_base' => $precioBase
                ];
            } else {
                $ivaUnitario = $precioBase * ($tasaIva / 100);
                $precioFinal = $precioBase + $ivaUnitario;
                
                return [
                    'nombre' => $nombreCompleto,
                    'cantidad' => $detalle->cantidad,
                    'precio_final' => $precioFinal,
                    'total' => $precioFinal * $detalle->cantidad,
                    'precio_base' => $precioBase
                ];
            }
        });
        
        // ==============================================
        // INICIAR IMPRESIÓN
        // ==============================================
        
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("Soluciones PC\n");
        $printer->text("RFC: ZARE881013I12\n");
        $printer->text("Tel: 6161362976\n");
        $printer->text("BLVD ADOLFO LOPEZ MATEOS 110\n");
        $printer->text("EJIDO NUEVO MEXICALI, SAN QUINTÍN B.C\n");
        
        $printer->text("\n");
        $printer->text("==============================\n");
        $printer->text("TICKET DE VENTA\n");
        $printer->text($venta->ticket . "\n");
        $printer->text("==============================\n");
        $printer->text("\n");
        
        // FECHA
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("Fecha: " . $venta->created_at->format('d/m/Y H:i') . "\n");
        
        // CLIENTE
        $printer->text("Cliente: " . strtoupper($venta->cliente->nombre) . "\n");
        $printer->text("Nota: " . $venta->notaAbono->folio . "\n");
        
        if ($venta->responsable) {
            $printer->text("Responsable: " . $venta->responsable->nombre . "\n");
        }
        
        $printer->text("\n");
        $printer->text("----------------------------------------\n");
        
        // CABECERA DE PRODUCTOS
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text(str_pad("CANT", 4) . " " . str_pad("DESCRIPCIÓN", 24) . " " . str_pad("TOTAL", 8, " ", STR_PAD_LEFT) . "\n");
        $printer->text("----------------------------------------\n");
        
        // DETALLE DE PRODUCTOS CON MARCA Y MODELO
           foreach ($detallesConImpuestos as $detalle) {
            $cantidad = str_pad($detalle['cantidad'], 4);
            
            // Usar el nombre completo con marca y modelo
            $nombreCompleto = $detalle['nombre'];
            
            // TRUNCAR PARA LA PRIMERA LÍNEA
            $nombreTruncado = substr($nombreCompleto, 0, 24);
            $nombreTruncado = str_pad($nombreTruncado, 24);
            
            $total = str_pad('$' . number_format($detalle['total'], 2), 8, " ", STR_PAD_LEFT);
            
            // Imprimir primera línea
            $printer->text("$cantidad $nombreTruncado $total\n");
            
            // Si el nombre completo es más largo, imprimir el resto
            $longitudNombre = strlen($nombreCompleto);
            if ($longitudNombre > 24) {
                // Imprimir segmentos adicionales
                $inicio = 24;
                while ($inicio < $longitudNombre) {
                    $segmento = substr($nombreCompleto, $inicio, 24);
                    if (!empty(trim($segmento))) {
                        $printer->text(str_pad("", 5) . $segmento . "\n");
                    }
                    $inicio += 24;
                }
            }
            
            
            // Precio unitario con impuestos
            $precioUnit = '$' . number_format($detalle['precio_final'], 2) . " c/u";
            $printer->text(str_pad("", 5) . $precioUnit . "\n");
        }
        
        $printer->text("-------------------------------\n");
        $printer->text("\n");
        $printer->text("Subtotal: $" . number_format($venta->subtotal, 2) . "\n");
        $printer->text("IVA {$tasaIva}%: $" . number_format($venta->iva_calculado, 2) . "\n");

        if ($esPersonaMoral && $venta->isr_calculado > 0) {
            $printer->text("ISR {$tasaIsr}%: -$" . number_format($venta->isr_calculado, 2) . "\n");
        }

        $printer->text(str_repeat("-", 32) . "\n");
        $printer->text("TOTAL: $" . number_format($venta->total, 2) . "\n");

        $cierre = $venta->cierreNota;
            $hayPagoDiferencia = false;

            $montoEfectivo = 0;
            $montoTransferencia = 0;

            if ($cierre) {
                $montoEfectivo = $cierre->monto_efectivo ?? 0;
                $montoTransferencia = $cierre->monto_transferencia ?? 0;

                if ($montoEfectivo > 0 || $montoTransferencia > 0) {
                    $hayPagoDiferencia = true;
                }
            }
       

        //
        
        // CONTROL DE SALDOS - ALINEADO A LA DERECHA
        $printer->text("\n"); // Línea en blanco
        $printer->text("\n"); 
        $printer->text("CONTROL DE SALDO\n");
        $printer->text(str_repeat("-", 40) . "\n");

        $printer->text("Saldo anterior:  $" . number_format($venta->saldo_antes, 2) . "\n");
        $printer->text("- Compra:  -$" . number_format($venta->total, 2) . "\n");
         // ================================
        // PAGO DE DIFERENCIA (SI EXISTE)
        // ================================
        if ($hayPagoDiferencia) {
            $printer->text("\n");
            $printer->text("PAGO DE DIFERENCIA\n");
            $printer->text(str_repeat("-", 32) . "\n");

            if ($montoEfectivo > 0) {
                $printer->text("Efectivo: $" . number_format($montoEfectivo, 2) . "\n");
            }

            if ($montoTransferencia > 0) {
                $printer->text("Transferencia: $" . number_format($montoTransferencia, 2) . "\n");
            }

            if (!empty($cierre->referencia_pago)) {
                $printer->text("Referencia: " . $cierre->referencia_pago . "\n");
            }

            $printer->text(str_repeat("-", 32) . "\n");
        }
        $printer->text("Saldo actual:  $" . number_format($venta->saldo_despues, 2) . "\n");

        $printer->text(str_repeat("-", 32) . "\n");
        // PIE DE TICKET
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("¡GRACIAS POR SU COMPRA!\n");
        
        $printer->text("\n");
        $printer->text("Usuario: " . (auth()->user()->name ?? 'Sistema') . "\n");
        $printer->text("Impreso: " . date('d/m/Y H:i:s') . "\n");
        
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("\n");
        $printer->text("\n");
        
        $printer->text("───────────────────────────────────\n");
        $printer->text("     FIRMA DEL RESPONSABLE          \n");
        $printer->text("                                   \n");

        // FINALIZAR IMPRESIÓN
        $printer->feed(3); // Avanzar 3 líneas
        $printer->cut(); // Cortar papel
        $printer->close();
        
        
        return true;
        
    } catch (\Exception $e) {
      
        // Intentar con impresora alternativa si falla
        return $this->imprimirRespaldo($ventaId);
    }
}

/**
 * Método de respaldo en caso de fallo con la impresora principal
 */
private function imprimirRespaldo($ventaId)
{
    try {
        $venta = VentaNotaAbono::findOrFail($ventaId);
        
        // Intentar con impresora genérica
        $connector = new WindowsPrintConnector("Microsoft Print to PDF"); // O "POS" genérico
        
        $printer = new Printer($connector);
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("SOLUCIONES PC - TICKET DE VENTA\n");
        $printer->text($venta->ticket . "\n");
        $printer->text("ERROR: No se pudo imprimir en térmica\n");
        $printer->text("Ticket guardado en sistema\n");
        $printer->cut();
        $printer->close();
        
        // Guardar log de fallo
   
        
        return false;
        
    } catch (\Exception $e) {
       
        return false;
    }
}

/**
 * Función auxiliar para formatear texto en columnas
 */
private function formatearLineaProducto($cantidad, $descripcion, $precio, $total)
{
    $cantidad = str_pad($cantidad, 4);
    $descripcion = str_pad(substr($descripcion, 0, 20), 20);
    $precio = str_pad('$' . number_format($precio, 2), 8, " ", STR_PAD_LEFT);
    $total = str_pad('$' . number_format($total, 2), 10, " ", STR_PAD_LEFT);
    
    return "$cantidad $descripcion $precio $total";
}
/**
 * Método SEPARADO para obtener el PDF
 */
public function obtenerPDF($id)
{
    try {
        $venta = VentaNotaAbono::findOrFail($id);
        
        if (!$venta->ticket_pdf) {
            // Generar PDF si no existe
            $pdfBlob = $this->generarPDFDesdeVista($id);
            $venta->update(['ticket_pdf' => $pdfBlob]);
            $venta->refresh();
        }
        
        $pdfContent = $venta->ticket_pdf;
        
        // Verificar si es PDF binario
        $isPdf = strpos($pdfContent, '%PDF-') === 0;
        
        if ($isPdf) {
            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="ticket_' . $venta->ticket . '.pdf"'
            ]);
        } else {
            // Si no es PDF, asumir que es HTML/texto
            return response($pdfContent, 200, [
                'Content-Type' => 'text/html',
                'Content-Disposition' => 'inline; filename="ticket_' . $venta->ticket . '.html"'
            ]);
        }
        
    } catch (\Exception $e) {
      
        abort(500, 'Error al obtener el ticket');
    }
}

//
private function generarPDFDesdeVista($ventaId)
{
    try {
        // Cargar datos SIN la relación 'devoluciones'
        $venta = VentaNotaAbono::with([
            'cliente.regimen',
            'notaAbono',
            'responsable',
            'detalles.producto',
            'cierreNota'
        ])->findOrFail($ventaId);

        // ================================
        // OBTENER DEVOLUCIONES MANUALMENTE
        // ================================
        $devoluciones = DevolucionVenta::where('venta_id', $ventaId)->get();
        
        // Crear un array para mapear detalles con sus devoluciones
        $detallesDevoluciones = [];
        foreach ($devoluciones as $devolucion) {
            $detallesDev = json_decode($devolucion->detalles, true) ?? [];
            foreach ($detallesDev as $detalleDev) {
                if (isset($detalleDev['detalle_id'])) {
                    $detalleId = $detalleDev['detalle_id'];
                    
                    // Si ya hay una devolución para este detalle, sumamos las cantidades
                    if (isset($detallesDevoluciones[$detalleId])) {
                        $detallesDevoluciones[$detalleId]['cantidad_devuelta'] += $detalleDev['cantidad_devuelta'] ?? 0;
                        $detallesDevoluciones[$detalleId]['total_devuelto'] += $detalleDev['total'] ?? 0;
                    } else {
                        $detallesDevoluciones[$detalleId] = [
                            'cantidad_devuelta' => $detalleDev['cantidad_devuelta'] ?? 0,
                            'total_devuelto' => $detalleDev['total'] ?? 0,
                            'motivo' => $devolucion->motivo,
                            'folio' => $devolucion->folio_devolucion
                        ];
                    }
                }
            }
        }

        // ================================
        // DETECTAR PAGO DE DIFERENCIA
        // ================================
        $cierre = $venta->cierreNota;

        $hayPagoDiferencia = false;
        $montoEfectivo = 0;
        $montoTransferencia = 0;
        $referenciaPago = null;

        if ($cierre) {
            $montoEfectivo = $cierre->monto_efectivo ?? 0;
            $montoTransferencia = $cierre->monto_transferencia ?? 0;
            $referenciaPago = $cierre->referencia_pago;

            if ($montoEfectivo > 0 || $montoTransferencia > 0) {
                $hayPagoDiferencia = true;
            }
        }

        // Obtener tasas del cliente
        $tasaIva = $venta->cliente->regimen->iva ?? 0;
        $tasaIsr = $venta->cliente->regimen->isr ?? 0;
        $esPersonaMoral = ($tasaIsr > 0);
        
        // VARIABLES PARA CALCULAR TOTALES
        $subtotalNeto = 0;
        $ivaNeto = 0;
        $isrNeto = 0;
        $totalNeto = 0;
        $subtotalOriginal = 0;
        $ivaOriginal = 0;
        $isrOriginal = 0;
        $totalOriginal = 0;
        $diferenciaDevoluciones = 0;
        
        // CALCULAR DETALLES CON INFORMACIÓN DE DEVOLUCIONES
        $detallesConImpuestosYDevoluciones = $venta->detalles->map(function($detalle) use (
            $tasaIva, 
            $tasaIsr, 
            $esPersonaMoral, 
            $detallesDevoluciones,
            &$subtotalNeto,
            &$ivaNeto,
            &$isrNeto,
            &$totalNeto,
            &$subtotalOriginal,
            &$ivaOriginal,
            &$isrOriginal,
            &$totalOriginal,
            &$diferenciaDevoluciones
        ) {
            $precioConIVA = $detalle->precio_unitario;
            $precioBase = $tasaIva > 0 ? $precioConIVA / (1 + ($tasaIva/100)) : $precioConIVA;
            
            // Obtener información de devoluciones
            $infoDevolucion = $detallesDevoluciones[$detalle->id] ?? null;
            
            // Usar cantidad_devuelta del modelo si existe, o de las devoluciones
            $cantidadDevuelta = $detalle->cantidad_devuelta ?? 0;
            if ($infoDevolucion && $infoDevolucion['cantidad_devuelta'] > $cantidadDevuelta) {
                $cantidadDevuelta = $infoDevolucion['cantidad_devuelta'];
            }
            
            // Calcular cantidades
            $cantidadOriginal = $detalle->cantidad;
            $cantidadNeto = $cantidadOriginal - $cantidadDevuelta;
            $devuelto = $cantidadDevuelta > 0;
            
            // Calcular precios con impuestos
            if ($esPersonaMoral) {
                $ivaUnitario = $precioBase * ($tasaIva / 100);
                $isrUnitario = $precioBase * ($tasaIsr / 100);
                $precioFinal = $precioBase + $ivaUnitario - $isrUnitario;
                
                // Calcular montos ORIGINALES
                $subtotalOriginalItem = $precioBase * $cantidadOriginal;
                $ivaOriginalItem = $ivaUnitario * $cantidadOriginal;
                $isrOriginalItem = $isrUnitario * $cantidadOriginal;
                $totalOriginalItem = $precioFinal * $cantidadOriginal;
                
                // Calcular montos NETOS (después de devoluciones)
                $subtotalNetoItem = $precioBase * $cantidadNeto;
                $ivaNetoItem = $ivaUnitario * $cantidadNeto;
                $isrNetoItem = $isrUnitario * $cantidadNeto;
                $totalNetoItem = $precioFinal * $cantidadNeto;
                
                // Calcular diferencia por devoluciones
                $diferenciaItem = $precioFinal * $cantidadDevuelta;
                
            } else {
                // Persona física: solo IVA
                $ivaUnitario = $precioBase * ($tasaIva / 100);
                $precioFinal = $precioBase + $ivaUnitario;
                
                // Calcular montos ORIGINALES
                $subtotalOriginalItem = $precioBase * $cantidadOriginal;
                $ivaOriginalItem = $ivaUnitario * $cantidadOriginal;
                $isrOriginalItem = 0;
                $totalOriginalItem = $precioFinal * $cantidadOriginal;
                
                // Calcular montos NETOS
                $subtotalNetoItem = $precioBase * $cantidadNeto;
                $ivaNetoItem = $ivaUnitario * $cantidadNeto;
                $isrNetoItem = 0;
                $totalNetoItem = $precioFinal * $cantidadNeto;
                
                // Calcular diferencia por devoluciones
                $diferenciaItem = $precioFinal * $cantidadDevuelta;
            }
            
            // Acumular totales generales
            $subtotalNeto += $subtotalNetoItem;
            $ivaNeto += $ivaNetoItem;
            $isrNeto += $isrNetoItem;
            $totalNeto += $totalNetoItem;
            
            $subtotalOriginal += $subtotalOriginalItem;
            $ivaOriginal += $ivaOriginalItem;
            $isrOriginal += $isrOriginalItem;
            $totalOriginal += $totalOriginalItem;
            
            $diferenciaDevoluciones += $diferenciaItem;
            
            return [
                'id' => $detalle->id,
                'nombre_producto' => $detalle->nombre_producto,
                'cantidad_original' => $cantidadOriginal,
                'cantidad_devuelta' => $cantidadDevuelta,
                'cantidad_neto' => $cantidadNeto,
                'devuelto' => $devuelto,
                'precio_base' => $precioBase,
                'precio_con_impuestos' => $precioFinal,
                'iva_unitario' => $ivaUnitario,
                'isr_unitario' => $esPersonaMoral ? $isrUnitario : 0,
                'subtotal_original' => $subtotalOriginalItem,
                'subtotal_neto' => $subtotalNetoItem,
                'iva_original' => $ivaOriginalItem,
                'iva_neto' => $ivaNetoItem,
                'isr_original' => $esPersonaMoral ? $isrOriginalItem : 0,
                'isr_neto' => $esPersonaMoral ? $isrNetoItem : 0,
                'total_original' => $totalOriginalItem,
                'total_neto' => $totalNetoItem,
                'diferencia_item' => $diferenciaItem,
                'precio_desglose' => [
                    'base' => $precioBase,
                    'iva' => $ivaUnitario,
                    'isr' => $esPersonaMoral ? $isrUnitario : 0,
                    'final' => $precioFinal
                ]
            ];
        });
        
        // Calcular total de productos devueltos
        $productosConDevoluciones = $detallesConImpuestosYDevoluciones->where('devuelto', true);
        $totalProductosDevueltos = $productosConDevoluciones->count();
        
        // Preparar datos para la vista
        $data = [
            'venta' => $venta,
            'fecha' => $venta->created_at->format('d/m/Y H:i:s'),
            'usuario' => auth()->user()->name ?? 'Sistema',
            'detalles' => $detallesConImpuestosYDevoluciones,
            'cliente' => $venta->cliente,
            'notaAbono' => $venta->notaAbono,
            'responsable' => $venta->responsable,
            'tasa_iva' => $tasaIva,
            'tasa_isr' => $tasaIsr,
            'es_persona_moral' => $esPersonaMoral,

            // Pago de diferencia
            'hay_pago_diferencia' => $hayPagoDiferencia,
            'monto_efectivo' => $montoEfectivo,
            'monto_transferencia' => $montoTransferencia,
            'referencia_pago' => $referenciaPago,
            
            // Información de devoluciones
            'total_productos_devueltos' => $totalProductosDevueltos,
            'hay_devoluciones' => $totalProductosDevueltos > 0,
            'devoluciones' => $devoluciones,
            
            // Calcular totales netos (usando las variables acumuladas)
            'subtotal_neto' => $subtotalNeto,
            'iva_neto' => $ivaNeto,
            'isr_neto' => $isrNeto,
            'total_neto' => $totalNeto,
            
            // Totales originales para comparación
            'subtotal_original' => $subtotalOriginal,
            'iva_original' => $ivaOriginal,
            'isr_original' => $isrOriginal,
            'total_original' => $totalOriginal,
            
            // Diferencia por devoluciones
            'diferencia_devoluciones' => $diferenciaDevoluciones
        ];
        
        // Generar PDF
        $pdf = PDF::loadView('administrarNotaAbono.ticket', $data)
                  ->setPaper([0, 0, 340, 602.00], 'portrait');
        
        return $pdf->output();
        
    } catch (\Exception $e) {
        Log::error('Error generando PDF: ' . $e->getMessage() . ' - Línea: ' . $e->getLine());
        return null;
    }
}
    /**
     * Agregar abono adicional a la nota
     */
    public function agregarAbono(Request $request, $notaAbonoId)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validate([
                'monto' => 'required|numeric|min:0.01',
                'observaciones' => 'nullable|string|max:500'
            ]);

            $notaAbono = NotaAbono::findOrFail($notaAbonoId);

            // Actualizar saldos
            $nuevoSaldo = $notaAbono->saldo_actual + $request->monto;
            $notaAbono->update([
                'saldo_actual' => $nuevoSaldo,
                'abono_inicial' => $notaAbono->abono_inicial + $request->monto
            ]);

            // Registrar movimiento
            MovimientoAbono::create([
                'nota_abono_id' => $notaAbono->id,
                'tipo' => 'abono',
                'monto' => $request->monto,
                'saldo_anterior' => $notaAbono->saldo_actual,
                'nuevo_saldo' => $nuevoSaldo,
                'concepto' => 'Abono adicional',
                'observaciones' => $request->observaciones,
                'user_id' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Abono agregado exitosamente',
                'nuevo_saldo' => $nuevoSaldo
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar abono: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cerrar la nota de abono
     */
    public function cerrarNota(Request $request, $notaAbonoId)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validate([
                
                'observaciones' => 'nullable|string|max:500'
            ]);

            $notaAbono = NotaAbono::findOrFail($notaAbonoId);

            // Verificar que no tenga ventas pendientes
            $ventasPendientes = $notaAbono->ventas()->where('estado', '!=', 'completada')->count();
            if ($ventasPendientes > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede cerrar la nota porque tiene ventas pendientes'
                ], 400);
            }

            // Actualizar estado
            $notaAbono->update([
                'estado' => $request->motivo,
                'fecha_cierre' => now(),
                'observaciones' => $request->observaciones
            ]);

            // Registrar movimiento de cierre
            MovimientoAbono::create([
                'nota_abono_id' => $notaAbono->id,
                'tipo' => 'cierre',
                'monto' => 0,
                'saldo_anterior' => $notaAbono->saldo_actual,
                'nuevo_saldo' => $notaAbono->saldo_actual,
                'concepto' => 'Cierre de nota - Motivo: ' . $request->motivo,
                'observaciones' => $request->observaciones,
                'user_id' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Nota de abono cerrada exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar la nota: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener productos para autocompletado
     */
  public function buscarProductos(Request $request)
{
    $query = $request->get('query', '');
    
    try {
        // Validar que la query tenga al menos 2 caracteres
        if (strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'productos' => [],
                'message' => 'Query demasiado corta'
            ]);
        }

        // Buscar productos de la categoría 2
        $productos = NombreConcepto::where('id_categoria', 2)
            ->where(function($q) use ($query) {
                $q->where('nombre', 'LIKE', "%{$query}%")
                  ->orWhere('codigo_barra', 'LIKE', "%{$query}%")
                  ->orWhere('modelo', 'LIKE', "%{$query}%")
                  ->orWhere('marca', 'LIKE', "%{$query}%");
            })
            ->orderBy('nombre')
            ->limit(6)
            ->get(['id', 'nombre', 'codigo_barra', 'precio', 'cantidad as stock','marca', 'modelo' ]);

        return response()->json([
            'success' => true,
            'productos' => $productos,
            'total' => $productos->count()
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al buscar productos: ' . $e->getMessage()
        ], 500);
    }
}
//
// En tu controlador (por ejemplo, ProductoController.php)
public function buscarPorCodigoBarra(Request $request)
{
    try {
        $codigoBarra = trim($request->get('codigo_barra', ''));
        
        if (empty($codigoBarra)) {
            return response()->json([
                'success' => false,
                'message' => 'Código de barras vacío'
            ], 400);
        }

        // Buscar producto por código de barras en categoría 2
        $producto = NombreConcepto::where('id_categoria', 2)
            ->where('codigo_barra', $codigoBarra)
            ->first([
                'id', 
                'nombre', 
                'codigo_barra', 
                'precio', 
                'cantidad as stock',
                'marca',
                'modelo'
            ]);

        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        // Verificar stock
        if ($producto->stock <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Producto agotado',
                'producto' => $producto
            ], 400);
        }

        return response()->json([
            'success' => true,
            'producto' => $producto,
            'message' => 'Producto encontrado'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al buscar producto: ' . $e->getMessage()
        ], 500);
    }
}
// En tu controlador AdministrarNotaAbono
public function buscarResponsables(Request $request)
{
    

    try {
        $query = $request->get('query', '');
        $clienteId = $request->get('cliente_id');


        // Si el query es muy corto
        if (strlen($query) < 2) {
            

            return response()->json([
                'success' => true,
                'responsables' => []
            ]);
        }

       

        $responsables = ResponsableAbono::where('cliente_id', $clienteId)
            ->where('nombre', 'LIKE', "%{$query}%")
            ->withCount('ventasNotaAbono')
            ->orderBy('nombre')
            ->limit(10)
            ->get(['id', 'nombre']);

        

        return response()->json([
            'success' => true,
            'responsables' => $responsables
        ]);

    } catch (\Exception $e) {

        

        return response()->json([
            'success' => false,
            'message' => 'Error al buscar responsables'
        ], 500);
    }
}


/*
Buscar Responsables
*/
public function registrarResponsable(Request $request)
{
    try {
        $request->validate([
            'cliente_id' => 'required|exists:clientes_abonos,id',
            'nombre' => 'required|string|max:150'
        ]);
        
        // Verificar si ya existe
        $existente = ResponsableAbono::where('cliente_id', $request->cliente_id)
            ->where('nombre', $request->nombre)
            ->first();
            
        if ($existente) {
            return response()->json([
                'success' => false,
                'message' => 'Este responsable ya está registrado para este cliente'
            ], 422);
        }
        
        $responsable = ResponsableAbono::create([
            'cliente_id' => $request->cliente_id,
            'nombre' => $request->nombre
        ]);
        
        return response()->json([
            'success' => true,
            'responsable' => $responsable,
            'message' => 'Responsable registrado exitosamente'
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al registrar responsable'
        ], 500);
    }
}
// Agrega este método a tu controlador
public function obtenerRegimenCliente(Request $request)
{
    try {
        $clienteId = $request->get('cliente_id');
        
        $cliente = ClienteAbono::with('regimen')->find($clienteId);
        
        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado'
            ]);
        }

        if (!$cliente->regimen) {
            return response()->json([
                'success' => false,
                'message' => 'El cliente no tiene un régimen fiscal asignado'
            ]);
        }

        return response()->json([
            'success' => true,
            'regimen' => [
                'id' => $cliente->regimen->id,
                'nombre' => $cliente->regimen->nombre,
                'iva' => $cliente->regimen->iva,
                'isr' => $cliente->regimen->isr
            ]
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al obtener el régimen del cliente'
        ]);
    }
}
    /**
     * Obtener historial de la nota de abono
     */
    public function obtenerHistorial($notaAbonoId)
    {
        try {
            $movimientos = MovimientoAbono::with(['usuario', 'venta'])
                ->where('nota_abono_id', $notaAbonoId)
                ->orderBy('created_at', 'desc')
                ->get();

            $ventas = VentaNotaAbono::with(['detalles.producto'])
                ->where('nota_abono_id', $notaAbonoId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'movimientos' => $movimientos,
                'ventas' => $ventas
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial: ' . $e->getMessage()
            ], 500);
        }
    }

    //
    /**
 * Cancelar una venta registrada
 */
public function cancelarVenta(Request $request, $id)
{
    try {
        DB::beginTransaction();
        
        // Buscar la venta con relaciones necesarias
        $venta = VentaNotaAbono::with([
            'cliente',
            'notaAbono',
            'detalles.producto'
        ])->findOrFail($id);
        
        // Validar que la venta no esté ya cancelada
        if ($venta->estado === 'cancelada') {
            return response()->json([
                'success' => false,
                'message' => 'Esta venta ya está cancelada'
            ], 400);
        }
        
        // Obtener motivo de cancelación
        $motivo = $request->input('motivo', 'Cancelación por usuario');
        $observaciones = $request->input('observaciones', '');
        
        // 1. RESTAURAR STOCK DE PRODUCTOS
        foreach ($venta->detalles as $detalle) {
            $producto = $detalle->producto;
            if ($producto) {
                $producto->increment('cantidad', $detalle->cantidad);
           
            }
        }
        
        // 2. RESTAURAR SALDOS
        $notaAbono = $venta->notaAbono;
        $cliente = $venta->cliente;
        
        // Calcular nuevos saldos
        $nuevoSaldoNota = $notaAbono->saldo_actual + $venta->total;
        $nuevoSaldoCliente = $cliente->saldo_global + $venta->total;
        
        // Actualizar nota de abono
        $notaAbono->update([
            'saldo_actual' => $nuevoSaldoNota,
            'subtotal_acumulado' => $notaAbono->subtotal_acumulado - $venta->subtotal,
            'iva_calculado' => $notaAbono->iva_calculado - $venta->iva_calculado,
            'isr_calculado' => $notaAbono->isr_calculado - $venta->isr_calculado,
            'total_con_impuestos' => $notaAbono->total_con_impuestos - $venta->total
        ]);
        
        // Actualizar cliente
        $cliente->update([
            'saldo_global' => $nuevoSaldoCliente
        ]);
        
        // 3. REGISTRAR MOVIMIENTO DE CANCELACIÓN
        MovimientoAbono::create([
            'nota_abono_id' => $notaAbono->id,
            'tipo' => 'cancelacion',
            'monto' => $venta->total,
            'saldo_anterior' => $notaAbono->saldo_actual - $venta->total, // Saldo antes de cancelar
            'nuevo_saldo' => $nuevoSaldoNota,
            'concepto' => 'Cancelación venta - ' . $venta->ticket,
            'observaciones' => "Motivo: {$motivo}. " . $observaciones,
            'venta_id' => $venta->id,
            'user_id' => auth()->id()
        ]);
        
        // 4. MARCAR VENTA COMO CANCELADA
        $venta->update([
            'estado' => 'cancelada',
            'fecha_cancelacion' => now(),
            'motivo_cancelacion' => $motivo,
            'usuario_cancelacion_id' => auth()->id()
        ]);
        
        // 5. GENERAR TICKET DE CANCELACIÓN (opcional, pero recomendado)
        $this->generarTicketCancelacion($venta->id, $motivo);
        
        DB::commit();
        

        
        return response()->json([
            'success' => true,
            'message' => 'Venta cancelada exitosamente',
            'nuevo_saldo' => $nuevoSaldoNota,
            'ticket_cancelado' => $venta->ticket
        ]);
        
    } catch (\Exception $e) {
        DB::rollBack();
    
        
        return response()->json([
            'success' => false,
            'message' => 'Error al cancelar venta: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Generar ticket de cancelación para impresión
 */
private function generarTicketCancelacion($ventaId, $motivo)
{
    try {
        $venta = VentaNotaAbono::with([
            'cliente.regimen',
            'notaAbono',
            'responsable',
            'detalles',
            'detalles.producto' // Agregar producto para marca/modelo
        ])->findOrFail($ventaId);
        
        // Obtener tasas
        $tasaIva = $venta->cliente->regimen->iva ?? 0;
        $tasaIsr = $venta->cliente->regimen->isr ?? 0;
        $esPersonaMoral = ($tasaIsr > 0);
        
        // Calcular precios para impresión
        $detallesConImpuestos = $venta->detalles->map(function($detalle) use ($tasaIva, $tasaIsr, $esPersonaMoral) {
            $precioConIVA = $detalle->precio_unitario;
            $precioBase = $tasaIva > 0 ? $precioConIVA / (1 + ($tasaIva/100)) : $precioConIVA;
            
            // CONCATENAR MARCA Y MODELO
            $nombreCompleto = $detalle->nombre_producto;
            
            if ($detalle->producto) {
                $producto = $detalle->producto;
                
                if (!empty(trim($producto->marca ?? ''))) {
                    $nombreCompleto .= ' ' . $producto->marca;
                }
                
                if (!empty(trim($producto->modelo ?? ''))) {
                    $nombreCompleto .= ' ' . $producto->modelo;
                }
            }
            
            $nombreCompleto = trim($nombreCompleto);
            
            if (empty($nombreCompleto)) {
                $nombreCompleto = $detalle->nombre_producto;
            }
            
            if ($esPersonaMoral) {
                $ivaUnitario = $precioBase * ($tasaIva / 100);
                $isrUnitario = $precioBase * ($tasaIsr / 100);
                $precioFinal = $precioBase + $ivaUnitario - $isrUnitario;
                
                return [
                    'nombre' => $nombreCompleto,
                    'cantidad' => $detalle->cantidad,
                    'precio_final' => $precioFinal,
                    'total' => $precioFinal * $detalle->cantidad,
                    'precio_base' => $precioBase
                ];
            } else {
                $ivaUnitario = $precioBase * ($tasaIva / 100);
                $precioFinal = $precioBase + $ivaUnitario;
                
                return [
                    'nombre' => $nombreCompleto,
                    'cantidad' => $detalle->cantidad,
                    'precio_final' => $precioFinal,
                    'total' => $precioFinal * $detalle->cantidad,
                    'precio_base' => $precioBase
                ];
            }
        });
        
        // Nombre de la impresora
        /*$nombreImpresora = "Bixolon";
        
        // Crear conexión a la impresora
        $connector = new WindowsPrintConnector($nombreImpresora);
        $printer = new Printer($connector);*/
         $impresora = Impresoras::where('tipo', 'termica')
            ->where('activa', 1)
            ->first();

        if (!$impresora) {
            return redirect()->back()
                ->with('error', 'No hay una impresora térmica activa configurada.');
        }

           // $nombreImpresora="Bixolon";
            $connector= new WindowsPrintConnector($impresora->nombre_sistema);
            $printer= new Printer($connector);

        
        // ==============================================
        // IMPRIMIR TICKET DE CANCELACIÓN
        // ==============================================
        
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("Soluciones PC\n");
        $printer->text("RFC: ZARE881013I12\n");
        $printer->text("Tel: 6161362976\n");
        $printer->text("BLVD ADOLFO LOPEZ MATEOS 110\n");
        $printer->text("EJIDO NUEVO MEXICALI, SAN QUINTÍN B.C\n");
        
        $printer->text("\n");
        $printer->text(str_repeat("=", 32) . "\n");
        $printer->text("CANCELACIÓN DE VENTA\n");
        $printer->text(str_repeat("=", 32) . "\n");
        $printer->text("\n");
        
        // INFORMACIÓN DE CANCELACIÓN
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("Fecha cancelación: " . now()->format('d/m/Y H:i:s') . "\n");
        $printer->text("Ticket: " . $venta->ticket . "\n");
        $printer->text("Fecha venta: " . $venta->created_at->format('d/m/Y H:i') . "\n");
        $printer->text("Cliente: " . strtoupper($venta->cliente->nombre) . "\n");
        $printer->text("Nota: " . $venta->notaAbono->folio . "\n");
        
        $printer->text("\n");
        $printer->text(str_repeat("-", 32) . "\n");
        $printer->text("MOTIVO DE CANCELACIÓN:\n");
        $printer->text(wordwrap($motivo, 32, "\n") . "\n");
        $printer->text(str_repeat("-", 32) . "\n");
        
        $printer->text("\n");
        $printer->text(str_repeat("-", 40) . "\n");
        
        // CABECERA DE PRODUCTOS
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text(str_pad("CANT", 4) . " " . str_pad("DESCRIPCIÓN", 24) . " " . str_pad("TOTAL", 8, " ", STR_PAD_LEFT) . "\n");
        $printer->text(str_repeat("-", 40) . "\n");
        
        // DETALLE DE PRODUCTOS CANCELADOS
        foreach ($detallesConImpuestos as $detalle) {
            $cantidad = str_pad($detalle['cantidad'], 4);
            
            // Usar el nombre completo con marca y modelo
            $nombreCompleto = $detalle['nombre'];
            
            // TRUNCAR PARA LA PRIMERA LÍNEA
            $nombreTruncado = substr($nombreCompleto, 0, 24);
            $nombreTruncado = str_pad($nombreTruncado, 24);
            
            $total = str_pad('$' . number_format($detalle['total'], 2), 8, " ", STR_PAD_LEFT);
            
            // Imprimir primera línea
            $printer->text("$cantidad $nombreTruncado $total\n");
            
            // Si el nombre completo es más largo, imprimir el resto
            $longitudNombre = strlen($nombreCompleto);
            if ($longitudNombre > 24) {
                $inicio = 24;
                while ($inicio < $longitudNombre) {
                    $segmento = substr($nombreCompleto, $inicio, 24);
                    if (!empty(trim($segmento))) {
                        $printer->text(str_pad("", 5) . $segmento . "\n");
                    }
                    $inicio += 24;
                }
            }
            
            // Precio unitario con impuestos
            $precioUnit = '$' . number_format($detalle['precio_final'], 2) . " c/u";
            $printer->text(str_pad("", 5) . $precioUnit . "\n");
        }
        
        $printer->text(str_repeat("-", 32) . "\n");
        $printer->text("\n");
        
        // RESUMEN DE IMPUESTOS CANCELADOS
        $printer->setJustification(Printer::JUSTIFY_RIGHT);
        $printer->text("Subtotal: $" . number_format($venta->subtotal, 2) . "\n");
        $printer->text("IVA {$tasaIva}%: $" . number_format($venta->iva_calculado, 2) . "\n");
        
        if ($esPersonaMoral && $venta->isr_calculado > 0) {
            $printer->text("ISR {$tasaIsr}%: -$" . number_format($venta->isr_calculado, 2) . "\n");
        }
        
        $printer->text(str_repeat("-", 32) . "\n");
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("TOTAL CANCELADO:\n");
        $printer->setJustification(Printer::JUSTIFY_RIGHT);
        $printer->text(str_pad('$' . number_format($venta->total, 2), 32, " ", STR_PAD_LEFT) . "\n");
        
        $printer->text("\n");
        $printer->text(str_repeat("=", 32) . "\n");
        
        // CONTROL DE SALDOS ACTUALIZADO
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("CONTROL DE SALDOS\n");
        $printer->text(str_repeat("-", 40) . "\n");
        
        // Calcular saldos
        $saldoAnterior = $venta->notaAbono->saldo_actual - $venta->total;
        
        $printer->text("Saldo anterior:  $" . number_format($saldoAnterior, 2) . "\n");
        $printer->text("+ Reembolso:  +$" . number_format($venta->total, 2) . "\n");
        $printer->text("Nuevo saldo:  $" . number_format($venta->notaAbono->saldo_actual, 2) . "\n");
        $printer->text(str_repeat("-", 32) . "\n");
        
        // PIE DE TICKET
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("CANCELACIÓN REGISTRADA\n");
        
        $printer->text("\n");
        $printer->text("Usuario: " . (auth()->user()->name ?? 'Sistema') . "\n");
        $printer->text("Cancelado: " . date('d/m/Y H:i:s') . "\n");
        
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("\n");
        $printer->text("\n");
        
        // FIRMAS
        $printer->text(str_repeat("─", 32) . "\n");
        $printer->text("     FIRMA DEL CLIENTE          \n");
        $printer->text("                                \n");
        
        $printer->text("\n");
        
        // FINALIZAR IMPRESIÓN
        $printer->feed(3);
        $printer->cut();
        $printer->close();
        
     
        
        return true;
        
    } catch (\Exception $e) {
       
        
        // Intentar con impresora alternativa si falla
        return false;
    }
}

//
/**
 * Devolver productos de una venta
 */
/**
 * Devolver productos de una venta (VERSIÓN ACTUALIZADA CON REGENERACIÓN DE PDF)
 */
public function devolverProductos(Request $request, $id)
{
    try {
        DB::beginTransaction();
        
        // Buscar la venta
        $venta = VentaNotaAbono::with([
            'detalles.producto', 
            'cliente', 
            'cliente.regimen', 
            'notaAbono'
        ])->findOrFail($id);
        
        // Validaciones
        if ($venta->estado === 'cancelada') {
            return response()->json([
                'success' => false,
                'message' => 'No se pueden devolver productos de una venta cancelada'
            ], 400);
        }
        
        $request->validate([
            'productos' => 'required|array|min:1',
            'productos.*.detalle_id' => 'required|exists:venta_detallesNotaAbono,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'motivo' => 'required|string|max:255',
            'observaciones' => 'nullable|string|max:500'
        ]);
        
        $motivo = $request->input('motivo'); // Esto ya viene como 'producto_defectuoso'

        // Validar que el motivo sea uno de los permitidos
        $motivosPermitidos = [
            'producto_defectuoso',
            'no_corresponde_pedido', 
            'cliente_arrepentido',
            'error_cantidad',
            'cambio_producto',
            'otro'
        ];

        if (!in_array($motivo, $motivosPermitidos)) {
            $motivo = 'otro'; // Valor por defecto si no es válido
        }

        $observaciones = $request->input('observaciones', '');
        $productosDevolucion = $request->input('productos', []);
        
        // Variables para cálculos
        $subtotalDevolucion = 0;
        $ivaDevolucion = 0;
        $isrDevolucion = 0;
        $totalDevolucion = 0;
        $detallesDevolucion = [];
        
        // Obtener tasas del cliente
        $tasaIva = $venta->cliente->regimen->iva ?? 0;
        $tasaIsr = $venta->cliente->regimen->isr ?? 0;
        $esPersonaMoral = ($tasaIsr > 0);
        
        // 1. PROCESAR CADA PRODUCTO
        foreach ($productosDevolucion as $productoDev) {
            $detalle = $venta->detalles->firstWhere('id', $productoDev['detalle_id']);
            
            if (!$detalle) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado en la venta'
                ], 400);
            }
            
            $cantidadDevolver = $productoDev['cantidad'];
            $cantidadDisponible = $detalle->cantidad - $detalle->cantidad_devuelta;
            
            // Validar cantidad
            if ($cantidadDevolver > $cantidadDisponible) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "Cantidad excede disponible para: {$detalle->nombre_producto}"
                ], 400);
            }
            
            // Calcular montos a devolver (con impuestos)
            $precioUnitario = $detalle->precio_unitario; // Este precio ya incluye impuestos
            
            // Calcular precio base
            $precioBase = $tasaIva > 0 ? $precioUnitario / (1 + ($tasaIva/100)) : $precioUnitario;
            
            // Calcular impuestos unitarios
            $ivaUnitario = $precioBase * ($tasaIva / 100);
            $isrUnitario = $esPersonaMoral ? $precioBase * ($tasaIsr / 100) : 0;
            
            // Calcular montos totales
            $subtotalProducto = $precioBase * $cantidadDevolver;
            $ivaProducto = $ivaUnitario * $cantidadDevolver;
            $isrProducto = $isrUnitario * $cantidadDevolver;
            $totalProducto = $subtotalProducto + $ivaProducto - $isrProducto;
            
            // Actualizar detalle
            $detalle->update([
                'cantidad_devuelta' => $detalle->cantidad_devuelta + $cantidadDevolver,
                'monto_devuelto' => $detalle->monto_devuelto + $totalProducto,
                'devuelto' => ($detalle->cantidad_devuelta + $cantidadDevolver) >= $detalle->cantidad ? 1 : 0,
                'fecha_devolucion' => now(),
                'motivo_devolucion' => $motivo
            ]);
            
            // Restaurar stock
            if ($detalle->producto) {
                $detalle->producto->increment('cantidad', $cantidadDevolver);
            }
            
            // Acumular totales
            $subtotalDevolucion += $subtotalProducto;
            $ivaDevolucion += $ivaProducto;
            $isrDevolucion += $isrProducto;
            $totalDevolucion += $totalProducto;
            
            $detallesDevolucion[] = [
                'detalle_id' => $detalle->id,
                'producto_id' => $detalle->producto_id,
                'nombre' => $detalle->nombre_producto,
                'cantidad_devuelta' => $cantidadDevolver,
                'precio_unitario' => $precioUnitario,
                'precio_base' => $precioBase,
                'iva_unitario' => $ivaUnitario,
                'isr_unitario' => $isrUnitario,
                'subtotal' => $subtotalProducto,
                'iva' => $ivaProducto,
                'isr' => $isrProducto,
                'total' => $totalProducto
            ];
        }
        
        // 2. CREAR REGISTRO DE DEVOLUCIÓN
        $devolucion = DevolucionVenta::create([
            'venta_id' => $venta->id,
            'nota_abono_id' => $venta->nota_abono_id,
            'cliente_id' => $venta->cliente_id,
            'folio_devolucion' => DevolucionVenta::generarFolio(),
            'motivo' => $motivo,
            'observaciones' => $observaciones,
            'subtotal' => $subtotalDevolucion,
            'iva' => $ivaDevolucion,
            'isr' => $isrDevolucion,
            'total' => $totalDevolucion,
            'estado' => DevolucionVenta::ESTADO_COMPLETADA,
            'detalles' => json_encode($detallesDevolucion),
            'user_id' => auth()->id()
        ]);
        
        // 3. ACTUALIZAR SALDOS
        $notaAbono = $venta->notaAbono;
        $cliente = $venta->cliente;
        
        $nuevoSaldoNota = $notaAbono->saldo_actual + $totalDevolucion;
        $saldoAnterior = $notaAbono->saldo_actual;
        
        // Actualizar nota de abono
        $notaAbono->update([
            'saldo_actual' => $nuevoSaldoNota,
            'subtotal_acumulado' => $notaAbono->subtotal_acumulado - $subtotalDevolucion,
            'iva_calculado' => $notaAbono->iva_calculado - $ivaDevolucion,
            'isr_calculado' => $notaAbono->isr_calculado - $isrDevolucion,
            'total_con_impuestos' => $notaAbono->total_con_impuestos - $totalDevolucion
        ]);
        
        // Actualizar cliente
        $cliente->update([
            'saldo_global' => $cliente->saldo_global + $totalDevolucion
        ]);
        
        // 4. ACTUALIZAR ESTADO DE LA VENTA Y RELACIONAR DEVOLUCIÓN
        $totalCantidad = $venta->detalles->sum('cantidad');
        $totalDevuelta = $venta->detalles->sum('cantidad_devuelta');

        if ($totalDevuelta === 0) {
            $nuevoEstado = 'completada';
        } elseif ($totalDevuelta < $totalCantidad) {
            $nuevoEstado = 'parcialmente_devuelta';
        } else {
            $nuevoEstado = 'totalmente_devuelta';
        }

        // Actualizar la venta con los nuevos montos
        $venta->update([
            'estado' => $nuevoEstado,
            'subtotal' => $venta->subtotal - $subtotalDevolucion,
            'iva_calculado' => $venta->iva_calculado - $ivaDevolucion,
            'isr_calculado' => $venta->isr_calculado - $isrDevolucion,
            'total' => $venta->total - $totalDevolucion
        ]);
        
        // 5. ACTUALIZAR EL PDF DE LA VENTA (ESTO ES LO NUEVO)
        try {
            // Llamar al método para generar el PDF actualizado
            if (method_exists($this, 'generarPDFDesdeVista')) {
                $pdfBlob = $this->generarPDFDesdeVista($venta->id);
                
                // Actualizar el campo ticket_pdf con el nuevo PDF
                $venta->update([
                    'ticket_pdf' => $pdfBlob
                ]);
                
                // Registrar que se actualizó el PDF
            
            }
        } catch (\Exception $e) {
            Log::warning('Error generando PDF después de devolución: ' . $e->getMessage());
            // No fallar la transacción si el PDF falla
        }
        
        // 6. Actualizar detalles con el ID de la devolución
        foreach ($productosDevolucion as $productoDev) {
            VentaDetalleNotaAbono::where('id', $productoDev['detalle_id'])
                ->update(['devolucion_id' => $devolucion->id]);
        }
        
        // 7. REGISTRAR MOVIMIENTO
        MovimientoAbono::create([
            'nota_abono_id' => $notaAbono->id,
            'tipo' => 'devolucion',
            'monto' => $totalDevolucion,
            'saldo_anterior' => $saldoAnterior,
            'nuevo_saldo' => $nuevoSaldoNota,
            'concepto' => 'Devolución - ' . $venta->ticket . ' (Folio: ' . $devolucion->folio_devolucion . ')',
            'observaciones' => "Motivo: {$motivo}. " . $observaciones,
            'venta_id' => $venta->id,
            'user_id' => auth()->id()
        ]);
        
        DB::commit();

        // 8. IMPRIMIR TICKET DE DEVOLUCIÓN
        try {
            $this->imprimirTicketDevolucion($devolucion->id, $motivo);
        } catch (\Exception $e) {
            Log::warning('Error imprimiendo ticket de devolución: ' . $e->getMessage());
            // No fallamos la transacción si la impresión falla
        }
        
        // Registrar en logs
  
        
        return response()->json([
            'success' => true,
            'message' => 'Devolución registrada exitosamente',
            'nuevo_saldo' => $nuevoSaldoNota,
            'total_devolucion' => $totalDevolucion,
            'nuevo_estado' => $nuevoEstado,
            'folio_devolucion' => $devolucion->folio_devolucion,
            'devolucion_id' => $devolucion->id,
            'pdf_actualizado' => true // Indicador de que se actualizó el PDF
        ]);
        
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error al procesar devolución: ' . $e->getMessage() . ' - Línea: ' . $e->getLine());
        
        return response()->json([
            'success' => false,
            'message' => 'Error al procesar devolución: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Método para obtener productos de una venta para devolución
 */
public function obtenerProductosParaDevolucion($ventaId)
{
    try {
        $venta = VentaNotaAbono::with(['detalles.producto'])->findOrFail($ventaId);
        
        $productos = $venta->detalles->map(function($detalle) {
            $cantidadDisponible = $detalle->cantidad - ($detalle->cantidad_devuelta ?? 0);
            
            return [
                'id' => $detalle->id,
                'nombre_producto' => $detalle->nombre_producto,
                'codigo_barra' => optional($detalle->producto)->codigo_barra,
                'precio_unitario' => $detalle->precio_unitario,
                'cantidad' => $detalle->cantidad,
                'cantidad_devuelta' => $detalle->cantidad_devuelta ?? 0,
                'disponible_devolver' => $cantidadDisponible,
                'puede_devolver' => $cantidadDisponible > 0
            ];
        });
        
        return response()->json([
            'success' => true,
            'productos' => $productos,
            'venta' => [
                'ticket' => $venta->ticket,
                'estado' => $venta->estado,
                'cliente' => $venta->cliente->nombre ?? 'N/A'
            ]
        ]);
        
    } catch (\Exception $e) {
      
        return response()->json([
            'success' => false,
            'message' => 'Error al obtener productos: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Método para imprimir ticket de devolución
 */
private function imprimirTicketDevolucion($devolucionId, $motivo)
{
    try {
        // Cargar la devolución con relaciones
        $devolucion = DevolucionVenta::with([
            'venta.cliente.regimen',
            'cliente',
            'notaAbono',
            'usuario',
            'venta.detalles.producto'
        ])->findOrFail($devolucionId);
        
        // Obtener tasas del cliente
        $tasaIva = $devolucion->venta->cliente->regimen->iva ?? 0;
        $tasaIsr = $devolucion->venta->cliente->regimen->isr ?? 0;
        $esPersonaMoral = ($tasaIsr > 0);
        
        // Parsear detalles de devolución
        $detallesDevolucion = json_decode($devolucion->detalles, true) ?? [];
        
        // Calcular precios finales con impuestos para cada detalle
        $detallesConImpuestos = [];
        $subtotal = 0;
        $ivaCalculado = 0;
        $isrCalculado = 0;
        
        foreach ($detallesDevolucion as $detalle) {
            $precioConIVA = $detalle['precio_unitario'] ?? 0;
            
            // Calcular precio base (sin IVA)
            $precioBase = $tasaIva > 0 ? $precioConIVA / (1 + ($tasaIva/100)) : $precioConIVA;
            
            // Obtener nombre completo con marca y modelo si está disponible
            $nombreCompleto = $detalle['nombre'] ?? 'Producto';
            
            // Buscar si hay producto relacionado para agregar marca/modelo
            $producto = null;
            if (isset($detalle['producto_id']) && $devolucion->venta && $devolucion->venta->detalles) {
                foreach ($devolucion->venta->detalles as $detalleVenta) {
                    if ($detalleVenta->producto_id == $detalle['producto_id'] && $detalleVenta->producto) {
                        $producto = $detalleVenta->producto;
                        break;
                    }
                }
            }
            
            // Agregar marca y modelo si existen
            if ($producto) {
                if (!empty(trim($producto->marca ?? ''))) {
                    $nombreCompleto .= ' ' . $producto->marca;
                }
                if (!empty(trim($producto->modelo ?? ''))) {
                    $nombreCompleto .= ' ' . $producto->modelo;
                }
            }
            
            // Calcular precio final con impuestos
            if ($esPersonaMoral) {
                $ivaUnitario = $precioBase * ($tasaIva / 100);
                $isrUnitario = $precioBase * ($tasaIsr / 100);
                $precioFinal = $precioBase + $ivaUnitario - $isrUnitario;
            } else {
                $ivaUnitario = $precioBase * ($tasaIva / 100);
                $precioFinal = $precioBase + $ivaUnitario;
            }
            
            $cantidad = $detalle['cantidad_devuelta'] ?? 1;
            $total = $precioFinal * $cantidad;
            
            $detallesConImpuestos[] = [
                'nombre' => $nombreCompleto,
                'cantidad' => $cantidad,
                'precio_final' => $precioFinal,
                'total' => $total,
                'precio_base' => $precioBase
            ];
            
            // Acumular para totales
            $subtotal += $precioBase * $cantidad;
            $ivaCalculado += $ivaUnitario * $cantidad;
            $isrCalculado += ($isrUnitario ?? 0) * $cantidad;
        }
        
        // Nombre de la impresora
        /*$nombreImpresora = "Bixolon";
        
        // Crear conexión a la impresora
        $connector = new WindowsPrintConnector($nombreImpresora);
        $printer = new Printer($connector);*/
         $impresora = Impresoras::where('tipo', 'termica')
            ->where('activa', 1)
            ->first();

        if (!$impresora) {
            return redirect()->back()
                ->with('error', 'No hay una impresora térmica activa configurada.');
        }

           // $nombreImpresora="Bixolon";
        $connector= new WindowsPrintConnector($impresora->nombre_sistema);
        $printer= new Printer($connector);

        
        // ==============================================
        // IMPRIMIR TICKET DE DEVOLUCIÓN
        // ==============================================
        
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("Soluciones PC\n");
        $printer->text("RFC: ZARE881013I12\n");
        $printer->text("Tel: 6161362976\n");
        $printer->text("BLVD ADOLFO LOPEZ MATEOS 110\n");
        $printer->text("EJIDO NUEVO MEXICALI, SAN QUINTÍN B.C\n");
        
        $printer->text("\n");
        $printer->text(str_repeat("=", 32) . "\n");
        $printer->text("DEVOLUCIÓN DE PRODUCTOS\n");
        $printer->text(str_repeat("=", 32) . "\n");
        $printer->text("\n");
        
        // INFORMACIÓN DE DEVOLUCIÓN
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("Fecha: " . now()->format('d/m/Y H:i:s') . "\n");
        $printer->text("Folio: " . $devolucion->folio_devolucion . "\n");
        $printer->text("Ticket: " . $devolucion->venta->ticket . "\n");
        $printer->text("Nota: " . $devolucion->notaAbono->folio . "\n");
        $printer->text("Cliente: " . strtoupper($devolucion->cliente->nombre) . "\n");
        
        $printer->text("\n");
        $printer->text(str_repeat("-", 32) . "\n");
        $printer->text("MOTIVO DE DEVOLUCIÓN:\n");
        $printer->text(wordwrap($motivo, 32, "\n") . "\n");
        $printer->text(str_repeat("-", 32) . "\n");
        
        $printer->text("\n");
        $printer->text(str_repeat("-", 40) . "\n");
        
        // CABECERA DE PRODUCTOS DEVUELTOS
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text(str_pad("CANT", 4) . " " . str_pad("PRODUCTOS DEVUELTOS", 24) . " " . str_pad("TOTAL", 8, " ", STR_PAD_LEFT) . "\n");
        $printer->text(str_repeat("-", 40) . "\n");
        
        // DETALLE DE PRODUCTOS DEVUELTOS CON PRECIO FINAL
        foreach ($detallesConImpuestos as $detalle) {
            $cantidad = str_pad($detalle['cantidad'], 4);
            
            // TRUNCAR PARA LA PRIMERA LÍNEA
            $nombreTruncado = substr($detalle['nombre'], 0, 24);
            $nombreTruncado = str_pad($nombreTruncado, 24);
            
            $total = str_pad('$' . number_format($detalle['total'], 2), 8, " ", STR_PAD_LEFT);
            
            // Imprimir primera línea
            $printer->text("$cantidad $nombreTruncado $total\n");
            
            // Si el nombre completo es más largo, imprimir el resto
            $longitudNombre = strlen($detalle['nombre']);
            if ($longitudNombre > 24) {
                $inicio = 24;
                while ($inicio < $longitudNombre) {
                    $segmento = substr($detalle['nombre'], $inicio, 24);
                    if (!empty(trim($segmento))) {
                        $printer->text(str_pad("", 5) . $segmento . "\n");
                    }
                    $inicio += 24;
                }
            }
            
            // Precio unitario con impuestos (PRECIO FINAL)
            $precioUnit = '$' . number_format($detalle['precio_final'], 2) . " c/u";
            $printer->text(str_pad("", 5) . $precioUnit . "\n");
        }
        
        $printer->text(str_repeat("-", 32) . "\n");
        $printer->text("\n");
        
        // RESUMEN DE DEVOLUCIÓN CON PRECIOS FINALES
        $printer->setJustification(Printer::JUSTIFY_RIGHT);
        $printer->text("Subtotal: $" . number_format($subtotal, 2) . "\n");
        $printer->text("IVA {$tasaIva}%: $" . number_format($ivaCalculado, 2) . "\n");
        
        if ($esPersonaMoral && $isrCalculado > 0) {
            $printer->text("ISR {$tasaIsr}%: -$" . number_format($isrCalculado, 2) . "\n");
        }
        
        $printer->text(str_repeat("-", 32) . "\n");
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("TOTAL DEVUELTO:\n");
        $printer->setJustification(Printer::JUSTIFY_RIGHT);
        $printer->text(str_pad('$' . number_format($devolucion->total, 2), 32, " ", STR_PAD_LEFT) . "\n");
        
        $printer->text("\n");
        $printer->text(str_repeat("=", 32) . "\n");
        
        // CONTROL DE SALDOS ACTUALIZADO
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("CONTROL DE SALDOS\n");
        $printer->text(str_repeat("-", 40) . "\n");
        
        // Calcular saldos
        $saldoAnterior = $devolucion->notaAbono->saldo_actual - $devolucion->total;
        
        $printer->text("Saldo anterior:  $" . number_format($saldoAnterior, 2) . "\n");
        $printer->text("+ Devolución:  +$" . number_format($devolucion->total, 2) . "\n");
        $printer->text("Nuevo saldo:  $" . number_format($devolucion->notaAbono->saldo_actual, 2) . "\n");
        $printer->text(str_repeat("-", 32) . "\n");
        
        // PIE DE TICKET
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("DEVOLUCIÓN REGISTRADA\n");
        
        $printer->text("\n");
        $printer->text("Usuario: " . ($devolucion->usuario->name ?? 'Sistema') . "\n");
        $printer->text("Devuelto: " . date('d/m/Y H:i:s') . "\n");
        
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("\n");
        $printer->text("\n");
        
        // FIRMAS
        $printer->text(str_repeat("─", 32) . "\n");
        $printer->text("     FIRMA DEL CLIENTE          \n");
        $printer->text("                                \n");
        
        $printer->text("\n");
        // FINALIZAR IMPRESIÓN
        $printer->feed(3);
        $printer->cut();
        $printer->close();
        
  
        
        return true;
        
    } catch (\Exception $e) {
      
        return false;
    }
}
//Historial Devoluciones
public function obtenerHistorialDevoluciones($ventaId)
{
    try {
        $venta = VentaNotaAbono::findOrFail($ventaId);
        
        // Obtener devoluciones de esta venta
        $devoluciones = DevolucionVenta::where('venta_id', $ventaId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($devolucion) {
                return [
                    'id' => $devolucion->id,
                    'folio_devolucion' => $devolucion->folio_devolucion,
                    'motivo' => $devolucion->motivo,
                    'motivo_texto' => $devolucion->motivo_texto,
                    'total' => $devolucion->total,
                    'observaciones' => $devolucion->observaciones,
                    'created_at' => $devolucion->created_at,
                    'detalles' => json_decode($devolucion->detalles, true) ?? []
                ];
            });
        
        // Obtener resumen por producto
        $resumenProductos = $venta->detalles->map(function($detalle) {
            return [
                'id' => $detalle->id,
                'nombre' => $detalle->nombre_producto,
                'cantidad_comprada' => $detalle->cantidad,
                'cantidad_devuelta' => $detalle->cantidad_devuelta ?? 0,
                'disponible' => $detalle->cantidad - ($detalle->cantidad_devuelta ?? 0)
            ];
        });
        
        return response()->json([
            'success' => true,
            'devoluciones' => $devoluciones,
            'resumen_productos' => $resumenProductos,
            'venta' => [
                'ticket' => $venta->ticket,
                'estado' => $venta->estado,
                'total_devoluciones' => $devoluciones->count()
            ],
            'message' => $devoluciones->isEmpty() ? 'No hay devoluciones registradas' : 'Historial cargado correctamente'
        ]);
        
    } catch (\Exception $e) {
      
        return response()->json([
            'success' => false,
            'message' => 'Error al obtener historial: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Obtener detalle de una devolución específica
 */
public function obtenerDetalleDevolucion($devolucionId)
{
    try {
        $devolucion = DevolucionVenta::findOrFail($devolucionId);
        
        return response()->json([
            'success' => true,
            'devolucion' => [
                'id' => $devolucion->id,
                'folio_devolucion' => $devolucion->folio_devolucion,
                'motivo' => $devolucion->motivo,
                'observaciones' => $devolucion->observaciones,
                'total' => $devolucion->total,
                'created_at' => $devolucion->created_at,
                'detalles' => $devolucion->detalles
            ]
        ]);
        
    } catch (\Exception $e) {
    
        return response()->json([
            'success' => false,
            'message' => 'Error al obtener detalle: ' . $e->getMessage()
        ], 500);
    }
}
//
  
/**
 * Procesar el cierre de nota de abono
 */
private function procesarCierreNota($notaAbono, $venta, $cierreDatos, $pagoMixto = null)
{
    try {
        
        
        // ==============================================
        // 1. NORMALIZAR DATOS (convertir null a string vacío)
        // ==============================================
        $datosNormalizados = [
            'tipo_pago_id' => $cierreDatos['tipo_pago_id'] ?? null,
            'efectivo' => $cierreDatos['efectivo'] ?? 0,
            'transferencia' => $cierreDatos['transferencia'] ?? 0,
            'referencia' => isset($cierreDatos['referencia']) && is_string($cierreDatos['referencia']) 
                ? $cierreDatos['referencia'] 
                : (string) ($cierreDatos['referencia'] ?? ''),
            'observaciones' => isset($cierreDatos['observaciones']) && is_string($cierreDatos['observaciones']) 
                ? $cierreDatos['observaciones'] 
                : (string) ($cierreDatos['observaciones'] ?? '')
        ];
       
        
        // ==============================================
        // 2. VALIDAR DATOS NORMALIZADOS
        // ==============================================
        $validator = validator($datosNormalizados, [
            'tipo_pago_id' => 'required|exists:tipo_pagos,id',
            'efectivo' => 'sometimes|numeric|min:0',
            'transferencia' => 'sometimes|numeric|min:0',
            'referencia' => 'nullable|string|max:100', // ← nullable, no required
            'observaciones' => 'nullable|string|max:500' // ← nullable, no required
        ]);
        
        if ($validator->fails()) {
            
            throw new \Illuminate\Validation\ValidationException($validator);
        }
        
        $validated = $validator->validated();
        
        // ==============================================
        // 3. DETERMINAR MONTOS (usar pago mixto si existe)
        // ==============================================
        $montoEfectivo = $validated['efectivo'];
        $montoTransferencia = $validated['transferencia'];
        
        // Si viene de pago mixto, usar esos valores
        if ($pagoMixto && isset($pagoMixto['pago_efectivo'])) {
            // Determinar si es efectivo o transferencia según tipo_pago_id
            $tipoPago = TipoPago::find($validated['tipo_pago_id']);
            $esEfectivo = $tipoPago && stripos($tipoPago->nombre, 'transferencia') === false;
            
            if ($esEfectivo) {
                $montoEfectivo = $pagoMixto['pago_efectivo'];
                $montoTransferencia = 0;
            } else {
                $montoEfectivo = 0;
                $montoTransferencia = $pagoMixto['pago_efectivo'];
            }
            
            
        }
       

        
        // ==============================================
        // 4. CREAR REGISTRO EN CIERRE_NOTA_ABONOS
        // ==============================================
        $cierre = CierreNotaAbono::create([
            'nota_abono_id' => $notaAbono->id,
            'venta_id' => $venta->id,
            'tipo_pago_id' => $validated['tipo_pago_id'],
            'monto_saldo_usado' => max(
                0,
                $venta->saldo_antes - $venta->saldo_despues
            ),
            'monto_efectivo' => $montoEfectivo,
            'monto_transferencia' => $montoTransferencia,
            'saldo_anterior' => $venta->saldo_antes,
            'saldo_despues' => max(0, $venta->saldo_despues),
            'referencia_pago' => $validated['referencia'] ?? null,
            'observaciones' => $validated['observaciones'] ?? ($pagoMixto['observaciones'] ?? null),
            'cerrado_por' => auth()->id()
        ]);
        
        // ==============================================
        // 5. ACTUALIZAR NOTA DE ABONO
        // ==============================================
        $notaAbono->update([
            'estado' => 'finalizada',
            'estado_cierre' => 'finalizada',
            'fecha_cierre' => now(),
            'venta_cierre_id' => $venta->id,
            'saldo_actual' => 0
        ]);
        
        
        // ==============================================
        // 6. REGISTRAR MOVIMIENTO DE CIERRE
        // ==============================================
        MovimientoAbono::create([
            'nota_abono_id' => $notaAbono->id,
            'tipo' => 'cierre',
            'monto' => $cierre->monto_efectivo + $cierre->monto_transferencia,
            'saldo_anterior' => $venta->saldo_antes,
            'nuevo_saldo' => 0,
            'concepto' => 'Cierre definitivo de nota de abono',
            'venta_id' => $venta->id,
            'observaciones' => sprintf(
                'Cierre por venta %s. %s: $%s%s',
                $venta->ticket,
                $cierre->monto_efectivo > 0 ? 'Efectivo' : 'Transferencia',
                number_format($cierre->monto_efectivo + $cierre->monto_transferencia, 2),
                $validated['observaciones'] ? ' | ' . $validated['observaciones'] : ''
            ),
            'user_id' => auth()->id()
        ]);
        
        
        
        return $cierre;
        
    } catch (\Illuminate\Validation\ValidationException $e) {

        throw $e;
        
    } catch (\Exception $e) {
    
        throw $e;
    }
}
    

}
