<?php

namespace App\Http\Controllers;

use App\Models\NombreConcepto;
use Illuminate\Http\Request;

class ServicioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
         $servicios = NombreConcepto::where('id_categoria', 1)
        ->orderBy('nombre', 'asc') // Orden alfabético ascendente
        ->paginate(10);
        
        $totalServicios = NombreConcepto::where('id_categoria', 1)->count();

        return view('servicios.servicios', [
            'servicios' => $servicios,
            'totalServicios' => $totalServicios,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show()
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $producto = NombreConcepto::findOrFail($id);
        
        return response()->json([
            'id' => $producto->id,
            'nombre' => $producto->nombre,
            'precio' => $producto->precio,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $servicio = NombreConcepto::findOrFail($id);
            
            $validatedData = $request->validate([
                'nombre' => 'required|string|max:255',
                'precio' => 'required|numeric',
            ]);
            
            $servicio->update([
                'nombre' => $validatedData['nombre'],
                'precio' => $validatedData['precio'],
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Servicio actualizado correctamente'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
         try {
            $servicio = NombreConcepto::findOrFail($id);
            $servicio->delete();

            // Para peticiones AJAX
            if(request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'servicio eliminado correctamente'
                ]);
            }
            
            // Para peticiones tradicionales
            return redirect()->route('servicios.index')
                ->with('success', 'servicio eliminado correctamente');
                
        } catch (\Exception $e) {
            if(request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al eliminar: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->route('servicios.index')
                ->with('error', 'Error al eliminar: ' . $e->getMessage());
        }
    }
}
