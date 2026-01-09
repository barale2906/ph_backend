<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Esta migración crea la tabla de opciones (respuestas) para las preguntas.
     * Cada pregunta puede tener múltiples opciones de respuesta.
     */
    public function up(): void
    {
        Schema::create('opciones', function (Blueprint $table) {
            $table->id()->comment('Identificador único de la opción de respuesta');
            $table->foreignId('pregunta_id')->constrained('preguntas')->onDelete('cascade')->comment('ID de la pregunta a la que pertenece esta opción');
            $table->string('texto')->comment('Texto de la opción de respuesta (ej: "Sí", "No", "Abstención", "A favor", "En contra")');
            $table->integer('orden')->default(0)->comment('Orden de visualización de la opción en la lista de respuestas disponibles');
            $table->timestamps();

            // Índices
            $table->index('pregunta_id');
            $table->index(['pregunta_id', 'orden']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opciones');
    }
};
