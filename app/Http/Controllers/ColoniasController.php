<?php

namespace App\Http\Controllers;

use App\Models\Colonias;
use Illuminate\Http\Request;

class ColoniasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $datos['colonias']=Colonias::paginate(100);
        return view('colonias.colonias',$datos);
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
        $datosColonias= request()->except('_token');
        Colonias::insert($datosColonias);
        return redirect()->route('colonias.index');

    // Recuperar las colonias actualizadas

    }

    /**
     * Display the specified resource.
     
    public function show(Colonias $colonias)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $colonia = Colonias::findOrFail($id);
        return response()->json($colonia);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update($id)
    {
        $datosColonia= request()->except('_token','_method');
        Colonias::where('id','=',$id)->update($datosColonia);

        $colonia=Colonias::findOrFail($id);
        return redirect()->route('colonias.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        Colonias::destroy($id);
        return redirect('colonias');
    }

    
}