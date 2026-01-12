<?php

namespace App\Http\Controllers;

use App\Models\Clientes;
use App\Models\Colonias;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;


class ClientesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clientes = Clientes::paginate(5); // Obtener clientes paginados
        $colonias = Colonias::all(); // Obtener todas las colonias
        return view('clientes.clientes', compact('clientes', 'colonias'));
    }

    /**
     * Show the form for creating a new resource.
     
    public function create()
    {
       
    }

    /**
     * Store a newly created resource in storage.
     */
   public function store(Request $request)
    {
        // Validación
        $campos = [
            'nombre' => 'required|string|regex:/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ\s.,\-]+$/|max:60|unique:clientes',
            'telefono' => 'required|numeric|digits:10',
            'telefono2' => 'nullable|numeric|digits:10',
            'rfc' => 'nullable|min:13|max:14|regex:/^[A-Za-z0-9]+$/|unique:clientes',
            'id_colonia' => 'nullable|exists:colonias,id'
        ];

        $mensajes = [
            'nombre.required' => 'El nombre es requerido',
            'nombre.regex' => 'El formato del nombre solo acepta letras y números',
            'nombre.unique' => 'El cliente con este nombre ya está registrado',
            'nombre.max' => 'El nombre no puede tener más de 60 caracteres',
            'telefono.required' => 'El teléfono es requerido',
            'telefono.numeric' => 'Solo se puede ingresar números al teléfono',
            'telefono.digits' => 'El teléfono debe tener exactamente 10 dígitos',
            'telefono2.numeric' => 'Solo se puede ingresar números al teléfono 2',
            'telefono2.digits' => 'El teléfono 2 debe tener exactamente 10 dígitos',
            'rfc.regex' => 'El formato del RFC solo acepta números y letras',
            'rfc.unique' => 'El RFC ya está registrado',
            'rfc.min' => 'El RFC debe tener al menos 13 caracteres',
            'rfc.max' => 'El RFC no puede tener más de 14 caracteres',
            'id_colonia.exists' => 'La colonia seleccionada no es válida' // ← CAMBIAR aquí tambié
        ];

        // Validar los datos
        $validator = Validator::make($request->all(), $campos, $mensajes);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
               // Obtener la colonia seleccionada - CORREGIDO
        $coloniaId = $request->input('id_colonia'); // ← CAMBIAR 'colonia' por 'id_colonia'
        $colonia = $coloniaId ? Colonias::find($coloniaId) : null;

            // Crear el cliente
            $cliente = Clientes::create([
                'nombre' => $request->nombre,
                'telefono' => $request->telefono,
                'telefono2' => $request->telefono2,
                'rfc' => $request->rfc,
                'id_colonia' => $colonia ? $colonia->id : null
            ]);

            // Cargar la relación de colonia para la respuesta
            $cliente->load('colonia');

            // Respuesta JSON exitosa
            return response()->json([
                'success' => true,
                'message' => 'Cliente registrado exitosamente',
                'cliente' => [
                    'id' => $cliente->id,
                    'nombre' => $cliente->nombre,
                    'telefono' => $cliente->telefono,
                    'rfc' => $cliente->rfc,
                    'colonia' => $cliente->colonia ? [
                        'colonia' => $cliente->colonia->colonia
                    ] : null
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el cliente: ' . $e->getMessage(),
                'errors' => ['general' => 'Error interno del servidor']
            ], 500);
        }
    }
    /**
     * Display the specified resource.
     
    public function show(Clientes $clientes)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $cliente = Clientes::with('colonia')->findOrFail($id);
        $colonias = Colonias::all(); // Agregar esta línea
        
        return response()->json([
                'cliente' => $cliente,
                'colonias' => $colonias // Enviar colonias al frontend
            ]);
        
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $cliente = Clientes::findOrFail($id);

        $reglas = [
            'nombre' => [
                'required', 'string', 'max:60',
                Rule::unique('clientes')->ignore($id),
            ],
            'telefono' => 'required|numeric|digits:10',
            'telefono2' => 'nullable|numeric|digits:10',
            'rfc' => 'nullable|min:13|max:14|regex:/^[A-Za-z0-9]+$/|unique:clientes,rfc,' . $id,
        ];

        $mensajes = [
            'nombre.required' => 'El nombre es obligatorio',
            'nombre.unique' => 'Este cliente ya existe',
            'telefono.required' => 'El teléfono es obligatorio',
            'telefono.digits' => 'El teléfono debe tener 10 dígitos',
            'rfc.unique' => 'El RFC ya está registrado',
        ];

        $validator = Validator::make($request->all(), $reglas, $mensajes);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $datos = $request->except('_token', '_method');
            
            // Manejar colonia
            if ($request->has('colonia')) {
                $colonia = Colonias::where('colonia', $request->colonia)->first();
                $datos['id_colonia'] = $colonia?->id;
            }

            $cliente->update($datos);

            return response()->json([
                'success' => true,
                'message' => 'Cliente actualizado correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el cliente: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            Clientes::destroy($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Cliente eliminado correctamente'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el cliente: ' . $e->getMessage()
            ], 500);
        }
    }

}
