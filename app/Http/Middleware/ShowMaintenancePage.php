<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Affiche la page de maintenance (HTTP 503) sur tout le site quand
 * MAINTENANCE_ENABLED=true et que l'on est dans la fenêtre
 * [MAINTENANCE_START ; MAINTENANCE_END].
 *
 * Exceptions (le site « continue » pour elles) :
 *  - les administrateurs connectés : ils gardent la main pendant les travaux ;
 *  - la page /connexion et la déconnexion : sinon un admin ne pourrait plus
 *    se connecter pour vérifier le site ;
 *  - les points d'entrée machines : /up (santé), webhooks DexPay (les paiements
 *    en cours doivent aboutir), sw.js et le manifest PWA.
 */
class ShowMaintenancePage
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->maintenanceActive() || $this->isExempt($request) || $this->isAdmin($request)) {
            return $next($request);
        }

        $endsAt = $this->parseDate(config('maintenance.end'));

        $response = response()->view('maintenance', [
            'startsAt' => $this->parseDate(config('maintenance.start')),
            'endsAt' => $endsAt,
        ], 503);

        $response->headers->set('Retry-After', $this->retryAfter($endsAt));
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }

    private function maintenanceActive(): bool
    {
        if (! config('maintenance.enabled')) {
            return false;
        }

        $now = now();
        $startsAt = $this->parseDate(config('maintenance.start'));
        $endsAt = $this->parseDate(config('maintenance.end'));

        // Pas encore l'heure de début : le site tourne normalement.
        if ($startsAt && $now->lt($startsAt)) {
            return false;
        }

        // Fin des travaux atteinte : retour automatique au site normal.
        if ($endsAt && $now->gte($endsAt)) {
            return false;
        }

        return true;
    }

    private function isExempt(Request $request): bool
    {
        return $request->is(
            'up',
            'connexion',
            'deconnexion',
            'webhooks/*',
            'sw.js',
            'manifest.webmanifest',
        );
    }

    private function isAdmin(Request $request): bool
    {
        return $request->user()?->role === 'admin';
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function retryAfter(?Carbon $endsAt): string
    {
        if (! $endsAt) {
            return '3600';
        }

        $seconds = (int) max(1, now()->diffInSeconds($endsAt, false));

        return (string) $seconds;
    }
}
