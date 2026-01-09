<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Esta migración crea la tabla pivot entre asistentes e inmuebles.
     * Un asistente puede representar varios inmuebles (mínimo 1).
     * Esta relación permite que un asistente vote por múltiples inmuebles.
     */
    public function up(): void
    {
        Schema::create('asistente_inmueble', function (Blueprint $table) {
            $table->id()->comment('Identificador único de la relación asistente-inmueble');
            $table->foreignId('asistente_id')->constrained('asistentes')->onDelete('cascade')->comment('ID del asistente que representa el inmueble');
            $table->foreignId('inmueble_id')->constrained('inmuebles')->onDelete('cascade')->comment('ID del inmueble que representa el asistente');
            $table->decimal('coeficiente', 5, 2)->comment('Coeficiente de copropiedad que representa este asistente para este inmueble específico (puede ser parcial si hay co-propietarios)');
            $table->string('poder_url')->nullable()->comment('URL o ruta del documento de poder notarial que autoriza al asistente a representar el inmueble');
            $table->timestamps();

            // Evitar duplicados: un asistente no puede representar el mismo inmueble dos veces
            $table->unique(['asistente_id', 'inmueble_id']);

            // Índices
            $table->index('asistente_id');
            $table->index('inmueble_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asistente_inmueble');
    }
};
