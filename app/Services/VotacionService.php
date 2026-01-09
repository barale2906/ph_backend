<?php

namespace App\Services;

use App\Events\VotoRegistrado;
use App\Models\Asistentes\Asistente;
use App\Models\Inmuebles\Inmueble;
use App\Models\Votaciones\Pregunta;
use App\Models\Votaciones\Voto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para gestionar votaciones.
 * 
 * Este servicio maneja la lógica de votación, incluyendo:
 * - Registro de votos
 * - Replicación de votos cuando un asistente representa múltiples inmuebles
 * - Validaciones de negocio
 * 
 * IMPORTANTE: Los votos son inmutables una vez registrados.
 */
class VotacionService
{
    /**
     * Registrar un voto para un inmueble.
     * 
     * Si el asistente representa múltiples inmuebles, se replicará el voto
     * para cada inmueble que el asistente representa.
     * 
     * @param int $preguntaId ID de la pregunta
     * @param int $opcionId ID de la opción seleccionada
     * @param int $inmuebleId ID del inmueble que vota
     * @param string|null $telefono Teléfono desde el cual se votó (opcional)
     * @return array Array con los votos registrados
     * @throws \RuntimeException Si la validación falla
     */
    /**
     * Registrar un voto para un inmueble (despacha job).
     * 
     * IMPORTANTE: En FASE 7, todas las escrituras van por cola.
     * Este método ahora despacha un job en lugar de escribir directamente.
     * 
     * @param int $preguntaId ID de la pregunta
     * @param int $opcionId ID de la opción seleccionada
     * @param int $inmuebleId ID del inmueble que vota
     * @param string|null $telefono Teléfono desde el cual se votó (opcional)
     * @return void
     */
    public function registrarVoto(
        int $preguntaId,
        int $opcionId,
        int $inmuebleId,
        ?string $telefono = null
    ): void {
        // Despachar job para procesar el voto de forma asíncrona
        \App\Jobs\Votaciones\RegistrarVotoJob::dispatch(
            $preguntaId,
            $opcionId,
            $inmuebleId,
            $telefono
        );
    }

    /**
     * Registrar un voto desde un asistente.
     * 
     * Si el asistente representa múltiples inmuebles, se replicará el voto
     * para cada inmueble que el asistente representa.
     * 
     * @param int $preguntaId ID de la pregunta
     * @param int $opcionId ID de la opción seleccionada
     * @param int $asistenteId ID del asistente que vota
     * @param string|null $telefono Teléfono desde el cual se votó (opcional)
     * @return array Array con los votos registrados
     * @throws \RuntimeException Si la validación falla
     */
    /**
     * Registrar un voto desde un asistente (despacha job).
     * 
     * Si el asistente representa múltiples inmuebles, se replicará el voto
     * para cada inmueble que el asistente representa.
     * 
     * IMPORTANTE: En FASE 7, todas las escrituras van por cola.
     * Este método ahora despacha un job en lugar de escribir directamente.
     * 
     * @param int $preguntaId ID de la pregunta
     * @param int $opcionId ID de la opción seleccionada
     * @param int $asistenteId ID del asistente que vota
     * @param string|null $telefono Teléfono desde el cual se votó (opcional)
     * @return void
     */
    public function registrarVotoDesdeAsistente(
        int $preguntaId,
        int $opcionId,
        int $asistenteId,
        ?string $telefono = null
    ): void {
        // Despachar job para procesar los votos de forma asíncrona
        \App\Jobs\Votaciones\RegistrarVotoDesdeAsistenteJob::dispatch(
            $preguntaId,
            $opcionId,
            $asistenteId,
            $telefono
        );
    }

    /**
     * Obtener los resultados de una pregunta.
     * 
     * @param int $preguntaId ID de la pregunta
     * @return array Resultados de la votación
     */
    public function obtenerResultados(int $preguntaId): array
    {
        $pregunta = Pregunta::findOrFail($preguntaId);
        return $pregunta->obtenerResultados();
    }
}
