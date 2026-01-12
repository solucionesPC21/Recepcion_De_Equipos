<?php

namespace App\Http\Controllers;

use App\Models\Marca;
use Illuminate\Http\Request;

class MarcaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $datos['marcas'] = Marca::paginate(100);
        return view('Marcas.marcas', $datos);
    }

    /**
     * Show the form for creating a new resource.
     
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $datosMarcas= request()->except('_token');
        Marca::insert($datosMarcas);
        return redirect()->route('marcas.index');
    }

    /**
     * Display the specified resource.
     
    public function show(Marca $marca)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $marca = Marca::findOrFail($id);
        return response()->json($marca);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update($id)
    {
        $datosMarca= request()->except('_token','_method');
        Marca::where('id','=',$id)->update($datosMarca);

        $marca=Marca::findOrFail($id);
        return redirect()->route('marcas.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        Marca::destroy($id);
        return redirect('marcas');
    }
}
