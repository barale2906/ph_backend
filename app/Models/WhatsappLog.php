<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para auditoría de mensajes de WhatsApp en la base MASTER.
 */
class WhatsappLog extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_logs';

    protected $fillable = [
        'ph_id',
        'reunion_id',
        'telefono',
        'message_id',
        'tipo',
        'estado',
        'motivo',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}

