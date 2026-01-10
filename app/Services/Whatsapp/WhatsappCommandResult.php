<?php

namespace App\Services\Whatsapp;

/**
 * Resultado del procesamiento de un comando de WhatsApp.
 * 
 * Encapsula el resultado de procesar un comando, incluyendo
 * si fue exitoso, el mensaje a enviar, y datos adicionales.
 */
class WhatsappCommandResult
{
    /**
     * Indica si el comando se procesó exitosamente.
     *
     * @var bool
     */
    public readonly bool $success;

    /**
     * Mensaje a enviar al usuario.
     *
     * @var string
     */
    public readonly string $message;

    /**
     * Datos adicionales del resultado.
     *
     * @var array
     */
    public readonly array $data;

    /**
     * Constructor.
     *
     * @param bool $success
     * @param string $message
     * @param array $data
     */
    public function __construct(bool $success, string $message, array $data = [])
    {
        $this->success = $success;
        $this->message = $message;
        $this->data = $data;
    }

    /**
     * Crea un resultado exitoso.
     *
     * @param string $message
     * @param array $data
     * @return self
     */
    public static function success(string $message, array $data = []): self
    {
        return new self(true, $message, $data);
    }

    /**
     * Crea un resultado de error.
     *
     * @param string $message
     * @param array $data
     * @return self
     */
    public static function error(string $message, array $data = []): self
    {
        return new self(false, $message, $data);
    }

    /**
     * Verifica si el resultado es exitoso.
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Verifica si el resultado es un error.
     *
     * @return bool
     */
    public function isError(): bool
    {
        return !$this->success;
    }
}
