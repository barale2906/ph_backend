<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Esta migración crea la tabla de inmuebles para cada PH.
     * Los inmuebles representan apartamentos, locales u otros espacios
     * dentro de la Propiedad Horizontal.
     */
    public function up(): void
    {
        Schema::create('inmuebles', function (Blueprint $table) {
            $table->id()->comment('Identificador único del inmueble');
            $table->string('nomenclatura')->unique()->comment('Código único del inmueble (ej: APT-101, LOC-01, PAR-05)');
            $table->decimal('coeficiente', 5, 2)->comment('Coeficiente de copropiedad del inmueble expresado como porcentaje (ej: 2.50 = 2.50%)');
            $table->string('tipo')->comment('Tipo de inmueble: apartamento, local, parqueadero, bodega, etc.');
            $table->string('propietario_documento')->nullable()->comment('Número de documento de identidad del propietario (cédula o NIT)');
            $table->string('propietario_nombre')->nullable()->comment('Nombre completo o razón social del propietario');
            $table->string('telefono')->nullable()->comment('Número de teléfono de contacto del propietario');
            $table->string('email')->nullable()->comment('Correo electrónico de contacto del propietario');
            $table->boolean('activo')->default(true)->comment('Indica si el inmueble está activo en el sistema (true) o inactivo (false)');
            $table->timestamps();

            // Índices para búsquedas rápidas
            $table->index('nomenclatura');
            $table->index('propietario_documento');
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inmuebles');
    }
};
