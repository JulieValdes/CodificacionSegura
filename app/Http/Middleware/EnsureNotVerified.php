<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureNotVerified
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // Si el usuario ya ha completado la verificación, redirigir a home
        if (!$user->verification_code) {
            return redirect()->route('home')->with('error', 'Ya has completado la autentificacion.');
        }

        return $next($request);
    }
}