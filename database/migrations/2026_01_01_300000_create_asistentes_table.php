<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Esta migración crea la tabla de asistentes para cada PH.
     * Los asistentes son las personas que participan en las reuniones.
     */
    public function up(): void
    {
        Schema::create('asistentes', function (Blueprint $table) {
            $table->id()->comment('Identificador único del asistente');
            $table->string('nombre')->comment('Nombre completo del asistente');
            $table->string('documento')->nullable()->comment('Número de documento de identidad (cédula o NIT). Puede ser nulo en casos donde no sea necesario registrarlo');
            $table->string('telefono')->nullable()->comment('Número de teléfono del asistente (usado para WhatsApp y contacto)');
            $table->string('codigo_acceso')->unique()->nullable()->comment('Código único de acceso para registro vía WhatsApp o Web (generado automáticamente)');
            $table->timestamps();

            // Índices
            $table->index('documento');
            $table->index('codigo_acceso');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asistentes');
    }
};
