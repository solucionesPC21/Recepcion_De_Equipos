<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Impresoras;

class ImpresoraController extends Controller
{
     public function index()
    {
        $impresoras = Impresoras::orderBy('id', 'desc')->get();
        return view('configuracion.impresora', compact('impresoras'));
    }

     public function store(Request $request)
    {
        $request->validate([
            'nombre_sistema' => 'required|string|max:150|unique:impresoras,nombre_sistema',
            'tipo' => 'required|in:termica,hojas',
            'sumatra_path' => 'required_if:tipo,hojas|string|max:255|nullable',
        ]);

        Impresoras::create([
            'nombre_sistema' => $request->nombre_sistema,
            'tipo' => $request->tipo,
            'sumatra_path' => $request->tipo === 'hojas'
                ? $request->sumatra_path
                : null,
            'activa' => $request->has('activa'),
        ]);

        return redirect()
            ->route('impresoras.index')
            ->with('success', 'Impresora registrada correctamente.');
    }

    public function destroy($id)
    {
        $impresora = Impresoras::findOrFail($id);

        $impresora->delete();

        return redirect()
            ->route('impresoras.index')
            ->with('success', 'Impresora eliminada correctamente.');
    }
    
}
