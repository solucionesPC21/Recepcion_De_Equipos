<?php

namespace App\Http\Controllers;

use App\Models\Recibo;
use Illuminate\Http\Request;
use App\Models\TipoEquipo;
use Carbon\Carbon;
use App\Models\Impresoras;
use PDF; 

class ReciboController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {       
            $recibos = Recibo::whereIn('id_estado', [1, 5])
                ->orderBy('created_at', 'desc')
                ->paginate(5);

            $totalRecibos = Recibo::whereIn('id_estado', [1, 5])->count();

            return view('recibos.recibos', compact('recibos', 'totalRecibos'));

    }

    //recibos cancelados
    public function rechazado(Request $request)
    {
        $recibos = Recibo::where('id_estado', 4)
        ->orderBy('created_at', 'desc') // Ordenar por created_at en orden descendente
        ->paginate(5);
        $totalRecibos = Recibo::where('id_estado', 4)->count(); // Obtenemos el total de tipos de equipo con estado 1
        return view('recibos.recibos-rechazados', compact('recibos', 'totalRecibos'));
    }

    //marcar como completado en el apartado de generar tickets
        public function actualizarEstado(Request $request) {
        $request->validate([
            'id_recibo' => 'required|exists:recibos,id',
            'id_estado' => 'required|exists:estados,id'
        ]);

        try {
            $recibo = Recibo::find($request->id_recibo);
            $recibo->id_estado = $request->id_estado;
            $recibo->save();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el estado: ' . $e->getMessage()
            ], 500);
        }
    }

    //marcar sin cobrar
    public function marcarSinCobrar($id)
    {
        $recibo = Recibo::find($id);

        if (!$recibo) {
            return response()->json([
                'error' => 'Recibo no encontrado.'
            ], 404);
        }

        $recibo->id_estado = 3;
        $recibo->fechaReparacion = Carbon::now();
        $recibo->save();

        return response()->json([
            'message' => 'El recibo ha sido marcado como completado sin cobrar.'
        ]);
    }

    
   
    public function pdfImprimir($id)
    {
        $recibo = Recibo::find($id);
    
        if (!$recibo) {
            // Manejo de error si el recibo no se encuentra
            abort(404, 'Recibo no encontrado');
        }

          // Buscar impresora activa de tipo HOJAS
        $impresora = Impresoras::where('tipo', 'hojas')
        ->where('activa', 1)
        ->first();

          if (!$impresora) {
            return redirect('/home')
            ->with('error', 'No hay una impresora de hojas activa configurada.');
        }
    
        // Contar la cantidad de equipos en el recibo
        $tipoEquipos = $recibo->tipoEquipo;
        $cantidadTiposEquipo = $tipoEquipos->count();
    
        $pdf = PDF::loadView('recibos.pdf', ['recibo' => $recibo, 'cantidadTiposEquipo' => $cantidadTiposEquipo])
            ->setPaper(array(0,0,612.00,792.00), 'portrait');
    
        // Ruta donde se guardará el PDF temporalmente
        $rutaPDF = public_path('pdfs/unico.pdf');
    
        try {
            // Guardar el PDF sobreescribiendo el existente
            $pdf->save($rutaPDF);
    
            // Nombre de la impresora específica a la que deseas enviar la impresión
    
            // Ruta al ejecutable SumatraPDF
            //$sumatraPath = 'C:\\Users\\Soluciones\\AppData\\Local\\SumatraPDF\\SumatraPDF.exe';
            $sumatraPath = $impresora->sumatra_path;

             // Nombre de impresora tomado desde BD
            $nombreImpresora = $impresora->nombre_sistema;
    
            // Comando para imprimir el PDF
            $comando = "\"$sumatraPath\" -print-to \"$nombreImpresora\" \"$rutaPDF\"";
    
            // Ejecutar el comando y obtener la salida
            $resultado = shell_exec($comando);
    
            // Si hay más de 3 tipos de equipo, imprimir el PDF nuevamente
            if ($cantidadTiposEquipo > 3) {
                $resultado = shell_exec($comando);
            }
    
            // Si el comando de impresión no arroja errores, redirige con un mensaje de éxito
            return redirect('/home')->with('success', 'Equipo registrado con éxito y la impresión se realizó correctamente.');
        } catch (\Exception $e) {
            // En caso de error, redirige con un mensaje de error
            return redirect('/home')->with('error', 'Error al imprimir el recibo: ' . $e->getMessage());
        }
    }
    //en revision
    public function marcarEnRevision($id)
    {
        $recibo = Recibo::findOrFail($id);

        if ($recibo->id_estado == 5) {
            return response()->json([
                'success' => false,
                'message' => 'Este recibo ya está en revisión'
            ]);
        }

        $recibo->id_estado = 5;
        $recibo->save();

        return response()->json(['success' => true]);
    }

    
    
 
        public function pdf($id)
        {
            
            $recibo = Recibo::find($id);

            if (!$recibo) {
                abort(404, 'Recibo no encontrado');
            }

            // Si existe el PDF en la base de datos, lo usamos
            if (!empty($recibo->pdf_blob)) {
                // Retornar el PDF desde el BLOB
                return response($recibo->pdf_blob, 200)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'inline; filename="recibo-'.$recibo->id.'.pdf"');
            }
    }

   


 // return $pdf->download($tipoEquipo->cliente->nombre . '-' . date('d-m-Y', strtotime($tipoEquipo->fecha)) . '.pdf');

   
    /**
     * Show the form for creating a new resource.
     
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     
    public function show(Recibo $recibo)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     
    public function edit(Recibo $recibo)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     
    public function update(Request $request, Recibo $recibo)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     
    public function destroy(Recibo $recibo)
    {
        //
    }
    */
}
