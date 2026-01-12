<?php

namespace App\Http\Controllers;
use App\Models\Clientes;
use App\Models\Colonias;
use App\Models\Equipo; 
use App\Models\Marca;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BusquedaClientesController extends Controller
{
  public function buscar(Request $request)
    {
        $term = $request->input('term');
        
        // Validar que haya término de búsqueda
        if (empty($term) || strlen($term) < 2) {
            return response()->json([]);
        }
    
        $clientes = Clientes::with('colonia') // Cargar la relación colonia
            ->where('nombre', 'like', "%{$term}%")
            ->take(7)
            ->get();
        
        // Formatear los datos para incluir el nombre de la colonia
        $clientesFormateados = $clientes->map(function ($cliente) {
            return [
                'id' => $cliente->id,
                'nombre' => $cliente->nombre,
                'telefono' => $cliente->telefono,
                'rfc' => $cliente->rfc,
                'colonia' => $cliente->colonia ? [
                    'id' => $cliente->colonia->id,
                    'colonia' => $cliente->colonia->colonia
                ] : null
            ];
        });
        
        return response()->json($clientesFormateados);
    }
    
}
 