<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureNotVerified
{
    public function handle(Request $request, Closure $next)
    {         
        // Verificar si el usuario ya ha verificado su correo electrónico
        if (Auth::user()->email_verified_at) {
            return redirect()->route('home')->with('error', 'Ya has verificado tu correo electrónico.');
        }

        return $next($request);
    }
}