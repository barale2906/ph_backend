<?php

namespace App\Http\Requests\Phs;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePhRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $ph = $this->route('ph');
        $phId = $ph instanceof \App\Models\Ph ? $ph->id : $ph;

        return $this->user()?->esSuperAdmin() ?? false
            || ($this->user()?->esAdminPh() && $this->user()?->tieneAccesoPh($phId));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $phId = $this->route('ph');

        return [
            'nit' => ['sometimes', 'string', 'max:20', Rule::unique('phs', 'nit')->ignore($phId)],
            'nombre' => ['sometimes', 'string', 'max:255'],
            'db_name' => ['sometimes', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/'],
            'estado' => ['sometimes', 'string', 'in:activo,inactivo'],
        ];
    }
}

