<?php

namespace App\Http\Controllers;
use App\Models\ClienteAbono;
use App\Models\Regimen;
use Illuminate\Support\Facades\View;

use Illuminate\Http\Request;

class ClienteAbonoController extends Controller
{
   public function store(Request $request)
{
    try {
        $cliente = ClienteAbono::create($request->all());
        
        // Cargar relaciones necesarias para la vista
        $cliente->load('regimen');
        
        return response()->json([
            'success' => true, 
            'message' => 'Cliente registrado correctamente',
            'cliente' => $cliente,
            'html' => view('notaAbonos.notaAbonosPartials', ['clientes' => collect([$cliente])])->render()
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false, 
            'message' => 'Error al registrar el cliente: ' . $e->getMessage()
        ], 500);
    }
}

    //Funcion para validar nombre de clientes repetidos
    public function verificarNombreAbono(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|min:3'
        ]);

        $existe = ClienteAbono::where('nombre', $request->nombre)->exists();

        return response()->json(['existe' => $existe]);
    }

    // Funcion para ver al cliente;
    public function show($id)
    {
        try {
            $cliente = ClienteAbono::with('regimen')->findOrFail($id);
            return response()->json($cliente);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Cliente no encontrado'
            ], 404);
        }
    }

    // Método para actualizar cliente
    public function update(Request $request, $id)
    {
        try {
            $cliente = ClienteAbono::findOrFail($id);
            
            // Validación opcional
            $validated = $request->validate([
                'nombre' => 'required|string|max:100|unique:clientes_abonos,nombre,' . $id,
                'correo' => 'nullable|email|max:70',
                'telefono' => 'nullable|string|max:10',
                'direccion' => 'nullable|string|max:255',
                'rfc' => 'nullable|string|max:13',
                'regimen_id' => 'required|exists:regimenes,id',
                'observaciones' => 'nullable|string'
            ]);
            
            $cliente->update($validated);
            
            return response()->json([
                'success' => true, 
                'message' => 'Cliente actualizado correctamente',
                'cliente' => $cliente
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Error al actualizar el cliente: ' . $e->getMessage()
            ], 500);
        }
    }

    //Buscar Cliente
    public function buscar(Request $request)
    {
        $searchTerm = $request->input('search');
        
        // Buscar clientes por nombre, teléfono, correo o RFC
        $clientes = ClienteAbono::with('regimen')
            ->when($searchTerm, function ($query) use ($searchTerm) {
                $query->where(function($q) use ($searchTerm) {
                    $q->where('nombre', 'like', '%'.$searchTerm.'%')
                    ->orWhere('telefono', 'like', '%'.$searchTerm.'%');
                });
            })
             ->orderBy('id', 'desc')
            ->paginate(10);

        // Renderizar solo el <tbody> con los clientes filtrados
        $recibosBodyHtml = View::make('notaAbonos.notaAbonosPartials', compact('clientes'))->render();

        // Renderizar los links de paginación (con el search conservado)
        $paginationLinks = $clientes->appends(['search' => $searchTerm])
                                    ->onEachSide(1)
                                    ->links('notaAbonos.paginacion')
                                    ->render();

        return response()->json([
            'recibosBodyHtml' => $recibosBodyHtml,
            'paginationLinks' => $paginationLinks
        ]);
    }
}