<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración de códigos de barras
    |--------------------------------------------------------------------------
    |
    | Parámetros usados para generar e imprimir códigos de barras.
    | Estos valores pueden ajustarse por PH sin afectar la lógica de votación.
    |
    */

    'formato' => 'CODE_128',

    // Ancho del factor del código de barras (afecta la densidad)
    'ancho' => 2,

    // Alto del código en píxeles
    'alto' => 60,

    // Margen en píxeles alrededor de cada código
    'margen' => 8,

    // Resolución usada al renderizar PDF
    'dpi' => 300,
];
