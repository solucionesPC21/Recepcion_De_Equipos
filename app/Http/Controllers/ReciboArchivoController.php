<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Recibo;
use App\Models\ReciboArchivo;


class ReciboArchivoController extends Controller
{
      public function subirArchivos(Request $request, $id)
    {
        $request->validate([
            'archivos'      => 'required|array',
            'archivos.*'    => 'file|mimes:pdf,jpg,jpeg,png,webp|max:1024', // 10MB
        ], [
            'archivos.required' => 'Debes seleccionar al menos un archivo.',
            'archivos.*.mimes'  => 'Solo se permiten archivos PDF o imágenes.',
            'archivos.*.max'    => 'Cada archivo no puede superar 1MB.',
        ]);

        foreach ($request->file('archivos') as $file) {
            ReciboArchivo::create([
                'recibo_id' => $id,
                'nombre'    => $file->getClientOriginalName(),
                'tipo'      => $file->getClientMimeType(),
                'archivo'   => file_get_contents($file),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Archivos subidos correctamente'
        ]);
    }

    public function listarArchivos($id)
    {
        return ReciboArchivo::where('recibo_id', $id)
            ->select('id', 'nombre', 'tipo', 'created_at')
            ->get();
    }

    public function descargarArchivo($archivoId)
    {
        $archivo = ReciboArchivo::findOrFail($archivoId);

        return response($archivo->archivo)
            ->header('Content-Type', $archivo->tipo)
            ->header('Content-Disposition', 'attachment; filename="'.$archivo->nombre.'"');
    }

}
