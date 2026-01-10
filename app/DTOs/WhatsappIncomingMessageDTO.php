<?php

namespace App\DTOs;

/**
 * DTO para normalizar mensajes entrantes de WhatsApp.
 * 
 * Este DTO debe ser usado tanto por:
 * - Webhook real de Meta
 * - Simulador interno
 * 
 * Garantiza que todos los mensajes tengan el mismo formato
 * independientemente de su origen.
 */
class WhatsappIncomingMessageDTO
{
    /**
     * Número de teléfono del remitente (sin prefijos, solo dígitos).
     * Ejemplo: "573001112233"
     *
     * @var string
     */
    public readonly string $from;

    /**
     * Texto del mensaje normalizado (trimmed, uppercase).
     * Ejemplo: "SI", "NO", "PRESENTE"
     *
     * @var string
     */
    public readonly string $message;

    /**
     * Timestamp del mensaje (Unix timestamp).
     *
     * @var int
     */
    public readonly int $timestamp;

    /**
     * ID único del mensaje de WhatsApp.
     * Ejemplo: "wamid.xxx"
     *
     * @var string
     */
    public readonly string $messageId;

    /**
     * Payload completo original del webhook (JSON).
     * Útil para auditoría y debugging.
     *
     * @var array
     */
    public readonly array $rawPayload;

    /**
     * Constructor.
     *
     * @param string $from Número de teléfono del remitente
     * @param string $message Texto del mensaje
     * @param int $timestamp Timestamp del mensaje
     * @param string $messageId ID único del mensaje
     * @param array $rawPayload Payload original completo
     */
    public function __construct(
        string $from,
        string $message,
        int $timestamp,
        string $messageId,
        array $rawPayload = []
    ) {
        // Normalizar teléfono: remover espacios, guiones, paréntesis, etc.
        $this->from = preg_replace('/[^0-9]/', '', $from);

        // Normalizar mensaje: trim y uppercase
        $this->message = strtoupper(trim($message));

        $this->timestamp = $timestamp;
        $this->messageId = $messageId;
        $this->rawPayload = $rawPayload;
    }

    /**
     * Crea un DTO desde un mensaje individual del webhook de Meta.
     *
     * @param array $message Datos del mensaje individual
     * @param array $fullPayload Payload completo para auditoría
     * @return self
     * @throws \InvalidArgumentException Si el mensaje no es válido
     */
    public static function fromMetaMessage(array $message, array $fullPayload = []): self
    {
        // Estructura esperada del mensaje de Meta:
        // {
        //   "from": "573001112233",
        //   "id": "wamid.xxx",
        //   "timestamp": "1234567890",
        //   "type": "text",
        //   "text": { "body": "SI" }
        // }

        if (empty($message)) {
            throw new \InvalidArgumentException('Mensaje de Meta vacío');
        }

        // Solo procesamos mensajes de tipo texto
        if (($message['type'] ?? '') !== 'text') {
            throw new \InvalidArgumentException('Solo se procesan mensajes de tipo texto');
        }

        $text = $message['text']['body'] ?? '';
        if (empty($text)) {
            throw new \InvalidArgumentException('Mensaje de texto vacío');
        }

        if (empty($message['from'])) {
            throw new \InvalidArgumentException('Mensaje sin remitente');
        }

        if (empty($message['id'])) {
            throw new \InvalidArgumentException('Mensaje sin ID');
        }

        return new self(
            from: $message['from'],
            message: $text,
            timestamp: (int) ($message['timestamp'] ?? time()),
            messageId: $message['id'],
            rawPayload: $fullPayload ?: $message
        );
    }

    /**
     * Crea un DTO desde el payload completo del webhook de Meta.
     * 
     * Extrae el primer mensaje del payload.
     * 
     * @deprecated Usar fromMetaMessage() en su lugar para mayor control
     * @param array $payload Payload del webhook de Meta
     * @return self
     * @throws \InvalidArgumentException Si el payload no es válido
     */
    public static function fromMetaWebhook(array $payload): self
    {
        $entry = $payload['entry'][0] ?? null;
        if (!$entry) {
            throw new \InvalidArgumentException('Payload de Meta inválido: falta "entry"');
        }

        $change = $entry['changes'][0] ?? null;
        if (!$change) {
            throw new \InvalidArgumentException('Payload de Meta inválido: falta "changes"');
        }

        $value = $change['value'] ?? null;
        if (!$value) {
            throw new \InvalidArgumentException('Payload de Meta inválido: falta "value"');
        }

        $message = $value['messages'][0] ?? null;
        if (!$message) {
            throw new \InvalidArgumentException('Payload de Meta inválido: falta "messages"');
        }

        return self::fromMetaMessage($message, $payload);
    }

    /**
     * Crea un DTO desde el simulador interno.
     *
     * @param array $data Datos del simulador
     * @return self
     * @throws \InvalidArgumentException Si los datos no son válidos
     */
    public static function fromSimulator(array $data): self
    {
        if (empty($data['from'])) {
            throw new \InvalidArgumentException('Campo "from" requerido');
        }

        if (empty($data['message'])) {
            throw new \InvalidArgumentException('Campo "message" requerido');
        }

        return new self(
            from: $data['from'],
            message: $data['message'],
            timestamp: $data['timestamp'] ?? time(),
            messageId: $data['message_id'] ?? 'sim_' . uniqid(),
            rawPayload: $data
        );
    }

    /**
     * Convierte el DTO a array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'from' => $this->from,
            'message' => $this->message,
            'timestamp' => $this->timestamp,
            'message_id' => $this->messageId,
            'raw_payload' => $this->rawPayload,
        ];
    }
}
