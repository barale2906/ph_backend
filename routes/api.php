<?php

use App\Http\Controllers\Asistentes\AsistenteController;
use App\Http\Controllers\Inmuebles\InmuebleController;
use App\Http\Controllers\Phs\PhController;
use App\Http\Controllers\Reuniones\ReunionController;
use App\Http\Controllers\Timers\TimerController;
use App\Http\Controllers\Votaciones\OpcionController;
use App\Http\Controllers\Votaciones\PreguntaController;
use App\Http\Controllers\Votaciones\VotoController;
use Illuminate\Support\Facades\Route;

/**
 * Health Check
 * 
 * Endpoint para verificar el estado del servidor.
 * 
 * @response 200 {
 *   "status": "ok"
 * }
 */
Route::get('/health', fn() => response()->json(['status' => 'ok']));

// ============================================
// RUTAS MASTER (No requieren tenant)
// ============================================

/**
 * Rutas de PHs (Propiedades Horizontales)
 * 
 * Estas rutas no requieren tenant porque gestionan las PHs
 * en la base de datos maestra.
 */
Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('phs', PhController::class);
});

// ============================================
// RUTAS POR PH (Requieren tenant)
// ============================================

/**
 * Todas las rutas siguientes requieren:
 * - Autenticación (auth:sanctum)
 * - Middleware tenant (resuelve la base de datos de la PH)
 * 
 * El middleware 'tenant' extrae el NIT del header, payload o ruta
 * y configura automáticamente la conexión a la base de datos de la PH.
 */
Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    
    // ============================================
    // REUNIONES
    // ============================================
    
    /**
     * @group Reuniones
     * 
     * Rutas para gestionar reuniones (asambleas).
     */
    Route::apiResource('reuniones', ReunionController::class);
    Route::post('reuniones/{reunion}/iniciar', [ReunionController::class, 'iniciar'])->name('reuniones.iniciar');
    Route::post('reuniones/{reunion}/cerrar', [ReunionController::class, 'cerrar'])->name('reuniones.cerrar');
    
    // ============================================
    // INMUEBLES
    // ============================================
    
    /**
     * @group Inmuebles
     * 
     * Rutas para gestionar inmuebles.
     */
    Route::apiResource('inmuebles', InmuebleController::class);
    
    // ============================================
    // ASISTENTES
    // ============================================
    
    /**
     * @group Asistentes
     * 
     * Rutas para gestionar asistentes.
     */
    Route::apiResource('asistentes', AsistenteController::class);
    
    // ============================================
    // TIMERS (CRONÓMETROS)
    // ============================================
    
    /**
     * @group Timers
     * 
     * Rutas para gestionar cronómetros.
     */
    Route::apiResource('timers', TimerController::class);
    Route::post('timers/{timer}/iniciar', [TimerController::class, 'iniciar'])->name('timers.iniciar');
    Route::post('timers/{timer}/pausar', [TimerController::class, 'pausar'])->name('timers.pausar');
    
    // ============================================
    // VOTACIONES - PREGUNTAS
    // ============================================
    
    /**
     * @group Votaciones - Preguntas
     * 
     * Rutas para gestionar preguntas de votación.
     */
    Route::apiResource('preguntas', PreguntaController::class);
    Route::post('preguntas/{pregunta}/abrir', [PreguntaController::class, 'abrir'])->name('preguntas.abrir');
    Route::post('preguntas/{pregunta}/cerrar', [PreguntaController::class, 'cerrar'])->name('preguntas.cerrar');
    Route::get('preguntas/{pregunta}/resultados', [PreguntaController::class, 'resultados'])
        ->name('preguntas.resultados')
        ->where('pregunta', '[0-9]+');
    
    // ============================================
    // VOTACIONES - OPCIONES
    // ============================================
    
    /**
     * @group Votaciones - Opciones
     * 
     * Rutas para gestionar opciones de respuesta.
     */
    Route::apiResource('opciones', OpcionController::class);
    
    // ============================================
    // VOTACIONES - VOTOS
    // ============================================
    
    /**
     * @group Votaciones - Votos
     * 
     * Rutas para gestionar votos.
     * IMPORTANTE: Los votos son inmutables una vez registrados.
     */
    Route::apiResource('votos', VotoController::class)->only(['index', 'store', 'show']);
});