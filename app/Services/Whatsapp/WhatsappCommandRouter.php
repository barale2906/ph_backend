<?php

namespace App\Services\Whatsapp;

use App\DTOs\WhatsappIncomingMessageDTO;
use App\Models\Asistentes\Asistente;
use App\Models\Votaciones\Opcion;
use App\Models\Votaciones\Pregunta;
use App\Services\QuorumService;
use App\Services\VotacionService;
use Illuminate\Support\Facades\Log;

/**
 * Router de comandos de WhatsApp.
 * 
 * Responsabilidad:
 * - Interpretar intención del mensaje
 * - Llamar servicios existentes del core
 * 
 * ❌ Prohibido crear lógica nueva aquí.
 * Solo debe llamar a servicios existentes.
 * 
 * Comandos soportados:
 * - SI, NO, A, B, etc. → VotacionService
 * - PRESENTE → QuorumService (información)
 * - AYUDA → Mensaje informativo
 */
class WhatsappCommandRouter
{
    public function __construct(
        protected VotacionService $votacionService,
        protected QuorumService $quorumService
    ) {
        //
    }

    /**
     * Procesa un mensaje de WhatsApp y ejecuta la acción correspondiente.
     * 
     * @param WhatsappIncomingMessageDTO $message Mensaje normalizado
     * @return WhatsappCommandResult Resultado del procesamiento
     */
    public function process(WhatsappIncomingMessageDTO $message): WhatsappCommandResult
    {
        $telefono = $message->from;
        $texto = $message->message;

        Log::info('Procesando comando de WhatsApp', [
            'telefono' => $telefono,
            'mensaje' => $texto,
        ]);

        // Buscar asistente por teléfono
        $asistente = Asistente::where('telefono', $telefono)->first();

        if (!$asistente) {
            return WhatsappCommandResult::error(
                'No estás registrado como asistente. ' .
                'Contacta al administrador para registrarte.'
            );
        }

        // Interpretar comando
        $comando = $this->interpretarComando($texto);

        switch ($comando['tipo']) {
            case 'voto':
                return $this->procesarVoto($asistente, $comando['valor'], $telefono);

            case 'presente':
                return $this->procesarPresente($asistente);

            case 'ayuda':
                return $this->procesarAyuda($asistente);

            default:
                return WhatsappCommandResult::error(
                    'Comando no reconocido. ' .
                    'Envía AYUDA para ver los comandos disponibles.'
                );
        }
    }

    /**
     * Interpreta el tipo de comando del mensaje.
     * 
     * @param string $texto Texto del mensaje
     * @return array ['tipo' => string, 'valor' => mixed]
     */
    protected function interpretarComando(string $texto): array
    {
        $texto = strtoupper(trim($texto));

        // Comando AYUDA
        if (in_array($texto, ['AYUDA', 'HELP', '?', 'INFO'])) {
            return ['tipo' => 'ayuda', 'valor' => null];
        }

        // Comando PRESENTE
        if (in_array($texto, ['PRESENTE', 'PRESENT', 'AQUI', 'AQUÍ'])) {
            return ['tipo' => 'presente', 'valor' => null];
        }

        // Comando de voto (SI, NO, A, B, etc.)
        return ['tipo' => 'voto', 'valor' => $texto];
    }

    /**
     * Procesa un comando de voto.
     * 
     * @param Asistente $asistente
     * @param string $mensajeVoto Mensaje del voto (SI, NO, A, B, etc.)
     * @param string $telefono
     * @return WhatsappCommandResult
     */
    protected function procesarVoto(Asistente $asistente, string $mensajeVoto, string $telefono): WhatsappCommandResult
    {
        // Buscar pregunta abierta
        $pregunta = Pregunta::where('estado', 'abierta')
            ->orderBy('apertura_at', 'desc')
            ->first();

        if (!$pregunta) {
            return WhatsappCommandResult::error(
                'No hay ninguna votación abierta en este momento.'
            );
        }

        // Cargar opciones
        $pregunta->load('opciones');

        // Mapear mensaje a opción
        $opcion = $this->mapearMensajeAOpcion($pregunta, $mensajeVoto);

        if (!$opcion) {
            $opcionesDisponibles = $pregunta->opciones->pluck('texto')->join(', ');
            return WhatsappCommandResult::error(
                "Opción no válida. Opciones disponibles: {$opcionesDisponibles}"
            );
        }

            // Registrar voto (despacha job)
            try {
                $this->votacionService->registrarVotoDesdeAsistente(
                    preguntaId: $pregunta->id,
                    opcionId: $opcion->id,
                    asistenteId: $asistente->id,
                    telefono: $telefono
                );

            return WhatsappCommandResult::success(
                "Voto registrado: {$opcion->texto}",
                ['reunion_id' => $pregunta->reunion_id, 'pregunta_id' => $pregunta->id]
            );

        } catch (\RuntimeException $e) {
            Log::warning('Error al registrar voto desde WhatsApp', [
                'error' => $e->getMessage(),
                'asistente_id' => $asistente->id,
                'pregunta_id' => $pregunta->id,
            ]);

            return WhatsappCommandResult::error(
                'No se pudo registrar el voto. ' . $e->getMessage()
            );
        }
    }

    /**
     * Procesa el comando PRESENTE.
     * 
     * @param Asistente $asistente
     * @return WhatsappCommandResult
     */
    protected function procesarPresente(Asistente $asistente): WhatsappCommandResult
    {
        // Obtener información del quórum
        $quorum = $this->quorumService->obtener();

        $mensaje = "Asistencia confirmada.\n\n";
        $mensaje .= "Quórum actual:\n";
        $mensaje .= "- Inmuebles presentes: {$quorum['total_inmuebles']}\n";
        $mensaje .= "- Coeficientes: {$quorum['suma_coeficientes']}\n";
        $mensaje .= "- Porcentaje: {$quorum['porcentaje']}%";

        return WhatsappCommandResult::success($mensaje);
    }

    /**
     * Procesa el comando AYUDA.
     * 
     * @param Asistente $asistente
     * @return WhatsappCommandResult
     */
    protected function procesarAyuda(Asistente $asistente): WhatsappCommandResult
    {
        $mensaje = "Comandos disponibles:\n\n";
        $mensaje .= "• SI, NO, A, B, etc. - Votar en la pregunta abierta\n";
        $mensaje .= "• PRESENTE - Ver información del quórum\n";
        $mensaje .= "• AYUDA - Ver este mensaje";

        return WhatsappCommandResult::success($mensaje);
    }

    /**
     * Mapea un mensaje de texto a una opción de la pregunta.
     * 
     * @param Pregunta $pregunta
     * @param string $mensaje
     * @return Opcion|null
     */
    protected function mapearMensajeAOpcion(Pregunta $pregunta, string $mensaje): ?Opcion
    {
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
