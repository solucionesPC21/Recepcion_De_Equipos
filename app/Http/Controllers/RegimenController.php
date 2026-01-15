<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Regimen;

class RegimenController extends Controller
{
    public function store(Request $request)
    {
         try {
        $regimen = Regimen::create($request->all());
        return response()->json([
            'success' => true, 
            'message' => 'Régimen registrado correctamente',
            'regimen' => $regimen // Enviamos el régimen creado para actualizar la lista
        ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Error al registrar el régimen: ' . $e->getMessage()
            ], 500);
        }
    }
}
