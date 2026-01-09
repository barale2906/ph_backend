<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configurar Scramble para excluir rutas problemáticas durante el análisis
        if (class_exists(Scramble::class)) {
            Scramble::routes(function ($route) {
                // Scramble pasa cada ruta individualmente
                // Retornar false para excluir, true o la ruta para incluir
                if ($route instanceof \Illuminate\Routing\Route) {
                    $uri = $route->uri();
                    // Excluir la ruta de resultados que requiere DB durante el análisis
                    if (str_contains($uri, 'preguntas') && str_contains($uri, 'resultados')) {
                        return false; // Excluir esta ruta del análisis
                    }
                }
                
                // Incluir todas las demás rutas
                return $route;
            });
        }
    }
}
