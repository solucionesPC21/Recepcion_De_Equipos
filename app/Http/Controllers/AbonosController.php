<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Facades\Auth;
use App\Models\Abono;
use App\Models\VentaAbono;
use App\Models\VentaAbonoDetalle;
use App\Models\NombreConcepto;
use App\Models\Estado;
use App\Models\Clientes;
use App\Models\TipoPago;
use Illuminate\Http\Request;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\CapabilityProfile;
use Illuminate\Support\Facades\Log;
use App\Models\Impresoras;
use PDF;


class AbonosController extends Controller
{
    
    /**
     * Display a listing of the resource.
     */

    public function index()
    {
        $ventas = VentaAbono::with(['cliente', 'estado', 'abonos', 'detalles.concepto'])
                    ->withCount(['abonos', 'detalles']) // ← Puedes agregar múltiples counts
                    ->orderBy('fecha_venta', 'desc')
                    ->paginate(10);
        
        $clientes = Clientes::orderBy('nombre')->get();
        $conceptos = NombreConcepto::orderBy('nombre')->get();
        $tiposPago = TipoPago::orderBy('tipoPago')->get();
        
        return view('abonos.abono', compact('ventas', 'clientes', 'conceptos', 'tiposPago'));
    }
    /**
 * Store a newly created cliente.
 */
public function storeCliente(Request $request)
{
    $request->validate([
        'nombre' => 'required|string|max:255',
        'telefono' => 'nullable|string|max:20'
    ]);

    // Verificar si ya existe un cliente con el mismo nombre (ignorando mayúsculas/minúsculas)
    $clienteExistente = Clientes::whereRaw('LOWER(nombre) = ?', [strtolower($request->nombre)])->first();

    if ($clienteExistente) {
        return response()->json([
            'success' => false,
            'message' => 'El cliente ya está registrado, no se puede volver a registrar.'
        ], 409); // Código 409 = conflicto
    }

    // Crear nuevo cliente si no existe
    $cliente = Clientes::create([
        'nombre' => $request->nombre,
        'telefono' => $request->telefono
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Cliente registrado correctamente',
        'cliente' => [
            'id' => $cliente->id,
            'nombre' => $cliente->nombre
        ]
    ]);
}

    /**
     * Funcion Para Buscar productos que han realizado algun abono.
     */
public function buscarProductos(Request $request)
{
    $termino = $request->get('q');

    if (!$termino || strlen($termino) < 2) {
        return response()->json([]);
    }

    $productos = NombreConcepto::whereIn('id_categoria', [2])
        ->where(function ($q) use ($termino) {
            $q->where('nombre', 'LIKE', "%{$termino}%")
              ->orWhere('marca', 'LIKE', "%{$termino}%")
              ->orWhere('codigo_barra', 'LIKE', "%{$termino}%")
              ->orWhere('modelo', 'LIKE', "%{$termino}%");
        })
        ->limit(10)
        ->get([
            'id',
            'nombre',
            'marca',
            'modelo',
            'precio',
            'cantidad'
        ])
        ->map(function ($producto) {
            return [
                'id'        => $producto->id,
                'nombre'    => $producto->nombre,
                'marca'     => $producto->marca,
                'modelo'    => $producto->modelo,
                'precio'    => $producto->precio,
                'cantidad'  => $producto->cantidad,
                'agotado'   => $producto->cantidad <= 0
            ];
        });

    return response()->json($productos);
}

    
    /**
     * Store a newly created abono.
     */
    public function store(Request $request)
    {
        $request->validate([
            'venta_id' => 'required|exists:venta_abono,id',
            'monto' => 'required|numeric|min:0.01'
        ]);

        return DB::transaction(function () use ($request) {
            $venta = VentaAbono::findOrFail($request->venta_id);
            
            if ($request->monto > $venta->saldo_restante) {
                return response()->json([
                    'success' => false,
                    'message' => 'El monto excede el saldo restante'
                ], 422);
            }

              // Guardar saldo anterior para el PDF
            $saldo_anterior = $venta->saldo_restante;

            $abono = Abono::create([
                'venta_abono_id' => $request->venta_id,
                'monto' => $request->monto,
                'tipo_pago_id' => $request->tipo_pago_id1 
            ]);

            $venta->saldo_restante -= $request->monto;
            
            if ($venta->saldo_restante <= 0) {
                $venta->estado_id = 2; // Estado "Pagado"
            }
            
            $venta->save();
            // Generar y guardar PDF del abono
            $this->generarPDFAbono($venta, $abono, $saldo_anterior);

              // Imprimir ticket del abono
            $this->printAbonoTicket($venta, $abono, false);

            return response()->json([
                'success' => true,
                'message' => 'Abono registrado correctamente',
                'saldo_restante' => $venta->saldo_restante
            ]);
        });
    }

    /**
     * Get abonos for a specific venta.
     */
    public function getAbonos($ventaId)
    {
         $abonos = Abono::with(['tipoPago']) // Carga la relación tipoPago
                ->where('venta_abono_id', $ventaId)
                ->orderBy('fecha_abono', 'desc')
                ->get()
                ->makeHidden('pdf_abonos'); 
                
        return response()->json($abonos);
    }

    /**
     * Remove the specified abono.
     */
    public function destroy(Abono $abono)
    {
        // Verificar si el usuario es admin
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para esta acción'
            ], 403); // Código HTTP 403 Forbidden
        }

        return DB::transaction(function () use ($abono) {
            $venta = $abono->venta;
            $venta->saldo_restante += $abono->monto;
            
            if ($venta->saldo_restante > 0 && $venta->estado_id == 2) {
                $venta->estado_id = 1;
            }
            
            $venta->save();
            $abono->delete();

            return response()->json([
                'success' => true,
                'message' => 'Abono eliminado correctamente'
            ]);
        });
    }

    /**
     * Store a new venta a crédito with multiple productos.
     */
    public function storeVenta(Request $request)
    {
        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'productos' => 'required|array|min:1',
            'productos.*.nombre' => 'required|string|max:255',
            'productos.*.precio' => 'required|numeric|min:0',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.id_concepto' => 'nullable|exists:nombreconcepto,id',
            'abono_inicial' => 'nullable|numeric|min:0',
            'productos.*.id_concepto' => 'nullable|exists:nombreconcepto,id',

        ]);

        return DB::transaction(function () use ($request) {
   

            // 🔹 Crear venta base
            $venta = VentaAbono::create([
                'id_cliente' => $request->cliente_id,
                'total' => 0,
                'saldo_restante' => 0,
                'usuario' => Auth::user()->nombre,
                'estado_id' => 1, // Pendiente
                'fecha_venta' => now()
            ]);

            // 🔹 Inicializar total
            $total = 0;

            foreach ($request->productos as $producto) {

                $precio = floatval($producto['precio']);
                $cantidad = intval($producto['cantidad']);
                $subtotal = $precio * $cantidad;

                // ✅ Acumular total CORRECTAMENTE
                $total += $subtotal;

                // 🔹 Producto de inventario
                if (!empty($producto['id_concepto'])) {

                    $concepto = NombreConcepto::lockForUpdate()->findOrFail($producto['id_concepto']);

                    // Solo descontar stock para categorías 1 y 2
                    if (in_array($concepto->id_categoria, [1, 2])) {

                        if ($cantidad > $concepto->cantidad) {
                            throw new \Exception("Stock insuficiente para {$concepto->nombre}");
                        }

                        // Descontar stock
                        $concepto->cantidad -= $cantidad;
                        $concepto->save();
                    }

                } else {
                    // 🔹 Producto manual (categoría 3)
                    $concepto = NombreConcepto::firstOrCreate(
                        ['nombre' => $producto['nombre']],
                        [
                            'precio' => $precio,
                            'id_categoria' => 3
                        ]
                    );
                }

                // 🔹 Guardar detalle
                VentaAbonoDetalle::create([
                    'venta_abono_id' => $venta->id,
                    'nombreconcepto_id' => $concepto->id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'subtotal' => $subtotal
                ]);
            }

            // 🔹 Abono inicial
            $abonoInicial = floatval($request->abono_inicial ?? 0);

            // 🔹 Calcular saldo
            $saldoRestante = $total - $abonoInicial;
            if ($saldoRestante < 0) {
                $saldoRestante = 0;
            }

            // 🔹 Actualizar venta
            $venta->update([
                'total' => $total,
                'saldo_restante' => $saldoRestante,
                'estado_id' => $saldoRestante == 0 ? 2 : 1 // Pagado / Pendiente
            ]);

            // 🔹 Registrar abono inicial
            $abono = null;

            if ($abonoInicial > 0) {
                $abono = Abono::create([
                    'venta_abono_id' => $venta->id,
                    'tipo_pago_id' => $request->tipo_pago_id,
                    'monto' => $abonoInicial,
                    'fecha_abono' => now()
                ]);

                $this->generarPDFAbono($venta, $abono, $total);
            }
            //prueba
            // 🔹 RECARGAR relaciones OBLIGATORIO
            $venta->load(['detalles.concepto', 'cliente']);

            // 🔹 PDF de la venta
            $isNewVenta = true;
            // 🔹 RECARGAR relaciones OBLIGATORIO
            $venta->load(['detalles.concepto', 'cliente']);
            $pdf = PDF::loadView('abonos.ventaAbonoTicket', [
                'venta' => $venta,
                'abono' => $abono,
                'isNewVenta' => $isNewVenta
            ]);

            // Ticket 80mm
            $lineHeight = 12;
            $sectionSpacing = 8;

            $totalLinesCount = 8 + count($venta->detalles) + 8;
            $calculatedHeight = ($totalLinesCount * $lineHeight) + ($sectionSpacing * 6);

            $finalHeight = max(250, min($calculatedHeight, 1500));

            $pdf->setPaper([0, 0, 220, $finalHeight], 'portrait');
            $pdf->setOption('dpi', 72);
            $pdf->setOption('margin-top', 0);
            $pdf->setOption('margin-bottom', 0);
            $pdf->setOption('margin-left', 0);
            $pdf->setOption('margin-right', 0);
            $pdf->setOption('disable-smart-shrinking', true);

            $venta->pdf_ventaAbono = $pdf->output();
            $venta->save();

            // 🔹 Imprimir ticket
            $this->printAbonoTicket($venta, $abono, true);

            return response()->json([
                'success' => true,
                'message' => 'Venta a abonos registrada correctamente',
                'venta' => [
                    'id' => $venta->id,
                    'total' => $venta->total,
                    'saldo_restante' => $venta->saldo_restante,
                    'cliente' => $venta->cliente->nombre,
                    'fecha_venta' => $venta->fecha_venta,
                ]
            ]);
        });
    }

    //buscarCliente
    public function buscarClientes(Request $request)
    {
        $termino = $request->input('termino');
        
        $clientes = Clientes::where('nombre', 'LIKE', "%{$termino}%")
                    ->limit(7)
                    ->get(['id', 'nombre']);
        
        return response()->json($clientes);
    }

  
    public function printAbonoTicket($venta, $abono = null, $isNewVenta = false)
{
    try {       
        // Configuración de la impresora
       /* $nombreImpresora = "Bixolon";
        $connector = new WindowsPrintConnector($nombreImpresora);
        $printer = new Printer($connector);*/
        $impresora = Impresoras::where('tipo', 'termica')
            ->where('activa', 1)
            ->first();

        if (!$impresora) {
            return redirect()->back()
                ->with('error', 'No hay una impresora térmica activa configurada.');
        }
        $connector= new WindowsPrintConnector($impresora->nombre_sistema);
        $printer= new Printer($connector);
        
        // Configurar tamaño de fuente pequeño pero legible
        $printer->setTextSize(1, 1);
        
        // Encabezado del ticket
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT);
        $printer->text("SOLUCIONES PC\n");
        $printer->selectPrintMode();
        $printer->text("RFC: ZARE881013I12\n");
        $printer->text("Tel: 6161362976\n");
        $printer->text("----------------------------\n");
        
        // Título
        $printer->setEmphasis(true);
        $printer->text($isNewVenta ? "RECIBO DE VENTA\n" : "RECIBO DE ABONO\n");
        $printer->setEmphasis(false);
        $printer->text("----------------------------\n");
        
        // Datos del cliente
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("Cliente: " . $venta->cliente->nombre . "\n");
        $printer->text("Fecha: " . ($venta->fecha_venta ? $venta->fecha_venta->format('d/m/Y H:i') : 'N/A') . "\n");
        $printer->text("Venta: #" . $venta->id . "\n");
        
        if ($abono && !$isNewVenta) {
            $printer->text("Abono: #" . $abono->id . "\n");
        }
        
        $printer->text("----------------------------\n");
        
        // Encabezado de columnas (optimizado para 80mm)
        $printer->setEmphasis(true);
        $printer->text("Cant  Descripción       Precio   Importe\n");
        $printer->setEmphasis(false);
        $printer->text("----------------------------\n");
        
        // Detalles de productos
        foreach ($venta->detalles as $detalle) {
            $cantidad = str_pad($detalle->cantidad, 4);
            
            // Descripción truncada inteligentemente
           $concepto = $detalle->concepto;

            // 🔹 Nombre completo (nombre + marca + modelo)
            $nombreCompleto = trim(
                $concepto->nombre
                . (!empty($concepto->marca) ? ' - ' . $concepto->marca : '')
                . (!empty($concepto->modelo) ? ' ' . $concepto->modelo : '')
            );

            // 🔹 Primera línea (máx 16 chars)
            $descripcionLinea1 = substr($nombreCompleto, 0, 16);
            $descripcionLinea1 = str_pad($descripcionLinea1, 16);

            
            $precio = str_pad("$" . number_format($detalle->precio_unitario, 2), 8);
            $importe = "$" . number_format($detalle->subtotal, 2);
            
            $printer->text($cantidad . " " . $descripcionLinea1 . " " . $precio . " " . $importe . "\n");
            
            // Si la descripción es muy larga, mostrar el resto en línea adicional
            if (strlen($nombreCompleto) > 16) {
                $restoDescripcion = substr($nombreCompleto, 16);

                // Cortar en líneas de 24 chars para 80mm
                $lineas = str_split($restoDescripcion, 24);

                foreach ($lineas as $linea) {
                    $printer->text("     " . $linea . "\n");
                }
            }

        }
        
        $printer->text("----------------------------\n");
        
        // Totales (formato compacto)
        $printer->setEmphasis(true);
        $printer->text("TOTAL VENTA: $" . number_format($venta->total, 2) . "\n");
        $printer->setEmphasis(false);
        
        if ($abono) {
            if ($isNewVenta) {
                $printer->text("Abono inicial: $" . number_format($abono->monto, 2) . "\n");
            } else {
                $saldo_anterior = $venta->saldo_restante + $abono->monto;
                $printer->text("Saldo anterior: $" . number_format($saldo_anterior, 2) . "\n");
                $printer->setEmphasis(true);
                $printer->text("Monto abonado: $" . number_format($abono->monto, 2) . "\n");
                $printer->setEmphasis(false);
            }
            
            if ($venta->saldo_restante <= 0) {
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_EMPHASIZED);
                $printer->text("¡PAGADO!\n");
                $printer->selectPrintMode();
                $printer->setJustification(Printer::JUSTIFY_LEFT);
            } else {
                $printer->setEmphasis(true);
                $printer->text("Saldo restante: $" . number_format($venta->saldo_restante, 2) . "\n");
                $printer->setEmphasis(false);
            }
            
            if ($abono->tipoPago) {
                $printer->text("Tipo Pago: " . $abono->tipoPago->tipoPago . "\n");
            }
        }
        
        $printer->text("----------------------------\n");
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("¡Gracias por su preferencia!\n");
        
        // Cortar papel
        $printer->cut();
        
        // Cerrar conexión
        $printer->close();
        
        return true;
    } catch (\Exception $e) {
        \Log::error("Error al imprimir ticket: " . $e->getMessage());
        return false;
    }
}

//
  public function reimprimirTicket($id)
{
    try {
        // Buscar el ABONO específico con sus relaciones
        $abono = Abono::with([
            'venta.cliente', 
            'venta.detalles.concepto',
            'tipoPago'
        ])->findOrFail($id);

        // Calcular el saldo anterior JUSTO ANTES de este abono
        $saldoAnterior = $abono->venta->total - $abono->venta->abonos()
            ->where('fecha_abono', '<', $abono->fecha_abono)
            ->sum('monto');

        // Calcular el saldo restante JUSTO DESPUÉS de este abono
        $saldoRestante = $saldoAnterior - $abono->monto;

        // Pasar los saldos calculados a la función de impresión
        $this->printAbonoTicketConSaldosEspecificos(
            $abono->venta, 
            $abono, 
            false,
            $saldoAnterior,
            $saldoRestante
        );
        
        return response()->json([
            'success' => true,
            'message' => 'Ticket de abono reimpreso correctamente'
        ]);
        
    } catch (\Exception $e) {
        \Log::error("Error al reimprimir ticket de abono: " . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error al reimprimir el ticket de abono: ' . $e->getMessage()
        ], 500);
    }
}
//funcion reimprimir ticket
public function printAbonoTicketConSaldosEspecificos($venta, $abono = null, $isNewVenta = false, $saldoAnterior = null, $saldoRestante = null)
{
    try {       
        // Configuración de la impresora
        /*$nombreImpresora = "Bixolon";
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


        //
        // Configurar tamaño de fuente pequeño pero legible
        $printer->setTextSize(1, 1);
        
        // Encabezado del ticket
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT);
        $printer->text("SOLUCIONES PC\n");
        $printer->selectPrintMode();
        $printer->text("RFC: ZARE881013I12\n");
        $printer->text("Tel: 6161362976\n");
        $printer->text("----------------------------\n");
        
        // Título
        $printer->setEmphasis(true);
        $printer->text($isNewVenta ? "RECIBO DE VENTA\n" : "RECIBO DE ABONO\n");
        $printer->setEmphasis(false);
        $printer->text("----------------------------\n");
        
        // Datos del cliente
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("Cliente: " . $venta->cliente->nombre . "\n");
        $printer->text("Fecha: " . ($abono->fecha_abono ? $abono->fecha_abono->format('d/m/Y H:i') : 'N/A') . "\n");
        $printer->text("Venta: #" . $venta->id . "\n");
        
        if ($abono && !$isNewVenta) {
            $printer->text("Abono: #" . $abono->id . "\n");
        }
        
        $printer->text("----------------------------\n");
        
        // Encabezado de columnas (optimizado para 80mm)
        $printer->setEmphasis(true);
        $printer->text("Cant  Descripción       Precio   Importe\n");
        $printer->setEmphasis(false);
        $printer->text("----------------------------\n");
        
        // Detalles de productos
        // Detalles de productos
        foreach ($venta->detalles as $detalle) {
            $cantidad = str_pad($detalle->cantidad, 4);
            
            // Descripción truncada inteligentemente
           $concepto = $detalle->concepto;

            // 🔹 Nombre completo (nombre + marca + modelo)
            $nombreCompleto = trim(
                $concepto->nombre
                . (!empty($concepto->marca) ? ' - ' . $concepto->marca : '')
                . (!empty($concepto->modelo) ? ' ' . $concepto->modelo : '')
            );

            // 🔹 Primera línea (máx 16 chars)
            $descripcionLinea1 = substr($nombreCompleto, 0, 16);
            $descripcionLinea1 = str_pad($descripcionLinea1, 16);

            
            $precio = str_pad("$" . number_format($detalle->precio_unitario, 2), 8);
            $importe = "$" . number_format($detalle->subtotal, 2);
            
            $printer->text($cantidad . " " . $descripcionLinea1 . " " . $precio . " " . $importe . "\n");
            
            // Si la descripción es muy larga, mostrar el resto en línea adicional
            if (strlen($nombreCompleto) > 16) {
                $restoDescripcion = substr($nombreCompleto, 16);

                // Cortar en líneas de 24 chars para 80mm
                $lineas = str_split($restoDescripcion, 24);

                foreach ($lineas as $linea) {
                    $printer->text("     " . $linea . "\n");
                }
            }

        }
        
        $printer->text("----------------------------\n");
        
        // Totales (formato compacto)
        $printer->setEmphasis(true);
        $printer->text("TOTAL VENTA: $" . number_format($venta->total, 2) . "\n");
        $printer->setEmphasis(false);
        
        if ($abono) {
            if ($isNewVenta) {
                $printer->text("Abono inicial: $" . number_format($abono->monto, 2) . "\n");
            } else {
                // USAR el saldo anterior calculado específicamente para este abono
                $printer->text("Saldo anterior: $" . number_format($saldoAnterior, 2) . "\n");
                $printer->setEmphasis(true);
                $printer->text("Monto abonado: $" . number_format($abono->monto, 2) . "\n");
                $printer->setEmphasis(false);
            }
            
            if ($saldoRestante <= 0) {
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_EMPHASIZED);
                $printer->text("¡PAGADO!\n");
                $printer->selectPrintMode();
                $printer->setJustification(Printer::JUSTIFY_LEFT);
            } else {
                $printer->setEmphasis(true);
                // USAR el saldo restante calculado específicamente para este abono
                $printer->text("Saldo restante: $" . number_format($saldoRestante, 2) . "\n");
                $printer->setEmphasis(false);
            }
            
            if ($abono->tipoPago) {
                $printer->text("Tipo Pago: " . $abono->tipoPago->tipoPago . "\n");
            }
        }
        
        $printer->text("----------------------------\n");
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("¡Gracias por su preferencia!\n");
        
        // Cortar papel
        $printer->cut();
        
        // Cerrar conexión
        $printer->close();
        
        return true;
        
    } catch (\Exception $e) {
        \Log::error("Error al imprimir ticket: " . $e->getMessage());
        return false;
    }
}
//generar pdf
   private function generarPDFAbono($venta, $abono, $saldo_anterior)
    {
        try {
            // Verificar que los datos necesarios estén presentes
            if (!$abono->fecha_abono) {
                $abono->fecha_abono = now();
            }
            //
            
            //
            $pdf = PDF::loadView('abonos.AbonosTicket', [
                'venta' => $venta,
                'abono' => $abono,
                'saldo_anterior' => $saldo_anterior
            ]);
            
            // Calcular el alto exacto basado en el contenido
            $lineHeight = 12; // Altura aproximada por línea de texto
            $sectionSpacing = 8; // Espaciado entre secciones
            
            // Calcular número de líneas aproximadas
            $headerLines = 8; // Encabezado + título + información cliente
            $productLines = count($venta->detalles);
            $totalLines = 8; // Totales + información pago + pie
            
            $totalLinesCount = $headerLines + $productLines + $totalLines;
            $calculatedHeight = ($totalLinesCount * $lineHeight) + ($sectionSpacing * 6);
            
            // Asegurar un mínimo y máximo razonable
            $minHeight = 250;
            $maxHeight = 1500;
            $finalHeight = max($minHeight, min($calculatedHeight, $maxHeight));
            
            // Configuración para tickets (80mm de ancho = 220px)
            $pdf->setPaper([0, 0, 220, $finalHeight], 'portrait');
            $pdf->setOption('dpi', 72);
            $pdf->setOption('margin-top', 0);
            $pdf->setOption('margin-bottom', 0);
            $pdf->setOption('margin-left', 0);
            $pdf->setOption('margin-right', 0);
            $pdf->setOption('disable-smart-shrinking', true);
            
            // Guardar PDF
            $abono->pdf_abonos = $pdf->output();
            $abono->save();
            
            return true;
        } catch (\Exception $e) {
            \Log::error("Error al generar PDF de abono: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Descargar/ver PDF del abono
     */
    public function verPDFAbono($id)
    {
        $abono = Abono::findOrFail($id);

        if (!$abono->pdf_abonos) {
            abort(404, 'PDF no encontrado');
        }

        return response($abono->pdf_abonos)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="abono_'.$abono->id.'.pdf"');
    }

    /*
        Ver pdf de Venta Abono (al registrar la primera venta de abono)
    */

    public function verPDF($id)
    {
        $venta = VentaAbono::findOrFail($id);

        if (!$venta->pdf_ventaAbono) {
            abort(404, 'PDF no encontrado');
        }

        return response($venta->pdf_ventaAbono)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="recibo_'.$venta->id.'.pdf"');
    }


    // modificar saldo restante del cliente
    /*public function actualizarSaldo(Request $request, $id)
    {
        $request->validate([
            'saldo_restante' => 'required|numeric|min:0'
        ]);

        try {
            $venta = VentaAbono::findOrFail($id);
            
            // Actualizar el saldo restante
            $venta->saldo_restante = $request->saldo_restante;
            
            // Actualizar el estado según el nuevo saldo
            if ($request->saldo_restante <= 0) {
                $venta->estado_id = 2; // Pagado
            } else {
                $venta->estado_id = 1; // Pendiente
            }
            
            $venta->save();

            return response()->json([
                'success' => true,
                'message' => 'Saldo actualizado correctamente',
                'nuevo_saldo' => $venta->saldo_restante,
                'nuevo_estado' => $venta->estado->nombre
            ]);

        } catch (\Exception $e) {
            \Log::error("Error actualizando saldo de venta {$id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el saldo: ' . $e->getMessage()
            ], 500);
        }
    }
  */
    // Método en el controlador actualiza total del abono
    public function actualizarTotal(Request $request, $id)
    {
        $request->validate([
            'total' => 'required|numeric|min:0',
            'saldo_restante' => 'required|numeric|min:0'
        ]);

        try {
            $venta = VentaAbono::findOrFail($id);
            
            // Actualizar el total y saldo restante
            $venta->total = $request->total;
            $venta->saldo_restante = $request->saldo_restante;
            
            // Actualizar el estado según el nuevo saldo
            if ($request->saldo_restante <= 0) {
                $venta->estado_id = 2; // Pagado
            } else {
                $venta->estado_id = 1; // Pendiente
            }
            
            $venta->save();

            return response()->json([
                'success' => true,
                'message' => 'Total actualizado correctamente',
                'nuevo_total' => $venta->total,
                'nuevo_saldo' => $venta->saldo_restante,
                'nuevo_estado' => $venta->estado->nombre
            ]);

        } catch (\Exception $e) {
            \Log::error("Error actualizando total de venta {$id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el total: ' . $e->getMessage()
            ], 500);
        }
    }

    //Funcion para borrar ventas de abonos:
    /**
 * Eliminar venta de abono (solo si no tiene abonos)
 */
public function destroyVenta($id)
{
    // 🔐 Verificar permisos
    if (!auth()->user()->isAdmin()) {
        return response()->json([
            'success' => false,
            'message' => 'No tienes permisos para esta acción'
        ], 403);
    }

    try {
        $venta = VentaAbono::with(['detalles.concepto'])
            ->withCount('abonos')
            ->findOrFail($id);

        // ⛔ No permitir eliminar si tiene abonos
        if ($venta->abonos_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la venta porque ya tiene abonos registrados'
            ], 422);
        }

        DB::transaction(function () use ($venta) {

            // 🔁 REGRESAR INVENTARIO (cantidad)
            foreach ($venta->detalles as $detalle) {

                $concepto = NombreConcepto::lockForUpdate()
                    ->find($detalle->nombreconcepto_id);

                if (
                    $concepto &&
                    in_array($concepto->id_categoria, [1, 2])
                ) {
                    $concepto->cantidad += $detalle->cantidad;
                    $concepto->save();
                }
            }

            // 🗑️ Eliminar detalles
            $venta->detalles()->delete();

            // 🗑️ Eliminar venta
            $venta->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Venta eliminada correctamente'
        ]);

    } catch (\Exception $e) {

        \Log::error("Error eliminando venta {$id}", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Error al eliminar la venta'
        ], 500);
    }
}


    /**
 * Obtener detalles de una venta para edición
 */
   /**
 * Obtener detalles de una venta para edición (versión simplificada)
 */
/**
 * Obtener detalles de una venta para edición
 */
/**
 * Obtener detalles de una venta para edición (VERSIÓN CORREGIDA)
 */
/**
 * Obtener detalles de una venta para edición (VERSIÓN CORREGIDA)
 */
public function getVentaDetalles($id)
{
    try {
        $venta = VentaAbono::with(['detalles.concepto', 'abonos', 'cliente'])->findOrFail($id);
        
        $detalles = $venta->detalles->map(function($detalle) {
            return [
                'id' => $detalle->id,
                'nombre' => $detalle->concepto->nombre,
                'marca' => $detalle->concepto->marca,      // ← AÑADIR
                'modelo' => $detalle->concepto->modelo,    // ← AÑADIR
                'precio' => (float) $detalle->precio_unitario,
                'cantidad' => (int) $detalle->cantidad,
                'subtotal' => (float) $detalle->subtotal,
                'id_concepto' => $detalle->nombreconcepto_id, // ← AÑADIR ESTO
                'concepto_id' => $detalle->nombreconcepto_id  // ← Y ESTO por compatibilidad
            ];
        });

        return response()->json([
            'success' => true,
            'venta' => [
                'id' => $venta->id,
                'total' => (float) $venta->total, // ← Asegurar que sea número
                'saldo_restante' => (float) $venta->saldo_restante,
                'cliente' => [
                    'nombre' => $venta->cliente->nombre
                ]
            ],
            'detalles' => $detalles,
            'total_abonado' => (float) $venta->abonos->sum('monto'),
            'saldo_actual' => (float) $venta->saldo_restante
        ], 200, [], JSON_UNESCAPED_UNICODE);

    } catch (\Exception $e) {
        \Log::error("Error obteniendo detalles de venta {$id}: " . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error al cargar los detalles de la venta'
        ], 500, [], JSON_UNESCAPED_UNICODE);
    }
}
    /**
     * Actualizar productos de una venta
    */
public function actualizarProductos(Request $request, $id)
{
    

    $request->validate([
        'productos' => 'required|array',
        'productos.*.nombre' => 'required|string|max:255',
        'productos.*.precio' => 'required|numeric|min:0',
        'productos.*.cantidad' => 'required|integer|min:1'
    ]);

    if (!auth()->user()->isAdmin()) {
        Log::warning('Usuario sin permisos', [
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'No tienes permisos para esta acción'
        ], 403);
    }

    try {
        DB::beginTransaction();

        $venta = VentaAbono::with([
            'abonos',
            'detalles.concepto'
        ])->findOrFail($id);

      

        $totalAbonado = $venta->abonos->sum('monto');

        // Indexar detalles actuales
        $detallesActuales = $venta->detalles->keyBy('nombreconcepto_id');

        $nuevoTotal = 0;
        $conceptosProcesados = [];

        foreach ($request->productos as $producto) {

    
    // 🔹 EXTRAER NOMBRE BASE si viene nombre completo
    $nombreBase = $producto['nombre'];
    
    // Si el nombre contiene " - ", extraer solo la primera parte
    if (strpos($nombreBase, ' - ') !== false) {
        $partes = explode(' - ', $nombreBase, 2);
        $nombreBase = trim($partes[0]);
        
    }
    
    // 🔹 VERIFICAR SI VIENE id_concepto
    if (!empty($producto['id_concepto']) && is_numeric($producto['id_concepto'])) {
        // Producto del inventario
        $concepto = NombreConcepto::lockForUpdate()
            ->findOrFail($producto['id_concepto']);
            
        // 🔴 Asegurar que el nombre coincida
        if ($concepto->nombre !== $nombreBase) {
            Log::warning('Nombre no coincide', [
                'nombre_bd' => $concepto->nombre,
                'nombre_request' => $nombreBase
            ]);
        }
        
    } else {
        // Producto manual - buscar por nombre base
        $concepto = NombreConcepto::where('nombre', $nombreBase)
            ->first();
            
        if (!$concepto) {
            // Crear nuevo producto manual
            $concepto = NombreConcepto::create([
                'nombre' => $nombreBase,
                'precio' => $producto['precio'],
                'id_categoria' => 3,
                'cantidad' => 0
            ]);
        }
    }
    

            $cantidadNueva = (int) $producto['cantidad'];
            $cantidadAnterior = $detallesActuales[$concepto->id]->cantidad ?? 0;

            $diferencia = $cantidadNueva - $cantidadAnterior;

            // AJUSTE DE INVENTARIO
            if ($concepto->id_categoria == 2 && $diferencia != 0) {

                if ($diferencia > 0 && $concepto->cantidad < $diferencia) {
                    Log::error('Stock insuficiente', [
                        'concepto_id' => $concepto->id,
                        'stock' => $concepto->cantidad,
                        'requerido' => $diferencia
                    ]);

                    throw new \Exception(
                        "Stock insuficiente para {$concepto->nombre}"
                    );
                }


                // diferencia positiva = resta | negativa = suma
                $concepto->decrement('cantidad', $diferencia);
            }

            VentaAbonoDetalle::updateOrCreate(
                [
                    'venta_abono_id' => $venta->id,
                    'nombreconcepto_id' => $concepto->id
                ],
                [
                    'cantidad' => $cantidadNueva,
                    'precio_unitario' => $producto['precio']
                ]
            );


            $nuevoTotal += $cantidadNueva * $producto['precio'];
            $conceptosProcesados[] = $concepto->id;
        }

        // Productos eliminados
        foreach ($detallesActuales as $detalle) {
            if (!in_array($detalle->nombreconcepto_id, $conceptosProcesados)) {


                if ($detalle->concepto->id_categoria == 2) {
                    $detalle->concepto->increment('cantidad', $detalle->cantidad);
                }

                $detalle->delete();
            }
        }

        $nuevoSaldo = $nuevoTotal - $totalAbonado;

        $venta->update([
            'total' => $nuevoTotal,
            'saldo_restante' => max(0, $nuevoSaldo),
            'estado_id' => $nuevoSaldo <= 0 ? 2 : 1
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Productos actualizados correctamente',
            'nuevo_total' => $nuevoTotal,
            'nuevo_saldo' => $venta->saldo_restante,
            'nuevo_estado' => $venta->estado->nombre
        ]);

    } catch (\Exception $e) {
        DB::rollBack();

        Log::error('ERROR actualizarProductos', [
            'venta_id' => $id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 422);
    }
}


}
    

