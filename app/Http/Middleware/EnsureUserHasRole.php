<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restreint une route à certains rôles. Sans argument, tout utilisateur
 * authentifié est accepté.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if (! $user->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Votre compte a été désactivé. Contactez un administrateur.',
            ]);
        }

        if ($roles !== [] && ! in_array($user->role, $roles, true)) {
            return redirect()->route('home')->with('error', "Vous n'avez pas accès à cette page.");
        }

        return $next($request);
    }
}
