<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EsAdmin
{
    /**
     * Deja pasar solo a usuarios administradores.
     * Se usa junto con "auth": ['auth', 'admin'].
     */
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario === null) {
            return redirect()->route('login');
        }

        //  ¿Es administrador?
        if ($usuario->is_admin !== true) {
            // 403 = "Prohibido": sabemos quién eres, pero no tienes permiso
            abort(403, 'No tienes acceso a la administración.');
        }

        // Todo bien: continúa hacia la ruta o el componente
        return $next($request);
    }
}
