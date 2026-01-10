<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    /**
     * Configurar conexión ph_database para pruebas.
     * 
     * En pruebas, ph_database apunta a la misma base de datos de prueba
     * para simplificar y acelerar las pruebas.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Configurar ph_database para que apunte a la base de pruebas
        $defaultConnection = config('database.default');
        $defaultConfig = config("database.connections.{$defaultConnection}");
        
        // ph_database usa la misma configuración que la conexión por defecto
        Config::set('database.connections.ph_database', $defaultConfig);
        DB::purge('ph_database');
    }
}
