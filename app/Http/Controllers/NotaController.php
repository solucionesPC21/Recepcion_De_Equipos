<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Nota;
use App\Models\Recibo;

class NotaController extends Controller
{
     public function obtenerNota($id)  // Cambiado a $id para coincidir con la ruta
    {
        // Validar que el ID del recibo esté presente
        if (!$id) {
            return response()->json(['error' => 'ID de recibo no proporcionado.'], 400);
        }

        // Buscar el recibo por ID y cargar la nota asociada
        $recibo = Recibo::with('nota')->find($id);
        
        if (!$recibo) {
            return response()->json(['nota' => '']);
        }

        // Si no hay nota o la relación no existe, retornar vacío
        if (!$recibo->nota) {
            return response()->json(['nota' => '']);
        }

        // Retornar la nota en formato JSON
        return response()->json(['nota' => $recibo->nota->nota]);
    }

    /**
     * Guarda la nota asociada a un recibo.
     */
    public function guardarNota(Request $request, $id)  // Agregado $id del parámetro de ruta
    {
        // Validar los datos de entrada - ahora el ID viene de la ruta
        $validated = $request->validate([
            'nota' => 'required|string'
        ]);

        // Usar el ID de la ruta en lugar del request body
        $recibo = Recibo::find($id);
        if (!$recibo) {
            return response()->json(['error' => 'Recibo no encontrado.'], 404);
        }

        // Obtener la nota asociada al recibo
        $notaModel = $recibo->nota;

        // Si no hay una nota existente, crea una nueva y asígnala al recibo
        if (!$notaModel) {
            $notaModel = new Nota();
            $notaModel->nota = $validated['nota'];
            $notaModel->save();

            // Asignar la nueva nota al recibo
            $recibo->id_nota = $notaModel->id;
            $recibo->save();
        } else {
            // Si ya existe una nota, simplemente actualizar el contenido
            $notaModel->nota = $validated['nota'];
            $notaModel->save();
        }

        // Retornar una respuesta exitosa
        return response()->json([
            'success' => true, 
            'nota' => $notaModel->nota,
            'message' => 'Nota guardada correctamente'
        ]);
    }
}
