<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Models\Ticket;


class BusquedaPago extends Controller
{
    public function buscar(Request $request)
{
    $searchTerm = $request->input('search');
    $perPage = $request->input('perPage', 10); // ← ahora sí respeta perPage

    $query = Ticket::where('estado_id', 3)
                ->orderBy('id', 'desc');

    // Búsqueda por nombre de cliente
    if (!empty($searchTerm)) {
        $query->where(function($q) use ($searchTerm) {
            $q->whereHas('cliente', function($q2) use ($searchTerm) {
                $q2->where('nombre', 'like', '%'.$searchTerm.'%');
            })->orWhereHas('recibo.tipoEquipo.cliente', function($q2) use ($searchTerm) {
                $q2->where('nombre', 'like', '%'.$searchTerm.'%');
            });
        });
    }

    // Filtro de fecha si existe
    if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
        $query->whereBetween('fecha', [
            $request->fecha_inicio,
            $request->fecha_fin
        ]);
    }

    // Paginar respetando perPage
    $tickets = $query->paginate($perPage);

    // Renderizar el cuerpo de la tabla
    $ticketsBodyHtml = View::make('pagos.pagosPartial', compact('tickets'))->render();

    // Renderizar paginación respetando todos los filtros
    $paginationLinks = $tickets
        ->appends([
            'search'       => $searchTerm,
            'fecha_inicio' => $request->input('fecha_inicio'),
            'fecha_fin'    => $request->input('fecha_fin'),
            'perPage'      => $perPage
        ])
        ->onEachSide(1)
        ->links('pagination::bootstrap-4')
        ->render();

    return response()->json([
        'recibosBodyHtml' => $ticketsBodyHtml,
        'paginationLinks' => $paginationLinks
    ]);
}

}
