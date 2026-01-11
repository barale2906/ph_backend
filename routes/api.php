<?php

use App\Http\Controllers\Asistentes\AsistenteController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Barcodes\BarcodePrintController;
use App\Http\Controllers\Inmuebles\InmuebleController;
use App\Http\Controllers\Internal\SimulateMessageController;
use App\Http\Controllers\Phs\PhController;
use App\Http\Controllers\Reuniones\ReunionController;
use App\Http\Controllers\Reportes\ReporteController;
use App\Http\Controllers\Timers\TimerController;
use App\Http\Controllers\Users\UserController;
use App\Http\Controllers\Votaciones\OpcionController;
use App\Http\Controllers\Votaciones\PreguntaController;
use App\Http\Controllers\Votaciones\VotoController;
use App\Http\Controllers\Whatsapp\WhatsappWebhookController;
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
// AUTENTICACIÓN (Público - Login)
// ============================================

/**
 * @group Autenticación
 * 
 * Rutas públicas para autenticación de usuarios.
 */
Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

// ============================================
// WEBHOOK WHATSAPP (Sin autenticación)
// ============================================

/**
 * Webhook de WhatsApp (Meta Cloud API)
 * 
 * Endpoint público para recibir mensajes de WhatsApp desde Meta.
 * 
 * IMPORTANTE: Este endpoint NO requiere autenticación porque es llamado
 * directamente por los servidores de Meta. La seguridad se garantiza mediante:
 * - Validación de firma X-Hub-Signature-256
 * - Token de verificación (hub_verify_token)
 * 
 * Rutas:
 * - GET /api/webhooks/whatsapp - Verificación del webhook (Meta)
 * - POST /api/webhooks/whatsapp - Recepción de mensajes (Meta)
 */
Route::match(['get', 'post'], '/webhooks/whatsapp', [WhatsappWebhookController::class, 'handle'])
    ->middleware('throttle:webhooks')
    ->name('webhooks.whatsapp');

// ============================================
// RUTAS INTERNAS (Para pruebas y simulación)
// ============================================

/**
 * Simulador de WhatsApp
 * 
 * Endpoint interno para simular mensajes de WhatsApp sin usar SDKs de Meta.
 * Usa la MISMA lógica que usará WhatsApp real.
 * 
 * IMPORTANTE: Este endpoint requiere autenticación y tenant (para configurar la DB del PH).
 */
Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::post('/internal/simulate-message', [SimulateMessageController::class, 'simulateMessage'])
        ->name('internal.simulate-message');
});

// ============================================
// RUTAS MASTER (No requieren tenant)
// ============================================

/**
 * Rutas de autenticación y usuarios
 * 
 * Estas rutas no requieren tenant porque gestionan usuarios
 * en la base de datos maestra.
 */
Route::middleware(['auth:sanctum'])->group(function () {
    // Autenticación
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
    Route::post('/change-password', [AuthController::class, 'changePassword'])->name('auth.change-password');
    
    // Usuarios
    Route::apiResource('users', UserController::class);
    Route::post('users/{user}/assign-ph', [UserController::class, 'assignPh'])->name('users.assign-ph');
    Route::post('users/{user}/remove-ph', [UserController::class, 'removePh'])->name('users.remove-ph');
    
    // PHs
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
    Route::apiResource('asistentes', AsistenteController::class)->middleware('throttle:asistencia');

    // ============================================
    // CÓDIGOS DE BARRAS (IMPRESIÓN)
    // ============================================

    /**
     * @group Códigos de barras
     *
     * Genera códigos de barras listos para imprimir.
     */
    Route::post('barcodes/print', [BarcodePrintController::class, 'imprimir'])
        ->name('barcodes.print');
    
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
    Route::apiResource('votos', VotoController::class)
        ->only(['index', 'store', 'show'])
        ->middleware('throttle:votaciones');
    
    // ============================================
    // REPORTES
    // ============================================
    
    /**
     * @group Reportes
     * 
     * Rutas para generar reportes en diferentes formatos (PDF, Excel, Word).
     * 
     * Todos los reportes se generan desde la base de datos del PH actual.
     */
    
    // Reportes PDF
    Route::get('reportes/reuniones/{reunion}/acta-pdf', [ReporteController::class, 'actaReunionPdf'])
        ->name('reportes.reuniones.acta-pdf')
        ->where('reunion', '[0-9]+');
    Route::get('reportes/asistentes/lista-pdf', [ReporteController::class, 'listaAsistentesPdf'])
        ->name('reportes.asistentes.lista-pdf');
    Route::get('reportes/preguntas/{pregunta}/resultados-pdf', [ReporteController::class, 'resultadosVotacionPdf'])
        ->name('reportes.preguntas.resultados-pdf')
        ->where('pregunta', '[0-9]+');
    
    // Reportes Excel
    Route::get('reportes/reuniones/{reunion}/exportar-excel', [ReporteController::class, 'exportarReunionExcel'])
        ->name('reportes.reuniones.exportar-excel')
        ->where('reunion', '[0-9]+');
    Route::get('reportes/asistentes/exportar-excel', [ReporteController::class, 'exportarAsistentesExcel'])
        ->name('reportes.asistentes.exportar-excel');
    Route::get('reportes/preguntas/{pregunta}/exportar-excel', [ReporteController::class, 'exportarVotacionExcel'])
        ->name('reportes.preguntas.exportar-excel')
        ->where('pregunta', '[0-9]+');
    Route::get('reportes/inmuebles/exportar-excel', [ReporteController::class, 'exportarInmueblesExcel'])
        ->name('reportes.inmuebles.exportar-excel');
    
    // Reportes Word
    Route::get('reportes/reuniones/{reunion}/acta-word', [ReporteController::class, 'actaReunionWord'])
        ->name('reportes.reuniones.acta-word')
        ->where('reunion', '[0-9]+');
    
    // Estadísticas
    Route::get('reportes/reuniones/{reunion}/estadisticas', [ReporteController::class, 'estadisticasReunion'])
        ->name('reportes.reuniones.estadisticas')
        ->where('reunion', '[0-9]+');
});