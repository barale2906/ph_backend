<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Cloud API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para la integración con Meta WhatsApp Cloud API.
    | Todas las variables deben estar definidas en el archivo .env
    |
    */

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Enabled
    |--------------------------------------------------------------------------
    |
    | Habilita o deshabilita la integración con WhatsApp.
    | Cuando está deshabilitado, el webhook rechazará las solicitudes.
    |
    */
    'enabled' => env('WHATSAPP_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Access Token
    |--------------------------------------------------------------------------
    |
    | Token de acceso permanente de Meta WhatsApp Cloud API.
    | Obtener desde: https://developers.facebook.com/apps
    |
    | ⚠️ NUNCA subir este token al repositorio.
    |
    */
    'token' => env('WHATSAPP_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Verify Token
    |--------------------------------------------------------------------------
    |
    | Token de verificación para el webhook de Meta.
    | Debe coincidir con el configurado en Meta Business Manager.
    |
    | ⚠️ NUNCA subir este token al repositorio.
    |
    */
    'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Phone Number ID
    |--------------------------------------------------------------------------
    |
    | ID del número de teléfono de WhatsApp Business.
    | Obtener desde: Meta Business Manager > WhatsApp > API Setup
    |
    */
    'phone_id' => env('WHATSAPP_PHONE_ID'),

    /*
    |--------------------------------------------------------------------------
    | Business Account ID
    |--------------------------------------------------------------------------
    |
    | ID de la cuenta de negocio de WhatsApp.
    | Obtener desde: Meta Business Manager
    |
    */
    'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),

    /*
    |--------------------------------------------------------------------------
    | API Base URL
    |--------------------------------------------------------------------------
    |
    | URL base de la API de Meta WhatsApp Cloud API.
    |
    */
    'api_base_url' => env('WHATSAPP_API_BASE_URL', 'https://graph.facebook.com/v21.0'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Tiempo máximo de espera para las solicitudes HTTP a la API de Meta (segundos).
    |
    */
    'timeout' => env('WHATSAPP_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Rate Limit
    |--------------------------------------------------------------------------
    |
    | Límite de mensajes por segundo que se pueden enviar.
    | Meta permite hasta 1000 mensajes por segundo para números verificados.
    | Para números no verificados, el límite es menor.
    |
    */
    'rate_limit' => env('WHATSAPP_RATE_LIMIT', 80),

    /*
    |--------------------------------------------------------------------------
    | Retry Attempts
    |--------------------------------------------------------------------------
    |
    | Número máximo de intentos de reintento para envío de mensajes fallidos.
    |
    */
    'retry_attempts' => env('WHATSAPP_RETRY_ATTEMPTS', 3),

    /*
    |--------------------------------------------------------------------------
    | Queue Name
    |--------------------------------------------------------------------------
    |
    | Nombre de la cola para procesar mensajes de WhatsApp.
    |
    */
    'queue' => env('WHATSAPP_QUEUE', 'whatsapp'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Secret
    |--------------------------------------------------------------------------
    |
    | Secreto para validar la firma de los webhooks de Meta.
    | Obtener desde: Meta Business Manager > Webhooks > App Secret
    |
    | ⚠️ NUNCA subir este secreto al repositorio.
    |
    */
    'webhook_secret' => env('WHATSAPP_WEBHOOK_SECRET'),

];
