<?php

namespace App\Http\Controllers;

use App\Models\NombreConcepto;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function index()
    {
        $productos = NombreConcepto::where('id_categoria', 2)->paginate(10);
        $totalProductos = NombreConcepto::where('id_categoria', 2)->count();

        return view('productos.productos', [
            'productos' => $productos,
            'totalProductos' => $totalProductos,
        ]);
    }

    /**
     Validacion de productos repetidos con codigo de barra
     */

    public function validarProducto(Request $request)
    {
        $codigo_barra = trim($request->input('codigo_barra', ''));
        $nombre = trim($request->input('nombre', ''));
        $modelo = trim($request->input('modelo', ''));
        $marca = trim($request->input('marca', ''));

        // Validar existencia de código de barras (solo en categoría 2)
        $codigoExiste = !empty($codigo_barra)
            ? NombreConcepto::where('codigo_barra', $codigo_barra)
                ->where('id_categoria', 2)
                ->exists()
            : false;

      // Validar existencia del producto con misma combinación exacta (solo en categoría 2)
        $productoExiste = false;
        if ($nombre !== '' && $modelo !== '' && $marca !== '' && $codigo_barra !== '') {
            $productoExiste = NombreConcepto::where('id_categoria', 2)
                ->whereRaw('LOWER(TRIM(nombre)) = ?', [strtolower(trim($nombre))])
                ->whereRaw('LOWER(TRIM(modelo)) = ?', [strtolower(trim($modelo))])
                ->whereRaw('LOWER(TRIM(marca)) = ?', [strtolower(trim($marca))])
                ->whereRaw('LOWER(TRIM(codigo_barra)) = ?', [strtolower(trim($codigo_barra))])
                ->exists();
        }
        return response()->json([
            'codigoExiste' => $codigoExiste,
            'productoExiste' => $productoExiste,
        ]);
    }


     /**
      *CONTROLADOR PARA BUSCAR PRODUCTOS
      */
        // App/Http/Controllers/ProductoController.php
      
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
        \Log::debug('Datos recibidos:', $request->all());
         try {
             $validatedData = $request->validate([
                 'producto' => 'required|string|max:255',
                 'codigo_barra' => 'nullable|string|unique:nombreconcepto,codigo_barra',
                 'precio' => 'required|numeric',
                 'cantidad' => 'required|integer',
                 'marca' => 'nullable|string|max:255',
                 'modelo' => 'nullable|string|max:255',
                 'descripcion' => 'nullable|string'
             ]);
     
             NombreConcepto::create([
                 'nombre' => $validatedData['producto'],
                 'precio' => $validatedData['precio'],
                 'cantidad' => $validatedData['cantidad'],
                 'codigo_barra' => $validatedData['codigo_barra'],
                 'marca' => $validatedData['marca'],
                 'modelo' => $validatedData['modelo'],
                 'descripcion' => $validatedData['descripcion'],
                 'id_categoria' => 2
             ]);
             
             return redirect()->route('productos.index')->with('success', 'Producto registrado correctamente.');
         } catch (\Exception $e) {
             return redirect()->route('productos.index')->with('error', 'Ocurrió un error al registrar el producto. Intenta de nuevo.');
         }
     }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
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
            'cantidad' => $producto->cantidad,
            'codigo_barra' => $producto->codigo_barra,
            'modelo' => $producto->modelo,
            'descripcion' => $producto->descripcion,
            'marca' => $producto->marca,
        ]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $producto = NombreConcepto::findOrFail($id);
            
            $validatedData = $request->validate([
                'nombre' => 'required|string|max:255',
                'precio' => 'required|numeric',
                'cantidad' => 'required|integer',
                'codigo_barra' => 'nullable|string',
                'marca' => 'nullable|string',
                'modelo' => 'nullable|string',
                'descripcion' => 'nullable|string'
            ]);
            
            $producto->update([
                'nombre' => $validatedData['nombre'],
                'precio' => $validatedData['precio'],
                'cantidad' => $validatedData['cantidad'],
                'codigo_barra' => $validatedData['codigo_barra'],
                'marca' => $validatedData['marca'],
                'modelo' => $validatedData['modelo'],
                'descripcion' => $validatedData['descripcion']
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Producto actualizado correctamente'
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
        $producto = NombreConcepto::findOrFail($id);
        $producto->delete();

        // Para peticiones AJAX
        if(request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado correctamente'
            ]);
        }
        
        // Para peticiones tradicionales
        return redirect()->route('productos.index')
            ->with('success', 'Producto eliminado correctamente');
            
    } catch (\Exception $e) {
        if(request()->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar: ' . $e->getMessage()
            ], 500);
        }
        
        return redirect()->route('productos.index')
            ->with('error', 'Error al eliminar: ' . $e->getMessage());
    }
}
}
