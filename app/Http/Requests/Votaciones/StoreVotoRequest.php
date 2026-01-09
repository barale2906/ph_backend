<?php

namespace App\Http\Requests\Votaciones;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request para registrar un voto.
 * 
 * Valida los datos necesarios para registrar un voto en una pregunta.
 * 
 * IMPORTANTE: Los votos se registran principalmente a través del VotacionService,
 * pero este request puede usarse para validar datos desde el frontend.
 */
class StoreVotoRequest extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado para realizar esta solicitud.
     * 
     * Cualquier usuario autenticado puede votar, o puede ser anónimo
     * si se vota desde WhatsApp o código de acceso.
     * 
     * @return bool
     */
    public function authorize(): bool
    {
        // Permitir votos anónimos (desde WhatsApp o código de acceso)
        return true;
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
            'opcion_id' => ['required', 'integer', 'exists:opciones,id'],
            'inmueble_id' => ['required_without:asistente_id', 'integer', 'exists:inmuebles,id'],
            'asistente_id' => ['required_without:inmueble_id', 'integer', 'exists:asistentes,id'],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:20'],
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
            'opcion_id.required' => 'La opción es obligatoria',
            'opcion_id.exists' => 'La opción especificada no existe',
            'inmueble_id.required_without' => 'Debe especificar un inmueble o un asistente',
            'inmueble_id.exists' => 'El inmueble especificado no existe',
            'asistente_id.required_without' => 'Debe especificar un inmueble o un asistente',
            'asistente_id.exists' => 'El asistente especificado no existe',
            'telefono.max' => 'El teléfono no puede exceder 20 caracteres',
        ];
    }
}
