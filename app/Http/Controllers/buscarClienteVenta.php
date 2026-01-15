<?php

namespace App\Http\Controllers;
use App\Models\Clientes;

use Illuminate\Http\Request;

class buscarClienteVenta extends Controller
{
    public function buscar(Request $request) {
        $term = $request->input('term');
        $clientes = Clientes::where('nombre', 'like', "%{$term}%")->take(12)->get();
    
        // Retornar un JSON con las coincidencias
        return response()->json($clientes);
    }

    public function seleccionarCliente(Request $request, $id) {
        $clientes = Clientes::find($id);
        
        if ($clientes) {
            // Retornar la información del cliente en formato JSON
            return response()->json([
                'nombre' => $clientes->nombre,
                'telefono' => $clientes->telefono,
                
            ]);
        } else {
            return response()->json(['error' => 'Cliente no encontrado.'], 404);
        }
    }
}
