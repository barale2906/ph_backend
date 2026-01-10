<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Asistentes\Asistente;
use App\Models\Votaciones\Pregunta;
use App\Models\Votaciones\Opcion;
use App\Services\VotacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Controlador para simular mensajes de WhatsApp.
 * 
 * Este endpoint simula la recepción de mensajes de WhatsApp sin usar SDKs de Meta.
 * Usa la MISMA lógica que usará WhatsApp real.
 * 
 * IMPORTANTE: Este es un endpoint interno para pruebas. NO debe estar expuesto públicamente.
 */
class SimulateMessageController extends Controller
{
    public function __construct(
        protected VotacionService $votacionService
    ) {
        //
    }

    /**
     * Simular recepción de mensaje de WhatsApp.
     * 
     * Este endpoint simula la recepción de un mensaje de WhatsApp y procesa
     * el voto o asistencia según corresponda.
     * 
     * Payload esperado:
     * {
     *   "from": "573001112233",
     *   "message": "SI"
     * }
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function simulateMessage(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'from' => 'required|string',
                'message' => 'required|string',
            ]);

            $telefono = $validated['from'];
            $mensaje = strtoupper(trim($validated['message']));
            
            // El NIT debe venir del middleware tenant o del request
            $nit = $request->header('X-PH-NIT') ?? $request->input('nit');
            
            if (!$nit) {
                return response()->json([
                    'success' => false,
                    'error' => 'NIT requerido',
                    'message' => 'Debe proporcionar el NIT del PH en el header X-PH-NIT o en el body como "nit"'
                ], 400);
            }

            Log::info('Simulando mensaje de WhatsApp', [
                'from' => $telefono,
                'message' => $mensaje,
            ]);

            // Buscar asistente por teléfono
            $asistente = Asistente::where('telefono', $telefono)->first();

            if (!$asistente) {
                return response()->json([
                    'success' => false,
                    'error' => 'Asistente no encontrado',
                    'message' => "No se encontró un asistente con el teléfono: {$telefono}"
                ], 404);
            }

            // Buscar pregunta abierta en la reunión activa
            // Asumimos que hay una pregunta abierta (en producción, esto se determinaría por contexto)
            $pregunta = Pregunta::where('estado', 'abierta')
                ->orderBy('apertura_at', 'desc')
                ->first();

            if (!$pregunta) {
                return response()->json([
                    'success' => false,
                    'error' => 'No hay pregunta abierta',
                    'message' => 'No hay ninguna pregunta abierta para votar en este momento'
                ], 404);
            }

            // Mapear mensaje a opción
            $opcion = $this->mapearMensajeAOpcion($pregunta, $mensaje);

            if (!$opcion) {
                return response()->json([
                    'success' => false,
                    'error' => 'Opción no válida',
                    'message' => "El mensaje '{$mensaje}' no corresponde a ninguna opción válida. " .
                                "Opciones disponibles: " . $pregunta->opciones->pluck('texto')->join(', ')
                ], 422);
            }

            // Registrar voto desde el asistente
            // Esto replicará el voto para todos los inmuebles que el asistente representa
            $this->votacionService->registrarVotoDesdeAsistente(
                preguntaId: $pregunta->id,
                opcionId: $opcion->id,
                asistenteId: $asistente->id,
                telefono: $telefono
            );

            Log::info('Voto registrado desde simulador WhatsApp', [
                'asistente_id' => $asistente->id,
                'pregunta_id' => $pregunta->id,
                'opcion_id' => $opcion->id,
                'telefono' => $telefono,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Voto registrado exitosamente',
                'data' => [
                    'asistente_id' => $asistente->id,
                    'pregunta_id' => $pregunta->id,
                    'opcion' => $opcion->texto,
                    'status' => 'queued', // Se procesará de forma asíncrona
                ]
            ], 202);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validación fallida',
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);

        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al procesar voto',
                'message' => $e->getMessage()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error al simular mensaje de WhatsApp', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno',
                'message' => 'Ocurrió un error al procesar el mensaje'
            ], 500);
        }
    }

    /**
     * Mapea un mensaje de texto a una opción de la pregunta.
     * 
     * Soporta múltiples formatos:
     * - "SI", "SÍ", "YES", "1"
     * - "NO", "NOT", "0"
     * - Texto exacto de la opción
     * 
     * @param Pregunta $pregunta
     * @param string $mensaje
     * @return Opcion|null
     */
    protected function mapearMensajeAOpcion(Pregunta $pregunta, string $mensaje): ?Opcion
    {
        // Cargar opciones si no están cargadas
        if (!$pregunta->relationLoaded('opciones')) {
            $pregunta->load('opciones');
        }

        // Mapeo de respuestas comunes
        $mapeo = [
            'SI' => ['SI', 'SÍ', 'YES', '1', 'S'],
            'NO' => ['NO', 'NOT', '0', 'N'],
        ];

        // Buscar por texto exacto primero
        $opcion = $pregunta->opciones->firstWhere('texto', $mensaje);
        if ($opcion) {
            return $opcion;
        }

        // Buscar por mapeo común
        foreach ($mapeo as $clave => $valores) {
            if (in_array($mensaje, $valores)) {
                $opcion = $pregunta->opciones->firstWhere('texto', $clave);
                if ($opcion) {
                    return $opcion;
                }
            }
        }

        // Buscar por coincidencia parcial (case-insensitive)
        $opcion = $pregunta->opciones->first(function ($opcion) use ($mensaje) {
            return stripos($opcion->texto, $mensaje) !== false || 
                   stripos($mensaje, $opcion->texto) !== false;
        });

        return $opcion;
    }
}
