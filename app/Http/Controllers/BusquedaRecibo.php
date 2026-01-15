<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Recibo;
use Illuminate\Support\Facades\View;

class BusquedaRecibo extends Controller
{
    public function buscar(Request $request)
    {
    
        $searchTerm = $request->input('search');
        $recibos = Recibo::whereHas('tipoEquipo.cliente', function ($query) use ($searchTerm) {
                $query->where('nombre', 'like', '%' . $searchTerm . '%');
            })
            ->whereIn('id_estado', [1, 5])
            ->orderBy('created_at', 'desc')
            ->paginate(5);


        // Cargar la vista parcial y pasar los datos de la búsqueda
        $recibosBodyHtml = View::make('recibos.recibos-body', compact('recibos'))->render();

        // Retornar la vista parcial como respuesta
        return response()->json([
            'recibosBodyHtml' => $recibosBodyHtml,
            
        ]);
    }

}