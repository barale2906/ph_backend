<?php

namespace App\Http\Middleware;

use App\Services\TenantResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantDatabase
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Extraer NIT del header o del payload
        $nit = $request->header('X-PH-NIT') 
            ?? $request->input('nit')
            ?? $request->route('nit');

        if (!$nit) {
            return response()->json([
                'error' => 'NIT de PH no proporcionado'
            ], 400);
        }

        // Resolver PH
        $tenantResolver = app(TenantResolver::class);
        $ph = $tenantResolver->resolve($nit);

        if (!$ph) {
            return response()->json([
                'error' => 'PH no encontrado o inactivo'
            ], 403);
        }

        // Validar que el usuario autenticado tiene acceso a este PH
        if ($request->user()) {
            $user = $request->user();
            if (!$user->tieneAccesoPh($ph->id)) {
                return response()->json([
                    'error' => 'No autorizado para acceder a este PH'
                ], 403);
            }
        }

        // Agregar PH al request para uso posterior
        $request->merge(['ph' => $ph]);
        $request->attributes->set('ph', $ph);

        return $next($request);
    }
}

