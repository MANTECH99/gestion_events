<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class ThemeController extends Controller
{
    /**
     * Bascule le thème des tableaux de bord organisateur/administrateur.
     * Sombre par défaut (absence de cookie) ; le cookie « theme » ne sert
     * que ces deux pages, les autres restent thémisées par leur route.
     */
    public function update(Request $request): RedirectResponse
    {
        $next = $request->cookie('theme') === 'light' ? 'dark' : 'light';

        Cookie::queue(Cookie::make('theme', $next, 60 * 24 * 365));

        return back();
    }
}
