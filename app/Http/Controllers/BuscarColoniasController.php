<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Colonias;

class BuscarColoniasController extends Controller
{
     public function buscarColonia(Request $request)
    {
        $terminoBusqueda = $request->input('term');
        
        if (empty($terminoBusqueda) || strlen($terminoBusqueda) < 2) {
            return response()->json([]);
        }
        
        // Buscar colonias incluyendo el ID
        $colonias = Colonias::where('colonia', 'LIKE', '%' . $terminoBusqueda . '%')
                            ->take(10)
                            ->get(['id', 'colonia']); // Incluir el ID y el nombre
        
        // Formatear los datos para incluir ID y nombre
        $datos = $colonias->map(function($colonia) {
            return [
                'id' => $colonia->id,
                'colonia' => $colonia->colonia
            ];
        });
        
        return response()->json($datos);
    }
}
