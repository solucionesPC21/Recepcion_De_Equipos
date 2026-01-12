<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VentaAbono;
use Illuminate\Support\Facades\View;

class BusquedaAbono extends Controller
{
     public function buscar(Request $request)
    {
        $searchTerm = $request->input('search');
        $estado = $request->input('estado'); // <-- nuevo

        // Buscar ventas por nombre del cliente
        $ventas = VentaAbono::with(['cliente', 'detalles.concepto', 'estado'])
        ->when($searchTerm, function ($query) use ($searchTerm) {
            $query->whereHas('cliente', function($q) use ($searchTerm) {
                $q->where('nombre', 'like', '%'.$searchTerm.'%');
            });
        })
        ->when($estado, function ($query) use ($estado) {
            $query->where('estado_id', $estado);
        })
        ->orderBy('fecha_venta', 'desc')
        ->paginate(10);

        // Renderizar solo el <tbody> con las ventas filtradas
        $recibosBodyHtml = View::make('abonos.abonoPartials', compact('ventas'))->render();

        // Renderizar los links de paginación (con el search conservado)

        $paginationLinks = $ventas->appends([
        'search' => $searchTerm,
        'estado' => $estado
        ])
        ->onEachSide(1)
        ->links('pagination::bootstrap-4')
        ->render();

        return response()->json([
            'recibosBodyHtml' => $recibosBodyHtml,
            'paginationLinks' => $paginationLinks
        ]);
    }
}
