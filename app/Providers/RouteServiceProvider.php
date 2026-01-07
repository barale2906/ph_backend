<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Configure the rate limiters for the application.
     */
    public function boot(): void
    {
        // Rate limiting para registro de asistencia
        RateLimiter::for('asistencia', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Rate limiting para votaciones
        RateLimiter::for('votaciones', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?? $request->ip());
        });

        // Rate limiting para webhooks
        RateLimiter::for('webhooks', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // Rate limiting general para API
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?? $request->ip());
        });
    }
}

