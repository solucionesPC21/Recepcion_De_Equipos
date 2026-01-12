<?php

namespace App\Http\Controllers;

use App\Models\Recibo;
use Illuminate\Http\Request;
use App\Models\TipoEquipo;
use App\Models\TipoPago;
use App\Models\Categoria;
use PDF;

class TicketController extends Controller
{
     /**
     * Display a listing of the resource.
     */
    public function index()
    {       
            $categorias = Categoria::whereIn('id', [1, 2])->get();
            $pagos = TipoPago::all(); 
            $recibos = Recibo::where('id_estado', 2)
                        ->orderBy('fechaReparacion', 'DESC')
                        ->paginate(6);
            $totalRecibos = Recibo::where('id_estado', 2)->count(); 
             return view('ticket.recibos', compact('recibos','pagos','totalRecibos','categorias'));
    }


    public function pdf($id)
    {
        $recibo = Recibo::find($id);
        $tipoEquipos = $recibo->tipoEquipo;
        $cantidadTiposEquipo = $tipoEquipos->count();
        if (!$recibo) {
            // Manejo de error si el tipo de equipo no se encuentra
            abort(404, 'Tipo de equipo no encontrado');
        }
        
        // Definir el tamaño personalizado de la página (mitad de carta)
        
        $pdf = PDF::loadView('ticket.pdf', ['recibo' => $recibo,'cantidadTiposEquipo'=>$cantidadTiposEquipo])->setPaper(array(0,0,612.00,792.00), 'portrait');
        
        return $pdf->stream();
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
