<?php

use App\Http\Controllers\Phs\PhController;
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

// Rutas de PHs (requieren autenticación, no requieren tenant)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('phs', PhController::class);
});

