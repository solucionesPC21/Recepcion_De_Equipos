<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\NombreConcepto;
use App\Models\Ticket;
use App\Models\Concepto;
use App\Models\Clientes;
use App\Models\TipoPago;
use Carbon\Carbon;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\CapabilityProfile;
use Illuminate\Http\Request;
use App\Models\Impresoras;
use PDF;

class VentaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {    
        // Retornar la vista con los datos filtrados
        // Obtener todos los conceptos de la base de datos
         $nombreConceptos = NombreConcepto::all();

        // Retornar la vista con los datos
        return view('cobro.cobro', compact('nombreConceptos'));
        
    }
    /**
     *  buscar Producto
     */

  public function buscarProducto(Request $request)
{

    
    $search = trim($request->input('query'));
    
    // REGLA: Si el query tiene solo números, buscar SOLO por código de barras EXACTO
    if (preg_match('/^\d+$/', $search)) {
        $productoExacto = NombreConcepto::select([
                'id',
                'nombre',
                'descripcion',
                'precio',
                'cantidad',
                'modelo',
                'marca',
                'codigo_barra'
            ])
            ->where('id_categoria', 2)
            ->where('codigo_barra', '=', $search) // EXACTO incluyendo ceros
            ->first();
        
        return response()->json($productoExacto ? [$productoExacto] : []);
    }
    
    // Si contiene letras o otros caracteres, buscar en otros campos
    $productos = NombreConcepto::select([
            'id',
            'nombre',
            'descripcion',
            'precio',
            'cantidad',
            'modelo',
            'marca',
            'codigo_barra'
        ])
        ->where('id_categoria', 2)
        ->where(function($query) use ($search) {
            $query->where('nombre', 'LIKE', "%$search%")
                  ->orWhere('modelo', 'LIKE', "%$search%")
                  ->orWhere('marca', 'LIKE', "%$search%");
        })
        ->get();

    
    return response()->json($productos);
}

     

    /**
     * Crear Cliente
     */
    public function crearCliente(Request $request)
{
    try {
        $request->validate([
            'nombre' => 'required|string|max:190|unique:clientes,nombre',
            'telefono' => 'nullable|string|max:15',
        ]);

        $cliente = Clientes::create([
            'nombre' => $request->nombre,
            'telefono' => $request->telefono,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cliente registrado correctamente.',
            'cliente' => $cliente,
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error de validación',
            'errors' => $e->errors(),
        ], 422);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al registrar el cliente',
        ], 500);
    }
}

    /**
     * Realizar Cobro (Generar Ticket)
     */
    public function realizarCobro(Request $request)
    {
       

    // Validar la solicitud
    $validated = $request->validate([
        'payment_method' => 'required|string|in:Efectivo,Transferencia', // Nombre del método
        'cart' => 'required|json',
        'client_name' => 'nullable|string',
        'client_phone' => 'nullable|string',
        'total' => 'required|numeric|min:0'
    ]);

    try {
         // Obtener el ID del tipo de pago
         $tipoPago = TipoPago::where('tipoPago', $validated['payment_method'])->first();
        
         if (!$tipoPago) {
             throw new \Exception("Tipo de pago no encontrado");
         }
        // Decodificar el carrito para verificación
        $cart = json_decode($validated['cart'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Formato de carrito inválido');
        }

        // Verificar consistencia del total
        $calculatedTotal = array_reduce($cart, function($sum, $item) {
            return $sum + ($item['price'] * $item['quantity']);
        }, 0);

        if (abs($calculatedTotal - $validated['total']) > 0.01) {
            throw new \Exception('El total no coincide con los productos del carrito');
        }

        // Crear o encontrar cliente
         // Manejar cliente (solo si se proporcionó nombre)
         $clienteId = null;
         if (!empty($validated['client_name'])) {
             $cliente = Clientes::firstOrCreate(
                 ['nombre' => $validated['client_name']],
                 ['telefono' => $validated['client_phone'] ?? null]
             );
             $clienteId = $cliente->id;
         }
 

        // Crear ticket
        $ticket = new Ticket();
        $ticket->id_tipoPago = $tipoPago->id; // Usar el ID, no el nombre
        $ticket->fecha = Carbon::now();
        $ticket->usuario = Auth::user()->nombre;
        $ticket->total = $validated['total'];
        $ticket->id_cliente = $clienteId; // Puede ser null
        $ticket->estado_id=3;
        $ticket->save();

         // Procesar cada concepto del carrito
         // Procesar cada concepto del carrito
        foreach ($request->concepto as $key => $idConcepto) {
            // Buscar el producto en inventario
            $producto = NombreConcepto::where('id', $idConcepto)->firstOrFail();
            
            $cantidadVendida = $request->cantidad[$key];
            
            // Validar stock disponible
            if ($producto->cantidad < $cantidadVendida) {
                throw new \Exception("Stock insuficiente para '{$nombreConcepto}'. Disponible: {$producto->cantidad}, Solicitado: {$cantidadVendida}");
            }
            
            
            // Descontar del inventario
            $producto->cantidad -= $cantidadVendida;
            $producto->save();

            // Registrar el concepto en el ticket
            $concepto = new Concepto();
            $concepto->cantidad = $cantidadVendida;
            $concepto->total = $request->precio_unitario[$key] * $cantidadVendida;
            $concepto->id_ticket = $ticket->id;
            $concepto->id_nombreConcepto = $producto->id;
            $concepto->save();
        }

       /* return response()->json([
            'success' => true,
            'message' => 'Venta registrada exitosamente',
            'ticket_id' => $ticket->id
        ]);
        */

         // 🔄 Recargar relaciones necesarias
        $ticket->load(['tipoPago', 'concepto.nombreConcepto']);
        $conceptos = $ticket->concepto;


         // 🖨️ Generar PDF y guardarlo en BLOB
        $pdf = PDF::loadView('pagos.ticket', [
            'ticket' => $ticket,
            'conceptos' => $conceptos,
        ])->setPaper([0, 0, 360, 792.00], 'portrait');

        $ticket->pdf_blob = $pdf->output();
        $ticket->save();

        return $this->imprimir($ticket->id);

    } catch (\Exception $e) {
       
        Log::error('Error en realizarCobro: ' . $e->getMessage(), [
            'exception' => $e,
            'request' => $request->all()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Error al procesar el ticket',
            'error' => $e->getMessage()
        ], 500);
    }
    }

    public function imprimir($ticketId)
    {
        try {
             // 1. Obtener impresora térmica activa
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

            $ticket = Ticket::findOrFail($ticketId);
            $concepto = $ticket->concepto;


            $printer->setJustification(Printer::JUSTIFY_CENTER); //JUSTIFICA AL CENTRO EL TEXTO
            $printer->text("Soluciones PC\n");
            $printer->text("RFC: ZARE881013I12\n");
            $printer->text("Telefono: 6161362976\n");
            
            $printer->text("\n");
            $printer->text("\n");
            $printer->setJustification(Printer::JUSTIFY_RIGHT);
            $printer->text("Fecha: ".  date('d/m/Y', strtotime($ticket->fecha)));
            

            $printer->text("\n");
            $printer->text("\n");
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            if ($ticket->cliente && $ticket->cliente->nombre) {
                $printer->text("Cliente: " . $ticket->cliente->nombre . "\n");
            }
            $printer->text("\n\n");

            $printer->setJustification(Printer::JUSTIFY_CENTER); //JUSTIFICA AL CENTRO EL TEXTO
            $cantidadText = str_pad("Cant", 2);
            $conceptoText = str_pad("Concepto", 17);
            $precioText = str_pad("Precio", 6);
            $totalText = str_pad("SubTotal", 8);
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text("$cantidadText  $conceptoText   $precioText  $totalText");
            $printer->text("\n\n");

            foreach ($concepto as $conceptos) {

                   // Concatenar nombre + modelo + marca_producto_id
                   $nombreCompleto = $conceptos->nombreConcepto->nombre;

                   if ($conceptos->nombreConcepto->marca) {
                       $nombreCompleto .= " " . $conceptos->nombreConcepto->marca;
                   }
    
                   if (!empty($conceptos->nombreConcepto->modelo)) {
                       $nombreCompleto .= " " . $conceptos->nombreConcepto->modelo;
                   }
                   
                   // Agregar la descripción si existe
                   // Luego si hay descripción, añadirla en nueva línea
                   if (!empty($conceptos->nombreConcepto->descripcion)) {
                        $nombreCompleto .= " - " . $conceptos->nombreConcepto->descripcion;
                    }


                // Asegura que cada columna tenga la longitud deseada y esté centrada
                $cantidad = str_pad($conceptos->cantidad, 4, ' ');
                $precio = '$' . str_pad($conceptos->nombreConcepto->precio, 5, ' ');
                $total = '$' . str_pad($conceptos->total, 5, ' ');
                // Divide el concepto en varias líneas si es demasiado largo
                $conceptoTexto = wordwrap($nombreCompleto, 18, "\n", true);
                $lineasConcepto = explode("\n", $conceptoTexto);
            
                // Imprime cada línea del concepto con las columnas correspondientes
                foreach ($lineasConcepto as $indice => $linea) {
                    $cantidadImp = ($indice === 0) ? $cantidad : str_repeat(' ', strlen($cantidad));
                    $precioImp = ($indice === 0) ? $precio : str_repeat(' ', strlen($precio));
                    $totalImp = ($indice === 0) ? $total : str_repeat(' ', strlen($total));
                
                    // Si es la primera línea del concepto, imprime todas las columnas
                    if ($indice === 0) {
                        $conceptoImp = str_pad($linea, 18, ' ');
                        $printer->text("$cantidadImp   $conceptoImp   $precioImp   $totalImp");
                    } else {
                        // Si es una línea subsiguiente del concepto, imprime todas las columnas pero con el mismo relleno que la primera línea
                        $conceptoImp = str_pad($linea, 18, ' ');
                        $printer->text("$cantidadImp   $conceptoImp   $precioImp   $totalImp");
                    }
                }
                $printer->text("\n");
            }

            $printer->text("\n");
            $printer->setJustification(Printer::JUSTIFY_RIGHT);
            $printer->text("Total: " . '$' . number_format($ticket->total, 2));

            $printer->text("\n\n");
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text("Pago: " . $ticket->tipoPago->tipoPago);
            $printer->text("\n\n");
            $printer->text("Cobrado por: " . $ticket->usuario. "\n\n");

            $testStr = $ticket->cliente ? "Cliente: " . $ticket->cliente->nombre : "SolucionesPC";

            $printer->qrCode($testStr, Printer::QR_ECLEVEL_L, 16); // Aquí 10 es el tamaño del QR (10 módulos)

            $printer->feed();
            $printer->cut();
            $printer->close();

            return response()->json(['success' => true, 'message' => 'Venta e impresión completadas']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
