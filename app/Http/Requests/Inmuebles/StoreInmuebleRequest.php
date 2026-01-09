<?php

namespace App\Http\Requests\Inmuebles;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request para crear un nuevo inmueble.
 * 
 * Valida los datos necesarios para crear un inmueble en la PH.
 */
class StoreInmuebleRequest extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado para realizar esta solicitud.
     * 
     * Solo ADMIN_PH y LOGISTICA pueden crear inmuebles.
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
            'nomenclatura' => ['required', 'string', 'max:50', 'unique:inmuebles,nomenclatura'],
            'coeficiente' => ['required', 'numeric', 'min:0', 'max:100'],
            'tipo' => ['required', 'string', 'max:50'],
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
            'nomenclatura.required' => 'La nomenclatura es obligatoria',
            'nomenclatura.unique' => 'Esta nomenclatura ya está registrada',
            'nomenclatura.max' => 'La nomenclatura no puede exceder 50 caracteres',
            'coeficiente.required' => 'El coeficiente es obligatorio',
            'coeficiente.numeric' => 'El coeficiente debe ser un número',
            'coeficiente.min' => 'El coeficiente debe ser mayor o igual a 0',
            'coeficiente.max' => 'El coeficiente debe ser menor o igual a 100',
            'tipo.required' => 'El tipo es obligatorio',
            'tipo.max' => 'El tipo no puede exceder 50 caracteres',
            'propietario_documento.max' => 'El documento no puede exceder 20 caracteres',
            'propietario_nombre.max' => 'El nombre no puede exceder 255 caracteres',
            'telefono.max' => 'El teléfono no puede exceder 20 caracteres',
            'email.email' => 'El email debe ser una dirección válida',
            'email.max' => 'El email no puede exceder 255 caracteres',
        ];
    }
}
