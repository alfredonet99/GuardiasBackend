<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
            "email" => "required|email|exists:users,email",
            "password" => "required|min:4",
        ];
    }

     public function messages()
    {
        return [
            'email.required' => 'El correo es obligatorio.',
            'email.email'    => 'Ingresa un correo válido.',
            'email.exists'   => 'Este correo no está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
        ];
    }
}
