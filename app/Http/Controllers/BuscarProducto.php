<?php

namespace App\Http\Controllers;

use App\Models\NombreConcepto;
use Illuminate\Http\Request;

class BuscarProducto extends Controller
{
     public function buscar(Request $request)
    {
        $termino = $request->input('q', '');
        $perPage = $request->input('perPage', 10); // Puedes ajustar esto
        
        $productos = NombreConcepto::where('id_categoria', 2)
            ->where(function($query) use ($termino) {
                $query->where('nombre', 'LIKE', "%{$termino}%")
                    ->orWhere('modelo', 'LIKE', "%{$termino}%")
                    ->orWhere('marca', 'LIKE', "%{$termino}%")
                    ->orWhere('codigo_barra', 'LIKE', "%{$termino}%");
            })
            ->paginate($perPage);
            
        return response()->json($productos);
    }
    
}
