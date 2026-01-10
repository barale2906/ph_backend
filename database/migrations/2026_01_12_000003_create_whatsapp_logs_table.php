<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabla en la base MASTER para auditar tráfico de WhatsApp.
     * Se usa para trazabilidad y protección contra replay.
     */
    public function up(): void
    {
        Schema::create('whatsapp_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ph_id')->nullable()->constrained('phs')->onDelete('set null');
            $table->string('reunion_id')->nullable();
            $table->string('telefono')->index();
            $table->string('message_id')->unique();
            $table->string('tipo')->default('mensaje_recibido'); // mensaje_recibido, mensaje_enviado, bloqueo, error
            $table->string('estado')->default('procesado'); // procesado, duplicado, rechazado, error
            $table->string('motivo')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['ph_id', 'reunion_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_logs');
    }
};

