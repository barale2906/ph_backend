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
        Schema::create('usuario_ph', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('ph_id')->constrained('phs')->onDelete('cascade');
            $table->string('rol')->default('LECTURA'); // Rol específico para este PH
            $table->timestamps();

            // Evitar duplicados
            $table->unique(['usuario_id', 'ph_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuario_ph');
    }
};

