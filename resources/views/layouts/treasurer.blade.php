@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar', [
        'title' => 'Espace trésorière',
        'subtitle' => 'Retraits, sessions et réservations',
        'icon' => 'wallet',
        'sections' => [
            [
                'title' => 'Finances',
                'items' => [
                    ['label' => 'Retraits', 'route' => 'treasurer.payouts.index', 'icon' => 'wallet', 'pattern' => 'treasurer.payouts.*'],
                    ['label' => 'Sessions de paiement', 'route' => 'treasurer.payment-sessions.index', 'icon' => 'credit-card', 'pattern' => 'treasurer.payment-sessions.*'],
                ],
            ],
            [
                'title' => 'Suivi',
                'items' => [
                    ['label' => 'Réservations', 'route' => 'treasurer.reservations.index', 'icon' => 'list'],
                    ['label' => 'Point de vente artiste', 'route' => 'treasurer.artist-pos.index', 'icon' => 'share', 'pattern' => 'treasurer.artist-pos.*'],
                ],
            ],
        ],
    ])
@endsection
