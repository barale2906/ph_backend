<?php

namespace App\Services;

use App\Models\Reuniones\Reunion;
use App\Models\Asistentes\Asistente;
use App\Models\Votaciones\Pregunta;
use App\Models\Inmuebles\Inmueble;
use App\Services\QuorumService;
use Illuminate\Support\Facades\DB;

/**
 * Servicio para generar reportes del sistema de asambleas PH.
 * 
 * Este servicio centraliza la lógica de generación de reportes en diferentes formatos:
 * - PDF: Para actas formales y documentos legales
 * - Excel: Para análisis de datos y exportaciones masivas
 * - Word: Para documentos editables y actas formales
 * 
 * Todos los reportes se generan desde la base de datos del PH actual (ph_database).
 */
class ReporteService
{
    /**
     * Instancia del servicio de quórum.
     * 
     * @var QuorumService
     */
    protected QuorumService $quorumService;

    /**
     * Constructor.
     * 
     * @param QuorumService $quorumService
     */
    public function __construct(QuorumService $quorumService)
    {
        $this->quorumService = $quorumService;
    }

    /**
     * Obtiene los datos completos de una reunión para reportes.
     * 
     * @param int $reunionId ID de la reunión
     * @return array Datos completos de la reunión
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function obtenerDatosReunion(int $reunionId): array
    {
        $reunion = Reunion::with([
            'preguntas.opciones',
            'preguntas.votos.opcion',
            'preguntas.votos.inmueble',
            'ordenDia',
            'timers',
        ])->findOrFail($reunionId);

        // Obtener asistentes con sus inmuebles
        $asistentes = Asistente::with('inmuebles')->get();

        // Calcular quórum
        $quorum = $this->quorumService->obtener($reunionId);

        // Obtener información del PH desde el request
        $ph = request()->attributes->get('ph');

        return [
            'reunion' => $reunion,
            'asistentes' => $asistentes,
            'quorum' => $quorum,
            'ph' => $ph,
        ];
    }

    /**
     * Obtiene los datos de resultados de votación para una pregunta específica.
     * 
     * @param int $preguntaId ID de la pregunta
     * @return array Datos de la votación
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function obtenerDatosVotacion(int $preguntaId): array
    {
        $pregunta = Pregunta::with([
            'reunion',
            'opciones',
            'votos.opcion',
            'votos.inmueble',
        ])->findOrFail($preguntaId);

        // Calcular resultados
        $resultados = $pregunta->obtenerResultados();

        // Obtener información del PH
        $ph = request()->attributes->get('ph');

        return [
            'pregunta' => $pregunta,
            'resultados' => $resultados,
            'ph' => $ph,
        ];
    }

    /**
     * Obtiene los datos de asistentes para reportes.
     * 
     * @param int|null $reunionId ID de la reunión (opcional)
     * @return array Datos de asistentes
     */
    public function obtenerDatosAsistentes(?int $reunionId = null): array
    {
        $asistentes = Asistente::with('inmuebles')->get();

        // Calcular quórum si hay reunión
        $quorum = $reunionId ? $this->quorumService->obtener($reunionId) : null;

        // Obtener información del PH
        $ph = request()->attributes->get('ph');

        return [
            'asistentes' => $asistentes,
            'quorum' => $quorum,
            'ph' => $ph,
            'reunion_id' => $reunionId,
        ];
    }

    /**
     * Obtiene los datos de inmuebles para reportes.
     * 
     * @return array Datos de inmuebles
     */
    public function obtenerDatosInmuebles(): array
    {
        $inmuebles = Inmueble::with('asistentes')->get();

        // Obtener información del PH
        $ph = request()->attributes->get('ph');

        return [
            'inmuebles' => $inmuebles,
            'ph' => $ph,
        ];
    }

    /**
     * Obtiene estadísticas generales de la reunión.
     * 
     * @param int $reunionId ID de la reunión
     * @return array Estadísticas
     */
    public function obtenerEstadisticasReunion(int $reunionId): array
    {
        $reunion = Reunion::with('preguntas')->findOrFail($reunionId);

        $totalPreguntas = $reunion->preguntas->count();
        $preguntasAbiertas = $reunion->preguntas->where('estado', 'abierta')->count();
        $preguntasCerradas = $reunion->preguntas->where('estado', 'cerrada')->count();

        $totalVotos = DB::table('votos')
            ->whereIn('pregunta_id', $reunion->preguntas->pluck('id'))
            ->count();

        $quorum = $this->quorumService->obtener($reunionId);
        $totalAsistentes = Asistente::count();
        $totalInmuebles = Inmueble::where('activo', true)->count();

        return [
            'total_preguntas' => $totalPreguntas,
            'preguntas_abiertas' => $preguntasAbiertas,
            'preguntas_cerradas' => $preguntasCerradas,
            'total_votos' => $totalVotos,
            'quorum' => $quorum,
            'total_asistentes' => $totalAsistentes,
            'total_inmuebles' => $totalInmuebles,
        ];
    }
}
