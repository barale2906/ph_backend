<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Esta migración crea la tabla de estamentos para cada PH.
     * Los estamentos son tipos o categorías de asistentes (propietario, arrendatario, etc.).
     */
    public function up(): void
    {
        Schema::create('estamentos', function (Blueprint $table) {
            $table->id()->comment('Identificador único del estamento');
            $table->string('nombre')->unique()->comment('Nombre único del estamento (ej: Propietario, Arrendatario, Administrador, Representante Legal)');
            $table->text('descripcion')->nullable()->comment('Descripción detallada del estamento y sus características');
            $table->boolean('activo')->default(true)->comment('Indica si el estamento está activo (true) y disponible para asignar o inactivo (false)');
            $table->timestamps();

            // Índices
            $table->index('nombre');
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estamentos');
    }
};
