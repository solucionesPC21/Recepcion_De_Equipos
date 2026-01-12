<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\UpdateRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('role')->paginate(5);
        return view('users.users', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:users',
            'usuario' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:3|confirmed',
            'role_id' => 'required|exists:roles,id',
        ], [
            'nombre.required' => 'El campo nombre es obligatorio.',
            'nombre.unique' => 'El nombre ya está en uso.',
            'usuario.required' => 'El campo usuario es obligatorio.',
            'usuario.unique' => 'El nombre de usuario ya está en uso.',
            'password.required' => 'El campo contraseña es obligatorio.',
            'password.min' => 'La contraseña debe tener al menos 3 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'role_id.required' => 'El rol es obligatorio.',
            'role_id.exists' => 'El rol seleccionado no es válido.',
        ]);

        $datosUsers = $request->except('_token', 'password_confirmation');
        $datosUsers['password'] = bcrypt($request->password);
        
        $user = User::create($datosUsers);

        if ($user) {
            return redirect('/users')->withSuccess('Usuario Registrado Con Éxito');
        } else {
            return redirect('/users')->with('error', 'Hubo un problema al registrar el usuario');
        }
    }

    public function edit(string $id)
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'sometimes|required',
            'usuario' => 'sometimes|required|unique:users,usuario,' . $id,
            'password' => 'sometimes|nullable|min:3|confirmed',
            'password_confirmation' => 'sometimes|required_with:password|same:password',
            'role_id' => 'sometimes|required|exists:roles,id',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'usuario.required' => 'El nombre de usuario es obligatorio.',
            'usuario.unique' => 'El nombre de usuario ya está en uso.',
            'password.min' => 'La contraseña debe tener al menos :min caracteres.',
            'password_confirmation.required_with' => 'La confirmación de la contraseña es obligatoria.',
            'password_confirmation.same' => 'Las contraseñas no coinciden.',
            'role_id.required' => 'El rol es obligatorio.',
            'role_id.exists' => 'El rol seleccionado no es válido.',
        ]);
    
        $user = User::find($id);
    
        if (!$user) {
            return redirect('/users')->with('error', 'Usuario no encontrado');
        }
    
        $data = $request->only(['nombre', 'usuario', 'role_id']);
    
        if (!empty($request->password)) {
            $data['password'] = bcrypt($request->password);
        }
    
        $user->update($data);
    
        return redirect('/users')->with('success', 'La cuenta se actualizó correctamente');
    }

    public function destroy(string $id)
    {
        $user = User::find($id);
        if ($user) {
            $user->delete();
            return redirect('users')->with('success', 'La cuenta ha sido eliminada correctamente');
        } else {
            return redirect('users')->with('error', 'Usuario no encontrado');
        }
    }
}