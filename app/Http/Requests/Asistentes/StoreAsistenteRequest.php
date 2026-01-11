<?php

namespace App\Http\Requests\Asistentes;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request para crear un nuevo asistente.
 * 
 * Valida los datos necesarios para crear un asistente en la PH.
 */
class StoreAsistenteRequest extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado para realizar esta solicitud.
     * 
     * Solo ADMIN_PH y LOGISTICA pueden crear asistentes.
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
            'nombre' => ['required', 'string', 'max:255'],
            'documento' => ['nullable', 'string', 'max:20'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'barcode_numero' => ['nullable', 'integer', 'min:1'],
            'inmuebles' => ['required', 'array', 'min:1'],
            'inmuebles.*' => ['required', 'integer', 'exists:inmuebles,id'],
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
            'nombre.required' => 'El nombre es obligatorio',
            'nombre.max' => 'El nombre no puede exceder 255 caracteres',
            'documento.max' => 'El documento no puede exceder 20 caracteres',
            'telefono.max' => 'El teléfono no puede exceder 20 caracteres',
            'barcode_numero.integer' => 'El código de barras debe ser numérico',
            'barcode_numero.min' => 'El código de barras debe ser mayor a cero',
            'inmuebles.required' => 'Debe asociar al menos un inmueble al asistente',
            'inmuebles.array' => 'Los inmuebles deben ser un array',
            'inmuebles.min' => 'Debe asociar al menos un inmueble al asistente',
            'inmuebles.*.exists' => 'Uno o más inmuebles no existen',
        ];
    }
}
