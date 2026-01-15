<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Gasto;

class GastoController extends Controller
{
    public function index(Request $request)
    {
        $query = Gasto::query()
                    ->orderBy('fecha', 'desc')  // Primero ordena por fecha (más reciente primero)
                    ->orderBy('id', 'desc');     // Luego por ID (como criterio de desempate)

        // Filtro por rango de fechas
        if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
            $query->whereBetween('fecha', [
                $request->fecha_inicio,
                $request->fecha_fin
            ]);
        }

        $gastos = $query->paginate(10);
        
        // Pasar las fechas de filtro a la vista
        $filtroFechas = [
            'inicio' => $request->fecha_inicio ?? null,
            'fin' => $request->fecha_fin ?? null
        ];

        return view('gastos.gastos', compact('gastos', 'filtroFechas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'descripcion' => 'required|string|max:255',
            'monto' => 'required|numeric|min:0.01',
            'fecha' => 'required|date'
        ]);

        try {
            Gasto::create($request->all());
            return response()->json([
                'success' => true,
                'message' => 'Gasto registrado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error: ' . $e->getMessage()
            ], 500);
        }
    }

// Similar para update y destroy

    public function edit(Gasto $gasto)
    {
        return response()->json($gasto);
    }

    public function update(Request $request, Gasto $gasto)
    {
        $request->validate([
            'descripcion' => 'required|string|max:255',
            'monto' => 'required|numeric|min:0.01',
            'fecha' => 'required|date'
        ]);

        try {
            $gasto->update($request->all());
            
            // Para peticiones AJAX
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Gasto actualizado correctamente',
                    'data' => $gasto
                ]);
            }
            
            // Para peticiones normales
            return redirect()->route('gastos.index')->with('swal', [
                'icon' => 'success',
                'title' => 'Éxito',
                'text' => 'Gasto actualizado correctamente'
            ]);
            
        } catch (\Exception $e) {
            // Para peticiones AJAX
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ocurrió un error al actualizar: ' . $e->getMessage(),
                    'errors' => $e->getErrors() ?? null
                ], 500);
            }
            
            // Para peticiones normales
            return back()->withInput()->with('swal', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'Ocurrió un error al actualizar: ' . $e->getMessage()
            ]);
        }
    }

    public function destroy(Gasto $gasto)
    {
        try {
            $gasto->delete();
            
            // Para peticiones AJAX, devolver JSON
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Gasto eliminado correctamente'
                ]);
            }
            
            // Para peticiones normales (por si acaso)
            return redirect()->route('gastos.index')->with('swal', [
                'icon' => 'success',
                'title' => 'Éxito',
                'text' => 'Gasto eliminado correctamente'
            ]);
            
        } catch (\Exception $e) {
            // Para peticiones AJAX
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ocurrió un error al eliminar: ' . $e->getMessage()
                ], 500);
            }
            
            // Para peticiones normales
            return back()->with('swal', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'Ocurrió un error al eliminar: ' . $e->getMessage()
            ]);
        }
    }
}
