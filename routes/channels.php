<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Canal privado para reuniones
// Formato: private-reunion.{reunionId}
Broadcast::channel('reunion.{reunionId}', function ($user, $reunionId) {
    // Verificar que el usuario tenga acceso a la reunión
    // Esto se hace verificando que el usuario tenga acceso al PH de la reunión
    
    $reunion = \App\Models\Reuniones\Reunion::find($reunionId);
    
    if (!$reunion) {
        return false;
    }
    
    // Superadmin tiene acceso a todo
    if ($user->esSuperAdmin()) {
        return true;
    }
    
    // Verificar que el usuario tenga acceso al PH de la reunión
    // TODO: Implementar verificación específica cuando tengamos la relación PH-Reunión
    // Por ahora, permitimos acceso si el usuario está autenticado
    return $user !== null;
});
