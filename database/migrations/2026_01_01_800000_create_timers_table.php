<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Esta migración crea la tabla de timers (cronómetros) para cada PH.
     * Los timers controlan el tiempo de intervenciones y votaciones.
     * IMPORTANTE: Solo puede haber 1 timer ACTIVO por tipo y reunión.
     * El backend cierra automáticamente los timers cuando expiran.
     */
    public function up(): void
    {
        Schema::create('timers', function (Blueprint $table) {
            $table->id()->comment('Identificador único del cronómetro');
            $table->foreignId('reunion_id')->constrained('reuniones')->onDelete('cascade')->comment('ID de la reunión a la que pertenece este cronómetro');
            $table->string('tipo')->comment('Tipo de cronómetro: INTERVENCION (tiempo para hablar) o VOTACION (tiempo para votar)');
            $table->integer('duracion_segundos')->comment('Duración configurada del cronómetro expresada en segundos (ej: 300 = 5 minutos)');
            $table->timestamp('inicio_at')->nullable()->comment('Momento real en que se inició el cronómetro (timestamp del servidor)');
            $table->timestamp('fin_at')->nullable()->comment('Momento calculado o real en que finaliza el cronómetro (inicio_at + duracion_segundos)');
            $table->string('estado')->default('inactivo')->comment('Estado actual: inactivo, activo (corriendo), pausado, finalizado (cerrado automáticamente por el backend)');
            $table->timestamps();

            // Índices
            $table->index('reunion_id');
            $table->index(['reunion_id', 'tipo', 'estado']);
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timers');
    }
};
