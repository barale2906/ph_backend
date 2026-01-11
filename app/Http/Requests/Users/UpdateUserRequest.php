<?php

namespace App\Http\Requests\Users;

use App\Enums\Rol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->route('user');
        
        // Super admin puede actualizar todos
        if ($this->user()?->esSuperAdmin()) {
            return true;
        }

        // Usuario puede actualizar su propia información
        if ($this->user()?->id === $user->id) {
            return true;
        }

        // Admin PH puede actualizar usuarios de sus PHs
        if ($this->user()?->rol === 'ADMIN_PH') {
            $userPhs = $this->user()->phs()->pluck('phs.id')->toArray();
            $modelPhs = $user->phs()->pluck('phs.id')->toArray();
            
            return !empty(array_intersect($userPhs, $modelPhs));
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user')->id;
        $user = $this->user();

        $rules = [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
        ];

        // Solo super admin puede cambiar el rol
        if ($user?->esSuperAdmin()) {
            $rules['rol'] = ['sometimes', 'string', Rule::enum(Rol::class)];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.string' => 'El nombre debe ser texto',
            'email.email' => 'El email debe ser válido',
            'email.unique' => 'Este email ya está registrado',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.confirmed' => 'La confirmación de contraseña no coincide',
            'rol.enum' => 'El rol debe ser uno de: SUPER_ADMIN, ADMIN_PH, LOGISTICA, LECTURA',
        ];
    }
}
