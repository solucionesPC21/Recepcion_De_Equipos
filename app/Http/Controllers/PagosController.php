<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;


class PagosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
{
    $perPage = $request->input('perPage', 10); // por defecto 10

    $query = Ticket::where('estado_id', 3)
                ->orderBy('id', 'desc');

    // Filtro por rango de fechas
    if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
        $query->whereBetween('fecha', [
            $request->fecha_inicio,
            $request->fecha_fin
        ]);
    }

    $tickets = $query->paginate($perPage)->appends([
        'fecha_inicio' => $request->fecha_inicio,
        'fecha_fin'    => $request->fecha_fin,
        'perPage'      => $perPage,
    ]);

    // Pasar las fechas de filtro a la vista
    $filtroFechas = [
        'inicio' => $request->fecha_inicio ?? null,
        'fin'    => $request->fecha_fin ?? null
    ];

    return view('pagos.pagos', compact('tickets', 'filtroFechas', 'perPage'));
}

    //
   

    public function cancelar($id)
    {
        $ticket = Ticket::findOrFail($id);
        
        if ($ticket->estado_id != 3) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden cancelar pagos con estado "Por Cancelar"'
            ], 400); // Código HTTP 400 para errores
        }
        
        $ticket->estado_id = 4;
        $ticket->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Pago cancelado correctamente'
        ]);
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
