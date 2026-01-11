<?php

namespace App\Http\Requests\Users;

use App\Enums\Rol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignPhRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Solo super admin o admin PH pueden asignar acceso
        return $this->user()?->esSuperAdmin() || $this->user()?->rol === 'ADMIN_PH';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ph_id' => ['required', 'integer', 'exists:phs,id'],
            'rol' => ['required', 'string', Rule::in(['ADMIN_PH', 'LOGISTICA', 'LECTURA'])], // No se puede asignar SUPER_ADMIN por PH
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
            'ph_id.required' => 'El ID de la PH es obligatorio',
            'ph_id.exists' => 'La PH especificada no existe',
            'rol.required' => 'El rol es obligatorio',
            'rol.in' => 'El rol debe ser uno de: ADMIN_PH, LOGISTICA, LECTURA',
        ];
    }
}
