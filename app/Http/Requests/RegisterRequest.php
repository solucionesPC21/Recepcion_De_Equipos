<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre'=>'required',
            'usuario'=>'required|unique:users,usuario',
            'password' => 'required|min:3|alpha_num',
            'password_confirmation' => 'required|same:password',
        ];
    }

    public function messages()
    {
        return [
            'nombre.required' => 'El campo nombre es obligatorio.',
            'usuario.required' => 'El campo usuario es obligatorio.',
            'usuario.unique' => 'El nombre de usuario ya está en uso.',
            'password.required' => 'El campo contraseña es obligatorio.',
            'password.min' => 'La contraseña debe tener al menos 3 caracteres.',
            'password.alpha_num' => 'La contraseña debe contener solo letras y números.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }
}
