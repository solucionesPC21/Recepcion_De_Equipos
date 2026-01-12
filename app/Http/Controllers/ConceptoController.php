<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Concepto;
use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\TipoEquipo;
use App\Models\NombreConcepto;
use Carbon\Carbon;
use App\Models\Recibo;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\CapabilityProfile;
use App\Models\Impresoras;
use PDF;
 

class ConceptoController extends Controller
{
    /**
     * Display a listing of the resource.
     
    public function index()
    {
     
    }

    /**
     * Show the form for creating a new resource.
     
    public function create()
    {
    }
    */
    
public function guardar(Request $request)
{
    $request->validate([
        'concepto_id.*' => 'nullable|numeric',
        'concepto.*' => 'required|string',
        'cantidad.*' => 'required|numeric|min:1',
        'precio_unitario.*' => 'required|numeric|min:0.01',
        'recibos_id' => 'required|numeric',
        'tipo_pago' => 'required',
        'categoria.*' => 'required|numeric|in:1,2',
    ]);

    $fechaActual = Carbon::now();
    $nombreUsuario = Auth::user()->nombre;

    // Crear el ticket
    $ticket = new Ticket();
    $ticket->id_recibo = $request->recibos_id;
    $ticket->id_tipoPago = $request->tipo_pago;
    $ticket->fecha = $fechaActual;
    $ticket->usuario = $nombreUsuario;
    $ticket->total = str_replace('$', '', $request->total_general);
    $ticket->save();

    // Procesar cada concepto
    foreach ($request->concepto as $key => $nombre) {
        $categoria = $request->categoria[$key];
        $precioForm = $request->precio_unitario[$key];
        $cantidad = $request->cantidad[$key];

        $nombreConcepto = null;

        // 1️⃣ Si viene un ID (seleccionado de sugerencias), usar el existente
        if (!empty($request->concepto_id[$key])) {
            $nombreConcepto = NombreConcepto::find($request->concepto_id[$key]);
        } 
        // 2️⃣ Si es servicio (categoría 1) y no tiene ID → buscar si ya existe
        elseif ($categoria == 1) {
            $nombreConcepto = NombreConcepto::whereRaw('LOWER(nombre) = ?', [strtolower($nombre)])
                ->where('id_categoria', 1)
                ->first();

            if (!$nombreConcepto) {
                // Si no existe, crearlo
                $nombreConcepto = NombreConcepto::create([
                    'nombre' => $nombre,
                    'precio' => $precioForm,
                    'id_categoria' => 1,
                    'cantidad' => null,
                ]);
            }
        } 
        // 3️⃣ Si es producto (categoría 2) sin ID, crearlo
        elseif ($categoria == 2) {
            $nombreConcepto = NombreConcepto::create([
                'nombre' => $nombre,
                'precio' => $precioForm,
                'id_categoria' => 2,
                'cantidad' => 0,
            ]);
        }

        // 🔄 Si el precio en BD es diferente al enviado → actualizarlo
        if ($nombreConcepto->precio != $precioForm) {
            $nombreConcepto->precio = $precioForm;
            $nombreConcepto->save();
        }

        // 🏪 Manejo de inventario para productos (categoría 2)
        if ($categoria == 2) {
            if ($nombreConcepto->cantidad < $cantidad) {
                // Si no hay suficiente stock, evita que se procese
                return back()->withErrors(['error' => 'Stock insuficiente para el producto: ' . $nombreConcepto->nombre]);
            }
            $nombreConcepto->decrement('cantidad', $cantidad);
        }

        // Crear concepto del ticket
        Concepto::create([
            'cantidad' => $cantidad,
            'total' => str_replace('$', '', $request->total[$key]),
            'id_ticket' => $ticket->id,
            'id_nombreConcepto' => $nombreConcepto->id,
        ]);
    }

    $ticket->load(['recibo.tipoEquipo.cliente.colonia', 'tipoPago', 'concepto.nombreConcepto']);
    $conceptos = $ticket->concepto; // si es null, será colección vacía


    $pdf = Pdf::loadView('completados.pdfTicket', [
        'ticket' => $ticket,
        'conceptos' => $conceptos,
    ])->setPaper([0, 0, 360, 602.00], 'portrait');


    $ticket->pdf_blob = $pdf->output();
    $ticket->save();

    return $this->imprimir($ticket->id);
}



    public function imprimir($ticketId)
    {
        try {
            /*$nombreImpresora="Bixolon";
            $connector= new WindowsPrintConnector($nombreImpresora);
            $printer= new Printer($connector);

          */
            $ticket = Ticket::findOrFail($ticketId);
            $concepto = $ticket->concepto;
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
            $printer->text("Cliente: " . $ticket->recibo->tipoEquipo[0]->cliente->nombre);
            $printer->text("\nColonia: ");
            if ($ticket->recibo->tipoEquipo[0]->cliente->colonia) {
                $printer->text($ticket->recibo->tipoEquipo[0]->cliente->colonia->colonia);
                $printer->text("\n\n");
            } else {
                $printer->text("\n\n");
            }
            $printer->setJustification(Printer::JUSTIFY_CENTER); //JUSTIFICA AL CENTRO EL TEXTO
            $cantidadText = str_pad("Cant", 2);
            $conceptoText = str_pad("Concepto", 17);
            $precioText = str_pad("Precio", 6);
            $totalText = str_pad("SubTotal", 8);
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text("$cantidadText  $conceptoText   $precioText  $totalText");
            $printer->text("\n\n");

            foreach ($concepto as $conceptos) {
                // Asegura que cada columna tenga la longitud deseada y esté centrada
                $cantidad = str_pad($conceptos->cantidad, 4, ' ');
                $precio = '$' . str_pad($conceptos->nombreConcepto->precio, 5, ' ');
                $total = '$' . str_pad($conceptos->total, 5, ' ');
                // Divide el concepto en varias líneas si es demasiado largo
                $conceptoTexto = wordwrap($conceptos->nombreConcepto->nombre, 18, "\n", true);
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

            $testStr = "Cliente: " . $ticket->recibo->tipoEquipo[0]->cliente->nombre;

            $printer->qrCode($testStr, Printer::QR_ECLEVEL_L, 16); // Aquí 10 es el tamaño del QR (10 módulos)

            $printer->feed();
            $printer->cut();
            $printer->close();
            /* Print a "Hello world" receipt" */
            
             // Actualizar el estado del recibo al que pertenece el ticket
             
            $recibo = $ticket->recibo;
             
            $ticket->estado_id=3;
            $ticket->save(); 

            if ($recibo) {
                $recibo->id_estado = 3;
               // $recibo->fechaReparacion = Carbon::now()->toDateString(); // Obtiene la fecha actual en formato 'YYYY-MM-DD'
                $recibo->save();
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Error al imprimir: ' . $e->getMessage()
        ], 500);
        }
    
    }


    /**
     * 
     * Store a newly created resource in storage.
     
    public function store(Request $request)
    { 
    }


    /**
     * Display the specified resource.
     
    public function show(Concepto $concepto)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     
    public function edit(Concepto $concepto)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     
    public function update(Request $request, Concepto $concepto)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     
    public function destroy(Concepto $concepto)
    {
        //
    }
        */
}
