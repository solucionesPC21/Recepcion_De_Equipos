<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Clientes;
use Illuminate\Support\Facades\View;

class BuscarCliente extends Controller
{

   public function buscar(Request $request)
    {
        try {
            $searchTerm = $request->input('search');
            
            // Si no hay término de búsqueda, devolver todos los clientes
            if (empty($searchTerm)) {
                $clientes = Clientes::with('colonia')->paginate(5);
            } else {
                // Buscar por nombre, teléfono o RFC
                $clientes = Clientes::with('colonia')
                    ->where('nombre', 'like', '%'.$searchTerm.'%')
                    ->orWhere('telefono', 'like', '%'.$searchTerm.'%')
                    ->orWhere('rfc', 'like', '%'.$searchTerm.'%')
                    ->paginate(5);
            }

            // Verificar si hay resultados
            $html = $clientes->count() > 0 
                ? view('clientes.clientes-body', compact('clientes'))->render()
                : '<tr><td colspan="7" class="text-center">No se encontraron clientes</td></tr>';

            return response()->json([
                'success' => true,
                'recibosBodyHtml' => $html,
                'total' => $clientes->total()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error en la búsqueda: ' . $e->getMessage()
            ], 500);
        }
    }

}
