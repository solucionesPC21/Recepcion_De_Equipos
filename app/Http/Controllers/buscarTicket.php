<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Recibo;
use Illuminate\Support\Facades\View;

class buscarTicket extends Controller
{
    public function buscar(Request $request)
    {
        $searchTerm = $request->input('search');

        // Si no hay término de búsqueda, devolver todos los recibos CON ESTADO 2
        if (empty($searchTerm)) {
            $recibos = Recibo::with(['tipoEquipo.cliente', 'estado'])
                ->where('id_estado', 2)  // ← ESTADO 2 (Pendiente)
                ->orderBy('updated_at', 'DESC')
                ->paginate(6);
        } else {
            // Búsqueda SOLO en recibos con estado 2
            $recibos = Recibo::with(['tipoEquipo.cliente', 'estado'])
                ->where('id_estado', 2)  // ← ESTADO 2 (Pendiente)
                ->where(function($query) use ($searchTerm) {
                    $query->whereHas('tipoEquipo.cliente', function($q) use ($searchTerm) {
                        $q->where('nombre', 'like', '%'.$searchTerm.'%');
                    })
                    ->orWhere('id', 'like', '%'.$searchTerm.'%');
                })
                ->orderBy('updated_at', 'DESC')
                ->paginate(6);
        }

        // Renderizar la vista parcial CORRECTA
        $recibosGridHtml = View::make('ticket.recibos-body', compact('recibos'))->render();

        return response()->json([
            'recibosGridHtml' => $recibosGridHtml,
            'total' => $recibos->total()
        ]);
    }

}
