<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NombreConcepto;
use Illuminate\Support\Facades\View;

class BusquedaConcepto extends Controller
{
    
    public function buscar(Request $request)
    {
        try {
            $request->validate([
                'query' => 'required|string|min:2|max:100',
                'mostrar_agotados' => 'sometimes|boolean'
            ]);
            
            $query = $request->input('query');
            $mostrarAgotados = $request->input('mostrar_agotados', false);
            
            $consulta = NombreConcepto::where('nombre', 'like', '%' . $query . '%')
                ->select('id', 'nombre', 'precio', 'id_categoria', 'cantidad', 'modelo', 'marca');
            
            if (!$mostrarAgotados) {
                $consulta->where(function($q) {
                    $q->where('id_categoria', 1)
                    ->orWhere(function($q2) {
                        $q2->where('id_categoria', 2)
                            ->where('cantidad', '>=', 0);
                    });
                });
            }
            
            $conceptos = $consulta->orderBy('nombre')
                ->limit(7)
                ->get();
                
            return response()->json($conceptos); // Devuelve los datos completos
            
        } catch (\Exception $e) {
            Log::error('Error en búsqueda de conceptos: ' . $e->getMessage());
            return response()->json(['error' => 'Error al realizar la búsqueda'], 500);
        }
    }

    protected function formatearTextoConcepto($concepto)
    {
        $partes = [$concepto->nombre];
        
        if (!empty($concepto->marca)) {
            $partes[] = $concepto->marca;
        }
        
        if (!empty($concepto->modelo)) {
            $partes[] = $concepto->modelo;
        }
        
        $partes[] = '$' . number_format($concepto->precio, 2);
        
        // Mostrar disponibilidad solo para categoría 2 (productos)
        if ($concepto->id_categoria == 2) {
            $partes[] = $concepto->cantidad > 0 
                ? 'Disponibles: ' . $concepto->cantidad 
                : 'AGOTADO';
        } else {
            $partes[] = 'SERVICIO';
        }
        
        return implode(' | ', $partes);
    }
}