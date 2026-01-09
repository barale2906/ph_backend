<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Esta migración crea la tabla de orden del día para cada PH.
     * El orden del día contiene los puntos que se tratarán en la reunión.
     */
    public function up(): void
    {
        Schema::create('orden_dia', function (Blueprint $table) {
            $table->id()->comment('Identificador único del punto del orden del día');
            $table->foreignId('reunion_id')->constrained('reuniones')->onDelete('cascade')->comment('ID de la reunión a la que pertenece este punto');
            $table->integer('orden')->comment('Orden numérico de presentación del punto en el orden del día (1, 2, 3, etc.)');
            $table->string('titulo')->comment('Título o nombre del punto del orden del día');
            $table->text('descripcion')->nullable()->comment('Descripción detallada del punto a tratar');
            $table->string('tipo')->nullable()->comment('Tipo de punto: información, votación, elección, aprobación, etc.');
            $table->foreignId('pregunta_id')->nullable()->constrained('preguntas')->onDelete('set null')->comment('ID de la pregunta/votación asociada a este punto (si aplica)');
            $table->boolean('tratado')->default(false)->comment('Indica si el punto ya fue tratado en la reunión (true) o está pendiente (false)');
            $table->timestamps();

            // Índices
            $table->index('reunion_id');
            $table->index(['reunion_id', 'orden']);
            $table->index('pregunta_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orden_dia');
    }
};
