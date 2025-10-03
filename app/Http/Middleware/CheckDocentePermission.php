<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Docente;
use Symfony\Component\HttpFoundation\Response;

class CheckDocentePermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return redirect()->route('login');
            }

            // Verificar si el usuario tiene el permiso requerido
            if (!$user->can($permission)) {
                abort(403, 'No tienes permisos para acceder a esta sección.');
            }

            // Si es un docente, verificar que existe el registro de docente
            if ($user->hasRole('DOCENTE')) {
                // Usar una consulta más específica para evitar problemas de binding
                $docente = Docente::where('user_id', '=', $user->id)->first();
                
                if (!$docente) {
                    return redirect()->route('dashboard')->with('error', 'No se encontró información de docente');
                }
                
                // Compartir el docente con la vista
                $request->merge(['docente' => $docente]);
            }

            return $next($request);
        } catch (\Exception $e) {
            // Log del error para debugging
            \Log::error('Error en CheckDocentePermission: ' . $e->getMessage());
            return redirect()->route('dashboard')->with('error', 'Error al verificar permisos');
        }
    }
}