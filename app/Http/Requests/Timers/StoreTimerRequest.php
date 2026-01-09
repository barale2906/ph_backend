<?php

namespace App\Http\Requests\Timers;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request para crear un nuevo cronómetro.
 * 
 * Valida los datos necesarios para crear un timer en una reunión.
 */
class StoreTimerRequest extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado para realizar esta solicitud.
     * 
     * Solo ADMIN_PH y LOGISTICA pueden crear timers.
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
            'reunion_id' => ['required', 'integer', 'exists:reuniones,id'],
            'tipo' => ['required', 'string', 'in:INTERVENCION,VOTACION'],
            'duracion_segundos' => ['required', 'integer', 'min:1', 'max:3600'],
            'estado' => ['sometimes', 'string', 'in:inactivo,activo,pausado,finalizado'],
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
            'reunion_id.required' => 'La reunión es obligatoria',
            'reunion_id.exists' => 'La reunión especificada no existe',
            'tipo.required' => 'El tipo es obligatorio',
            'tipo.in' => 'El tipo debe ser: INTERVENCION o VOTACION',
            'duracion_segundos.required' => 'La duración es obligatoria',
            'duracion_segundos.integer' => 'La duración debe ser un número entero',
            'duracion_segundos.min' => 'La duración debe ser al menos 1 segundo',
            'duracion_segundos.max' => 'La duración no puede exceder 3600 segundos (1 hora)',
            'estado.in' => 'El estado debe ser: inactivo, activo, pausado o finalizado',
        ];
    }
}
