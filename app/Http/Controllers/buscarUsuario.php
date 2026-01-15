<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Models\User;

class buscarUsuario extends Controller
{
    public function buscar(Request $request)
    {
        $searchTerm = $request->input('search');

        // Realizar la búsqueda por el nombre del usuario
        $users = User::where('nombre', 'like', '%'.$searchTerm.'%')->paginate(5);

        // Cargar la vista parcial y pasar los datos de la búsqueda
        $recibosBodyHtml = View::make('users.users-body', compact('users'))->render();

        // Retornar la vista parcial como respuesta
        return response()->json(['recibosBodyHtml' => $recibosBodyHtml]);
    }
}
