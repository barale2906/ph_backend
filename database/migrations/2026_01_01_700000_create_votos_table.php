<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Esta migración crea la tabla de votos.
     * IMPORTANTE: Los votos son INMUTABLES (no UPDATE, no DELETE).
     * Un inmueble solo puede votar una vez por pregunta.
     * Si un asistente representa N inmuebles, el voto se replica para cada inmueble.
     */
    public function up(): void
    {
        Schema::create('votos', function (Blueprint $table) {
            $table->id()->comment('Identificador único del voto (los votos son INMUTABLES)');
            $table->foreignId('pregunta_id')->constrained('preguntas')->onDelete('cascade')->comment('ID de la pregunta por la cual se está votando');
            $table->foreignId('inmueble_id')->constrained('inmuebles')->onDelete('cascade')->comment('ID del inmueble que está votando');
            $table->foreignId('opcion_id')->constrained('opciones')->onDelete('cascade')->comment('ID de la opción seleccionada por el inmueble');
            $table->decimal('coeficiente', 5, 2)->comment('Coeficiente de copropiedad del inmueble al momento de emitir el voto (se guarda para mantener consistencia histórica)');
            $table->string('telefono')->nullable()->comment('Número de teléfono desde el cual se emitió el voto (si fue vía WhatsApp)');
            $table->timestamp('votado_at')->useCurrent()->comment('Momento exacto en que se registró el voto (timestamp del servidor, inmutable)');
            $table->timestamps();

            // Índice único: un inmueble solo vota una vez por pregunta
            // Esta es la protección crítica contra votos duplicados
            $table->unique(['pregunta_id', 'inmueble_id']);

            // Índices para consultas rápidas
            $table->index('pregunta_id');
            $table->index('inmueble_id');
            $table->index('opcion_id');
            $table->index('votado_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('votos');
    }
};
