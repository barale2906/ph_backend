<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Esta migración crea la tabla de reuniones (asambleas) para cada PH.
     * Las reuniones pueden ser ordinarias o extraordinarias.
     */
    public function up(): void
    {
        Schema::create('reuniones', function (Blueprint $table) {
            $table->id()->comment('Identificador único de la reunión');
            $table->string('tipo')->comment('Tipo de reunión: ordinaria (anual, periódica) o extraordinaria (especial)');
            $table->date('fecha')->comment('Fecha programada de la reunión');
            $table->time('hora')->comment('Hora programada de inicio de la reunión');
            $table->string('modalidad')->comment('Modalidad de la reunión: presencial, virtual o mixta');
            $table->string('estado')->default('programada')->comment('Estado actual: programada, en_curso, finalizada, cancelada');
            $table->timestamp('inicio_at')->nullable()->comment('Momento real en que se inició la reunión (timestamp del servidor)');
            $table->timestamp('cierre_at')->nullable()->comment('Momento real en que se cerró la reunión (timestamp del servidor)');
            $table->timestamps();

            // Índices
            $table->index('fecha');
            $table->index('estado');
            $table->index(['fecha', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reuniones');
    }
};
