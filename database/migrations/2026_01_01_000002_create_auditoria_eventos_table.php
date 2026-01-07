<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('auditoria_eventos', function (Blueprint $table) {
            $table->id();
            $table->string('evento'); // inicio_asamblea, cierre_asamblea, inicio_votacion, cierre_votacion, registro_asistente, voto_recibido, error_critico
            $table->string('tipo'); // asamblea, votacion, asistencia, voto, sistema
            $table->foreignId('ph_id')->nullable()->constrained('phs')->onDelete('set null');
            $table->foreignId('usuario_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('reunion_id')->nullable(); // ID de reunión en la DB del PH
            $table->json('datos')->nullable(); // Datos adicionales del evento
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            // Índices para búsquedas rápidas
            $table->index('evento');
            $table->index('ph_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditoria_eventos');
    }
};

