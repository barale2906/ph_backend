<?php

namespace App\Http\Requests\Votaciones;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request para actualizar una pregunta de votación.
 * 
 * Valida los datos necesarios para actualizar una pregunta existente.
 */
class UpdatePreguntaRequest extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado para realizar esta solicitud.
     * 
     * Solo ADMIN_PH y LOGISTICA pueden actualizar preguntas.
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
            'pregunta' => ['sometimes', 'string', 'max:1000'],
            'estado' => ['sometimes', 'string', 'in:inactiva,abierta,cerrada,cancelada'],
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
            'pregunta.max' => 'El texto de la pregunta no puede exceder 1000 caracteres',
            'estado.in' => 'El estado debe ser: inactiva, abierta, cerrada o cancelada',
            'orden.min' => 'El orden debe ser mayor a 0',
        ];
    }
}
