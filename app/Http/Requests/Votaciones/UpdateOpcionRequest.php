<?php

namespace App\Http\Requests\Votaciones;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request para actualizar una opción de respuesta.
 * 
 * Valida los datos necesarios para actualizar una opción existente.
 */
class UpdateOpcionRequest extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado para realizar esta solicitud.
     * 
     * Solo ADMIN_PH y LOGISTICA pueden actualizar opciones.
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
            'texto' => ['sometimes', 'string', 'max:255'],
            'orden' => ['sometimes', 'integer', 'min:1'],
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
            'texto.max' => 'El texto de la opción no puede exceder 255 caracteres',
            'orden.min' => 'El orden debe ser mayor a 0',
        ];
    }
}
