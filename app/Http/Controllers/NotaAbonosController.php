<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Regimen; // Asegúrate de importar el modelo
use App\Models\ClienteAbono;
use App\Models\NotaAbono;
use App\Models\MovimientoAbono;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use PDF;


class NotaAbonosController extends Controller
{
     public function index()
    {
        $regimenes = Regimen::all();

        $clientes = ClienteAbono::with('regimen')
            ->orderBy('id', 'desc')
            ->paginate(10); // Carga la relación y pagina

        return view('notaAbonos.notaAbonos', compact('regimenes', 'clientes'));
    }


      /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            // Validación
            $validated = $request->validate([
                'cliente_id' => 'required|exists:clientes_abonos,id',
                'monto_abono' => 'required|numeric|min:0.01',
                'fecha_abono' => 'required|date',
                'observaciones' => 'nullable|string|max:500'
            ]);

            // Obtener cliente
            $cliente = ClienteAbono::findOrFail($request->cliente_id);

            // Crear la nota de abono
            $notaAbono = NotaAbono::create([
                'folio' => NotaAbono::generarFolio(),
                'cliente_id' => $request->cliente_id,
                'abono_inicial' => $request->monto_abono,
                'saldo_actual' => $request->monto_abono,
                'estado' => 'activa',
                'fecha_apertura' => $request->fecha_abono,
                'observaciones' => $request->observaciones
            ]);

            // Registrar movimiento
            MovimientoAbono::create([
                'nota_abono_id' => $notaAbono->id,
                'tipo' => 'abono',
                'monto' => $request->monto_abono,
                'saldo_anterior' => 0,
                'nuevo_saldo' => $request->monto_abono,
                'concepto' => 'Abono inicial',
                'observaciones' => $request->observaciones,
                'user_id' => auth()->id()
            ]);

            // Actualizar saldo global del cliente (si usas triggers, esto se hace automáticamente)
            $cliente->saldo_global += $request->monto_abono;
            $cliente->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Nota de abono creada exitosamente',
                'nota_abono' => $notaAbono,
                'folio' => $notaAbono->folio
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la nota de abono: ' . $e->getMessage()
            ], 500);
        }
    }

     /**
     * Mostrar una nota de abono específica (para editar)
     */
    public function show($id)
    {
        try {
            $notaAbono = NotaAbono::with(['cliente', 'movimientos'])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'nota_abono' => $notaAbono
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Nota de abono no encontrada'
            ], 404);
        }
    }

    /**
     * Actualizar una nota de abono específica con ajustes
     */
   public function update(Request $request, $id)
{
    try {
        DB::beginTransaction();

        $notaAbono = NotaAbono::findOrFail($id);

        // Verificar que la nota esté activa
        if ($notaAbono->estado !== 'activa') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede editar una nota de abono cerrada'
            ], 422);
        }

        $tipoOperacion = $request->input('tipo_operacion', 'editar');
        $montoAjuste = $request->input('monto_ajuste', 0);
        $conceptoAjuste = $request->input('concepto_ajuste', '');

        // Validaciones según el tipo de operación
        if ($tipoOperacion === 'sumar' || $tipoOperacion === 'restar') {
            $validated = $request->validate([
                'monto_ajuste' => 'required|numeric|min:0.01',
                'concepto_ajuste' => 'required|string|max:255',
                'fecha_abono' => 'required|date',
                'observaciones' => 'nullable|string|max:500'
            ]);

            // Validar que no se reste más del saldo disponible
            if ($tipoOperacion === 'restar' && $montoAjuste > $notaAbono->saldo_actual) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede restar más del saldo disponible. Saldo actual: $' . number_format($notaAbono->saldo_actual, 2)
                ], 422);
            }

        } else {
            $validated = $request->validate([
                'abono_inicial' => 'required|numeric|min:0.01',
                'fecha_abono' => 'required|date',
                'observaciones' => 'nullable|string|max:500'
            ]);
        }

        $cliente = $notaAbono->cliente;
        $saldoAnterior = $notaAbono->saldo_actual;
        $nuevoSaldo = $saldoAnterior;

        // Procesar según el tipo de operación
        if ($tipoOperacion === 'sumar') {
            // Sumar al saldo
            $nuevoSaldo = $saldoAnterior + $montoAjuste;
            
            // Actualizar saldo global del cliente
            $cliente->saldo_global += $montoAjuste;
            $cliente->save();

            // Registrar movimiento de ajuste positivo (usando 'abono' que está permitido)
            MovimientoAbono::create([
                'nota_abono_id' => $notaAbono->id,
                'tipo' => 'abono', // Usar 'abono' que está en el ENUM
                'monto' => $montoAjuste,
                'saldo_anterior' => $saldoAnterior,
                'nuevo_saldo' => $nuevoSaldo,
                'concepto' => $this->getConceptoAjuste($conceptoAjuste),
                'observaciones' => $request->observaciones ?: 'Abono adicional del cliente',
                'user_id' => auth()->id()
            ]);

        } elseif ($tipoOperacion === 'restar') {
            // Restar del saldo
            $nuevoSaldo = $saldoAnterior - $montoAjuste;
            
            // Actualizar saldo global del cliente
            $cliente->saldo_global -= $montoAjuste;
            $cliente->save();

            // Registrar movimiento de ajuste negativo (usando 'ajuste' que está permitido)
            MovimientoAbono::create([
                'nota_abono_id' => $notaAbono->id,
                'tipo' => 'ajuste', // Usar 'ajuste' que está en el ENUM
                'monto' => $montoAjuste,
                'saldo_anterior' => $saldoAnterior,
                'nuevo_saldo' => $nuevoSaldo,
                'concepto' => $this->getConceptoAjuste($conceptoAjuste),
                'observaciones' => $request->observaciones ?: 'Ajuste negativo de saldo',
                'user_id' => auth()->id()
            ]);

        } else {
            // Solo editar información básica
            $abonoInicialAnterior = $notaAbono->abono_inicial;
            $nuevoAbonoInicial = $request->abono_inicial;
            
            // Actualizar la nota
            $notaAbono->update([
                'abono_inicial' => $nuevoAbonoInicial,
                'fecha_apertura' => $request->fecha_abono,
                'observaciones' => $request->observaciones
            ]);

            // Si cambió el abono inicial, registrar movimiento como 'ajuste'
            if ($abonoInicialAnterior != $nuevoAbonoInicial) {
                $diferencia = $nuevoAbonoInicial - $abonoInicialAnterior;
                
                MovimientoAbono::create([
                    'nota_abono_id' => $notaAbono->id,
                    'tipo' => 'ajuste', // Usar 'ajuste' para correcciones
                    'monto' => abs($diferencia),
                    'saldo_anterior' => $saldoAnterior,
                    'nuevo_saldo' => $nuevoSaldo,
                    'concepto' => 'Corrección de abono inicial',
                    'observaciones' => $request->observaciones ?: 'Corrección manual del abono inicial',
                    'user_id' => auth()->id()
                ]);
            }
        }

        // Actualizar saldo actual de la nota
        $notaAbono->saldo_actual = $nuevoSaldo;
        $notaAbono->save();

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => $this->getMensajeExito($tipoOperacion, $montoAjuste),
            'nota_abono' => $notaAbono
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Error de validación',
            'errors' => $e->errors()
        ], 422);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Error al actualizar la nota de abono: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Helper para obtener concepto formateado
 */
private function getConceptoAjuste($concepto)
{
    $conceptos = [
        'abono_adicional' => 'Abono Adicional del Cliente',
        'correccion_error' => 'Corrección por Error',
        'ajuste_administrativo' => 'Ajuste Administrativo',
        'otros' => 'Otros Ajustes'
    ];
    
    return $conceptos[$concepto] ?? 'Ajuste de Saldo';
}

/**
 * Helper para mensajes de éxito
 */
    private function getMensajeExito($tipoOperacion, $monto)
    {
        $mensajes = [
            'sumar' => 'Se agregaron $' . number_format($monto, 2) . ' al saldo exitosamente',
            'restar' => 'Se restaron $' . number_format($monto, 2) . ' del saldo exitosamente',
            'editar' => 'Información de la nota actualizada exitosamente'
        ];
        
        return $mensajes[$tipoOperacion] ?? 'Operación completada exitosamente';
    }

    /**
 * Obtener el historial de movimientos de una nota de abono
 */
   public function historial(Request $request, $id)
{
    try {
        

        $notaAbono = NotaAbono::with(['cliente'])->find($id);
        
        if (!$notaAbono) {
            return response()->json([
                'success' => false,
                'message' => 'Nota de abono no encontrada'
            ], 404);
        }

        // Query para movimientos con filtros
        $query = MovimientoNotaAbono::where('nota_abono_id', $id)
                    ->with(['user'])
                    ->orderBy('fecha_movimiento', 'desc') // Ordenar por fecha del movimiento
                    ->orderBy('created_at', 'desc'); // Y luego por creación

        // Aplicar filtros si existen
        if ($request->has('tipo') && $request->tipo) {
            $query->where('tipo', $request->tipo);
        }

        // Filtrar por fecha del movimiento (no por created_at)
        if ($request->has('fecha_desde') && $request->fecha_desde) {
            $query->whereDate('fecha_movimiento', '>=', $request->fecha_desde);
        }

        if ($request->has('fecha_hasta') && $request->fecha_hasta) {
            $query->whereDate('fecha_movimiento', '<=', $request->fecha_hasta);
        }

        $movimientos = $query->get();

        return response()->json([
            'success' => true,
            'nota_abono' => $notaAbono,
            'movimientos' => $movimientos,
            'total_movimientos' => $movimientos->count(),
            'filtros_aplicados' => [ // Para debug
                'tipo' => $request->tipo,
                'fecha_desde' => $request->fecha_desde,
                'fecha_hasta' => $request->fecha_hasta
            ]
        ]);

    } catch (\Exception $e) {
        \Log::error('Error al obtener historial: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Error al obtener el historial: ' . $e->getMessage()
        ], 500);
    }
}

public function filtrarNotas(Request $request)
{
    try {
        $query = NotaAbono::with(['cliente']);
        
        // Aplicar filtros
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_apertura', '>=', $request->fecha_desde);
        }
        
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_apertura', '<=', $request->fecha_hasta);
        }
        
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        
        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }
        
        // Paginación - 4 registros por página
        $notas = $query->orderBy('fecha_apertura', 'desc')
                      ->paginate(4);

        return response()->json([
            'success' => true,
            'notas' => $notas->items(),
            'pagination' => [
                'current_page' => $notas->currentPage(),
                'last_page' => $notas->lastPage(),
                'per_page' => $notas->perPage(),
                'total' => $notas->total(),
                'from' => $notas->firstItem(),
                'to' => $notas->lastItem(),
            ]
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al filtrar notas: ' . $e->getMessage()
        ], 500);
    }
}

  public function filtrarMovimientos(Request $request, $notaAbonoId)
{
    try {

        // Verificar que la nota existe
        $notaAbono = NotaAbono::with(['cliente'])->find($notaAbonoId);
        
        if (!$notaAbono) {
            return response()->json([
                'success' => false,
                'message' => 'Nota de abono no encontrada'
            ], 404);
        }

        // Query para movimientos
        $query = MovimientoAbono::where('nota_abono_id', $notaAbonoId)
                ->with(['usuario']);

        // Aplicar filtros
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        // Paginación - 10 registros por página
        $perPage = 10;
        $page = $request->get('page', 1);
        
        $movimientos = $query->orderBy('created_at', 'desc')
                            ->paginate($perPage, ['*'], 'page', $page);



        return response()->json([
            'success' => true,
            'nota_abono' => [
                'id' => $notaAbono->id,
                'folio' => $notaAbono->folio,
                'cliente' => $notaAbono->cliente,
                'abono_inicial' => $notaAbono->abono_inicial,
                'saldo_actual' => $notaAbono->saldo_actual
            ],
            'movimientos' => $movimientos->items(),
            'pagination' => [
                'current_page' => $movimientos->currentPage(),
                'last_page' => $movimientos->lastPage(),
                'per_page' => $movimientos->perPage(),
                'total' => $movimientos->total(),
                'from' => $movimientos->firstItem(),
                'to' => $movimientos->lastItem(),
                'has_more_pages' => $movimientos->hasMorePages()
            ],
            'total_movimientos' => $movimientos->total()
        ]);

    } catch (\Exception $e) {
        \Log::error('ERROR EN FILTRADO MOVIMIENTOS: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error al filtrar movimientos: ' . $e->getMessage()
        ], 500);
    }
}
    /**
     * Cerrar una nota de abono
     */
    public function cerrar($id)
    {
        try {
            DB::beginTransaction();

            $notaAbono = NotaAbono::findOrFail($id);
            
            // Verificar que el saldo sea 0
            if ($notaAbono->saldo_actual != 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede cerrar la nota. El saldo actual debe ser $0.00'
                ], 422);
            }

            // Cerrar la nota
            $notaAbono->update([
                'estado' => 'cerrada',
                'fecha_cierre' => now()
            ]);

            // Registrar movimiento de cierre
            MovimientoAbono::create([
                'nota_abono_id' => $notaAbono->id,
                'tipo' => 'cierre',
                'monto' => 0,
                'saldo_anterior' => 0,
                'nuevo_saldo' => 0,
                'concepto' => 'Cierre de nota de abono',
                'observaciones' => 'Nota cerrada manualmente',
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
     * Obtener notas de abono de un cliente
     */
    public function getByCliente($clienteId)
    {
        try {
            $notasAbono = NotaAbono::where('cliente_id', $clienteId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'notas_abono' => $notasAbono
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las notas de abono'
            ], 500);
        }
    }

    /**
     * Buscar notas de abono con filtros
     */
   public function buscar(Request $request)
    {
        try {

            $query = NotaAbono::with(['cliente']); // Asegúrate de que 'cliente' sea el nombre correcto de la relación

            // Filtro por fechas
            if ($request->has('fecha_desde') && $request->fecha_desde) {
                $query->whereDate('fecha_apertura', '>=', $request->fecha_desde);
            }
            
            if ($request->has('fecha_hasta') && $request->fecha_hasta) {
                $query->whereDate('fecha_apertura', '<=', $request->fecha_hasta);
            }
            
            // Filtro por estado
            if ($request->has('estado') && $request->estado) {
                $query->where('estado', $request->estado);
            }
            
            // Filtro por cliente
            if ($request->has('cliente_id') && $request->cliente_id) {
                $query->where('cliente_id', $request->cliente_id);
            }
            
            $notas = $query->orderBy('created_at', 'desc')->get();

        
            
            // Para debug: verificar los datos que se envían
            foreach ($notas as $nota) {
                
            }

            return response()->json([
                'success' => true,
                'notas' => $notas,
                'total' => $notas->count(),
                'message' => 'Búsqueda completada exitosamente'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en búsqueda de notas: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al realizar la búsqueda: ' . $e->getMessage()
            ], 500);
        }
    }

    // Exportar historial de movimientos a PDF
public function exportarHistorialPDF(Request $request, $notaAbonoId)
{
    try {
        // Obtener la nota de abono con relaciones
        $notaAbono = NotaAbono::with(['cliente', 'movimientos.usuario', 'ventas'])
                             ->findOrFail($notaAbonoId);
        
        // Aplicar filtros si existen
        $query = MovimientoAbono::where('nota_abono_id', $notaAbonoId)
                               ->with(['usuario', 'venta'])
                               ->orderBy('created_at', 'desc');
        
        if ($request->has('tipo') && $request->tipo) {
            $query->where('tipo', $request->tipo);
        }
        
        if ($request->has('fecha_desde') && $request->fecha_desde) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }
        
        if ($request->has('fecha_hasta') && $request->fecha_hasta) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }
        
        $movimientos = $query->get();
        
        // ==============================================
        // CALCULAR COMPRAS VÁLIDAS - USANDO COLUMNA DEL MODELO
        // ==============================================
        // Usamos directamente la columna total_con_impuestos de NotaAbono
        // Esta columna ya debería tener solo las compras válidas (sin canceladas/totalmente devueltas)
        $totalComprasValidas = $notaAbono->total_con_impuestos ?? 0;
        
        // Para información adicional, calculamos las ventas inválidas
        $ventas = $notaAbono->ventas;
        $ventasInvalidas = $ventas->filter(function($venta) {
            return in_array($venta->estado, ['cancelada', 'totalmente_devuelta']);
        });
        $totalComprasInvalidas = $ventasInvalidas->sum('total');
        
        // ==============================================
        // CALCULAR RESUMEN CORREGIDO
        // ==============================================
        $resumen = [
            'total_movimientos' => $movimientos->count(),
            'total_abonos' => $movimientos->where('tipo', 'abono')->sum('monto'),
            'total_compras' => $totalComprasValidas, // ← DE total_con_impuestos
            'total_ajustes' => $movimientos->where('tipo', 'ajuste')->sum('monto'),
            'total_devoluciones' => $movimientos->where('tipo', 'devolucion')->sum('monto'),
            'total_cancelaciones' => $movimientos->where('tipo', 'cancelacion')->sum('monto'),
            'saldo_inicial' => $notaAbono->abono_inicial,
            'saldo_actual' => $notaAbono->saldo_actual,
            
            // Estadísticas adicionales (opcional)
            'ventas_completadas' => $ventas->where('estado', 'completada')->count(),
            'ventas_parciales' => $ventas->where('estado', 'parcialmente_devuelta')->count(),
            'ventas_canceladas' => $ventas->where('estado', 'cancelada')->count(),
            'ventas_devueltas' => $ventas->where('estado', 'totalmente_devuelta')->count(),
            'monto_compras_invalidas' => $totalComprasInvalidas, // Para referencia
            
            // Información de la columna (para verificación)
            'subtotal_acumulado' => $notaAbono->subtotal_acumulado ?? 0,
            'iva_calculado' => $notaAbono->iva_calculado ?? 0,
            'isr_calculado' => $notaAbono->isr_calculado ?? 0,
            'total_con_impuestos' => $notaAbono->total_con_impuestos ?? 0,
        ];
        
        // Datos para la vista
        $data = [
            'notaAbono' => $notaAbono,
            'movimientos' => $movimientos,
            'resumen' => $resumen,
            'filtros' => [
                'tipo' => $request->tipo,
                'fecha_desde' => $request->fecha_desde,
                'fecha_hasta' => $request->fecha_hasta
            ],
            'fecha_generacion' => now()->format('d/m/Y H:i:s'),
            'usuario_actual' => auth()->user()->name ?? 'Sistema'
        ];
        
        // Configurar el PDF
        $pdf = PDF::loadView('notaAbonos.historial-movimientos', $data);
        
        // Opciones del PDF
        $pdf->setPaper('A4', 'landscape');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'Helvetica'
        ]);
        
        // Nombre del archivo
        $filename = "historial-nota-{$notaAbono->folio}-" . date('Y-m-d') . ".pdf";
        
        // Retornar el PDF para descarga
        return $pdf->download($filename);
        
    } catch (\Exception $e) {
        Log::error('Error generando PDF de historial: ' . $e->getMessage());
        return back()->with('error', 'Error al generar PDF: ' . $e->getMessage());
    }
}
}
