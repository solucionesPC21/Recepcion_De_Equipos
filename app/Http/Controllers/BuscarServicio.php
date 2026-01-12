<?php

namespace App\Http\Controllers;

use App\Models\NombreConcepto;
use Illuminate\Http\Request;

class BuscarServicio extends Controller
{
     public function buscar(Request $request)
    {
        $termino = $request->input('q', '');
        $perPage = $request->input('perPage', 10); // Puedes ajustar esto
        
        $servicios = NombreConcepto::where('id_categoria', 1)
            ->where(function($query) use ($termino) {
                $query->where('nombre', 'LIKE', "%{$termino}%");
            })
            ->paginate($perPage);
            
        return response()->json($servicios);
    }
}
