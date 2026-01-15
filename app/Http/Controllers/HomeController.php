<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Equipo;
use App\Models\Marca;
use App\Models\Colonias;

class HomeController extends Controller
{
    public function index()
    {
        $marcas = Marca::orderBy('marca', 'asc')->get(); // Ordenar marcas A-Z
        $equipos = Equipo::orderBy('equipo', 'asc')->get(); // Ordenar equipos A-Z  
        $colonias = Colonias::orderBy('colonia', 'asc')->get(); // Obtener todas las colonias ordenadas descendentemente
        return view('home.home', compact('equipos','marcas','colonias'));
    }

}
