<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Preparación para futuras tareas de IA (fase 11).
     */
    public function up(): void
    {
        Schema::create('ia_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ph_id')->nullable()->constrained('phs')->onDelete('set null');
            $table->string('reunion_id')->nullable();
            $table->string('tipo')->comment('Tipo de tarea de IA (transcripcion, resumen, decisiones, etc.)');
            $table->string('estado')->default('pendiente'); // pendiente, en_proceso, completado, fallido
            $table->json('parametros')->nullable();
            $table->json('resultado')->nullable();
            $table->timestamps();

            $table->index(['ph_id', 'reunion_id']);
            $table->index('estado');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ia_jobs');
    }
};

