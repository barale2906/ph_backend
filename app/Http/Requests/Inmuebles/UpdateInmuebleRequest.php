<?php

namespace App\Http\Requests\Inmuebles;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request para actualizar un inmueble.
 * 
 * Valida los datos necesarios para actualizar un inmueble existente.
 */
class UpdateInmuebleRequest extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado para realizar esta solicitud.
     * 
     * Solo ADMIN_PH y LOGISTICA pueden actualizar inmuebles.
     * 
     * @return bool
     */
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && ($user->esAdminPh() || $user->esLogistica());
    }

    /**
     * Obtener las reglas de validación que se aplican a la solicitud.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nomenclatura' => ['sometimes', 'string', 'max:50', Rule::unique('inmuebles', 'nomenclatura')->ignore($this->route('inmueble'))],
            'coeficiente' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'tipo' => ['sometimes', 'string', 'max:50'],
            'propietario_documento' => ['nullable', 'string', 'max:20'],
            'propietario_nombre' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Obtener mensajes personalizados para errores de validación.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nomenclatura.unique' => 'Esta nomenclatura ya está registrada',
            'nomenclatura.max' => 'La nomenclatura no puede exceder 50 caracteres',
            'coeficiente.numeric' => 'El coeficiente debe ser un número',
            'coeficiente.min' => 'El coeficiente debe ser mayor o igual a 0',
            'coeficiente.max' => 'El coeficiente debe ser menor o igual a 100',
            'tipo.max' => 'El tipo no puede exceder 50 caracteres',
            'propietario_documento.max' => 'El documento no puede exceder 20 caracteres',
            'propietario_nombre.max' => 'El nombre no puede exceder 255 caracteres',
            'telefono.max' => 'El teléfono no puede exceder 20 caracteres',
            'email.email' => 'El email debe ser una dirección válida',
            'email.max' => 'El email no puede exceder 255 caracteres',
        ];
    }
}
