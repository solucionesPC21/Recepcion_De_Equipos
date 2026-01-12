<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cotizacion; // Asegúrate de importar el modelo Cotizacion
use Illuminate\Support\Facades\View;

class BuscarCotizacion extends Controller
{
    public function buscar(Request $request)
    {
        $searchTerm = $request->input('search');

        // ✅ BÚSQUEDA MODIFICADA PARA COTIZACIONES
        $cotizaciones = Cotizacion::where('nombre_cliente', 'like', '%'.$searchTerm.'%')
            ->orderBy('fecha_creacion', 'DESC')
            ->paginate(10); // Ajusta el número de resultados por página

        // Cargar la vista parcial y pasar los datos de la búsqueda
        $recibosBodyHtml = View::make('cotizacionHistorial.cotizacionHistorial-body', compact('cotizaciones'))->render();

        // Retornar la vista parcial como respuesta
        return response()->json([
            'recibosBodyHtml' => $recibosBodyHtml,
        ]);
    }

}
