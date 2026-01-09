<?php

namespace App\Http\Middleware;

use App\Services\TenantResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para resolver la base de datos del tenant (PH) y configurar la conexión.
 * 
 * Este middleware es OBLIGATORIO para todas las rutas que requieren acceso a datos
 * específicos de un PH. Implementa el aislamiento multi-tenant por base de datos.
 * 
 * Flujo:
 * 1. Extraer NIT del header, payload o ruta
 * 2. Resolver PH usando TenantResolver
 * 3. Cambiar conexión DB dinámicamente
 * 4. Validar acceso del usuario al PH
 * 5. Si falla en cualquier paso → abortar con 403
 * 
 * IMPORTANTE: Nunca se acepta PH por body sin validar contra usuario.
 */
class ResolveTenantDatabase
{
    /**
     * Maneja una solicitud entrante.
     * 
     * Extrae el NIT del PH de las siguientes fuentes (en orden de prioridad):
     * - Header 'X-PH-NIT'
     * - Parámetro 'nit' en el body del request
     * - Parámetro 'nit' en la ruta
     * 
     * Una vez obtenido el NIT, resuelve el PH y configura la conexión de base de datos.
     * Valida que el usuario autenticado tenga acceso al PH solicitado.
     * 
     * @param Request $request Solicitud HTTP entrante
     * @param Closure $next Siguiente middleware en la cadena
     * @return Response Respuesta HTTP
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Paso 1: Extraer NIT del header, payload o ruta
        $nit = $this->extractNit($request);

        if (!$nit) {
            return response()->json([
                'error' => 'NIT de PH no proporcionado',
                'message' => 'Debe proporcionar el NIT del PH en el header X-PH-NIT, en el body como "nit" o en la ruta'
            ], 400);
        }

        // Paso 2: Resolver PH usando TenantResolver
        $tenantResolver = app(TenantResolver::class);
        $ph = $tenantResolver->resolve($nit);

        if (!$ph) {
            return response()->json([
                'error' => 'PH no encontrado o inactivo',
                'message' => "No se encontró un PH activo con el NIT: {$nit}"
            ], 403);
        }

        // Paso 3: Validar que el usuario autenticado tiene acceso a este PH
        // IMPORTANTE: Nunca se acepta PH por body sin validar contra usuario
        if ($request->user()) {
            $user = $request->user();
            if (!$user->tieneAccesoPh($ph->id)) {
                return response()->json([
                    'error' => 'No autorizado para acceder a este PH',
                    'message' => "El usuario no tiene permisos para acceder al PH con NIT: {$nit}"
                ], 403);
            }
        }

        // Paso 4: Agregar PH al request para uso posterior en controladores
        $request->merge(['ph' => $ph]);
        $request->attributes->set('ph', $ph);

        // La conexión de base de datos ya fue configurada por TenantResolver
        // Continuar con el siguiente middleware/controlador
        return $next($request);
    }

    /**
     * Extrae el NIT del PH de la solicitud.
     * 
     * Busca el NIT en el siguiente orden de prioridad:
     * 1. Header 'X-PH-NIT'
     * 2. Parámetro 'nit' en el body del request
     * 3. Parámetro 'nit' en la ruta
     * 
     * @param Request $request Solicitud HTTP
     * @return string|null NIT encontrado o null si no se encuentra
     */
    protected function extractNit(Request $request): ?string
    {
        // Prioridad 1: Header X-PH-NIT
        if ($request->hasHeader('X-PH-NIT')) {
            return $request->header('X-PH-NIT');
        }

        // Prioridad 2: Parámetro 'nit' en el body
        if ($request->has('nit')) {
            return $request->input('nit');
        }

        // Prioridad 3: Parámetro 'nit' en la ruta
        if ($request->route('nit')) {
            return $request->route('nit');
        }

        return null;
    }
}

