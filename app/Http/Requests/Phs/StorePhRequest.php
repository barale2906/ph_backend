<?php

namespace App\Http\Requests\Phs;

use Illuminate\Foundation\Http\FormRequest;

class StorePhRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->esSuperAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nit' => ['required', 'string', 'max:20', 'unique:phs,nit'],
            'nombre' => ['required', 'string', 'max:255'],
            'db_name' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/'],
            'estado' => ['sometimes', 'string', 'in:activo,inactivo'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nit.required' => 'El NIT es obligatorio',
            'nit.unique' => 'Este NIT ya está registrado',
            'nombre.required' => 'El nombre es obligatorio',
            'db_name.required' => 'El nombre de la base de datos es obligatorio',
            'db_name.regex' => 'El nombre de la base de datos solo puede contener letras minúsculas, números y guiones bajos',
        ];
    }
}

