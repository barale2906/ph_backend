<?php

namespace App\Http\Requests\Reuniones;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request para actualizar una reunión.
 * 
 * Valida los datos necesarios para actualizar una reunión existente.
 */
class UpdateReunionRequest extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado para realizar esta solicitud.
     * 
     * Solo ADMIN_PH puede actualizar reuniones.
     * 
     * @return bool
     */
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && $user->esAdminPh();
    }

    /**
     * Obtener las reglas de validación que se aplican a la solicitud.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tipo' => ['sometimes', 'string', 'in:ordinaria,extraordinaria'],
            'fecha' => ['sometimes', 'date'],
            'hora' => ['sometimes', 'string', 'regex:/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            'modalidad' => ['sometimes', 'string', 'in:presencial,virtual,mixta'],
            'estado' => ['sometimes', 'string', 'in:programada,en_curso,finalizada,cancelada'],
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
            'tipo.in' => 'El tipo debe ser: ordinaria o extraordinaria',
            'fecha.date' => 'La fecha debe ser una fecha válida',
            'hora.regex' => 'La hora debe tener el formato HH:MM (ej: 14:30)',
            'modalidad.in' => 'La modalidad debe ser: presencial, virtual o mixta',
            'estado.in' => 'El estado debe ser: programada, en_curso, finalizada o cancelada',
        ];
    }
}
