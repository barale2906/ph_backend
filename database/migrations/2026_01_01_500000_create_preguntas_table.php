<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Esta migración crea la tabla de preguntas (votaciones) para cada PH.
     * Las preguntas son los temas que se votan en las reuniones.
     */
    public function up(): void
    {
        Schema::create('preguntas', function (Blueprint $table) {
            $table->id()->comment('Identificador único de la pregunta/votación');
            $table->foreignId('reunion_id')->constrained('reuniones')->onDelete('cascade')->comment('ID de la reunión a la que pertenece esta pregunta');
            $table->text('pregunta')->comment('Texto completo de la pregunta o tema a votar');
            $table->string('estado')->default('abierta')->comment('Estado de la votación: abierta (aceptando votos), cerrada (finalizada), cancelada');
            $table->timestamp('apertura_at')->nullable()->comment('Momento exacto en que se abrió la votación (timestamp del servidor)');
            $table->timestamp('cierre_at')->nullable()->comment('Momento exacto en que se cerró la votación (timestamp del servidor)');
            $table->integer('orden')->default(0)->comment('Orden de presentación de la pregunta en el orden del día de la reunión');
            $table->timestamps();

            // Índices
            $table->index('reunion_id');
            $table->index('estado');
            $table->index(['reunion_id', 'orden']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preguntas');
    }
};
