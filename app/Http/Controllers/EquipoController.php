<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use Illuminate\Http\Request;

class EquipoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $datos['equipos'] = Equipo::paginate(100);
        return view('tipoEquipos.equipos', $datos);
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
        $datosTipoEquipo= request()->except('_token');
        Equipo::insert($datosTipoEquipo);
        return redirect()->route('tipo_equipos.index');
    }

    /**
     * Display the specified resource.
     
    public function show(Equipo $equipo)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $tipo_equipo = Equipo::findOrFail($id);
        return response()->json($tipo_equipo);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update($id)
    {
        $datosTipoEquipo= request()->except('_token','_method');
        Equipo::where('id','=',$id)->update($datosTipoEquipo);

        $tipo_equipo=Equipo::findOrFail($id);
        return redirect()->route('tipo_equipos.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        Equipo::destroy($id);
        return redirect('tipo_equipos');
    }
}
