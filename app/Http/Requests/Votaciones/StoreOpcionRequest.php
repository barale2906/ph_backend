<?php

namespace App\Http\Requests\Votaciones;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request para crear una nueva opción de respuesta.
 * 
 * Valida los datos necesarios para crear una opción para una pregunta.
 */
class StoreOpcionRequest extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado para realizar esta solicitud.
     * 
     * Solo ADMIN_PH y LOGISTICA pueden crear opciones.
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
            'pregunta_id' => ['required', 'integer', 'exists:preguntas,id'],
            'texto' => ['required', 'string', 'max:255'],
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
            'pregunta_id.required' => 'La pregunta es obligatoria',
            'pregunta_id.exists' => 'La pregunta especificada no existe',
            'texto.required' => 'El texto de la opción es obligatorio',
            'texto.max' => 'El texto de la opción no puede exceder 255 caracteres',
            'orden.min' => 'El orden debe ser mayor a 0',
        ];
    }
}
