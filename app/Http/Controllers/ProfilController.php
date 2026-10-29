<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class ProfilController extends Controller
{
    public function show(): View
    {
        return view('profil.show', ['user' => auth()->user()]);
    }

    /**
     * Définit ou change le code PIN de retrait (2FA sur les payouts).
     * Réservé aux organisateurs et administrateurs.
     */
    public function updatePayoutPin(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->isOrganizer() || $user->isAdmin(), 403);

        // Limitation : 5 tentatives de changement par minute (anti-force brute).
        $throttleKey = 'payout-pin-update:'.$user->id;

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return back()->with('error', 'Trop de tentatives. Réessayez dans '.RateLimiter::availableIn($throttleKey).' secondes.');
        }

        $data = $request->validate([
            'current_pin' => ['nullable', 'string', 'digits_between:4,6'],
            'pin' => ['required', 'string', 'digits_between:4,6', 'confirmed'],
        ], [
            'pin.digits_between' => 'Le code doit contenir entre 4 et 6 chiffres.',
            'pin.confirmed' => 'La confirmation ne correspond pas au code.',
            'current_pin.digits_between' => 'Le code actuel doit contenir entre 4 et 6 chiffres.',
        ], [
            'current_pin' => 'code actuel',
            'pin' => 'nouveau code',
        ]);

        if (! $user->setPayoutPin($data['pin'], $data['current_pin'] ?? null)) {
            RateLimiter::hit($throttleKey, 60);

            return back()->withErrors(['current_pin' => 'Le code actuel est incorrect.']);
        }

        RateLimiter::clear($throttleKey);

        return back()->with('success', 'Code de retrait enregistré. Il sera demandé à chaque demande de retrait.');
    }
}
