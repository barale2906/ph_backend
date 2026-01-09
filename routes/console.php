<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Programar el cierre automático de timers expirados cada minuto
Schedule::command('timers:cerrar-expirados')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
