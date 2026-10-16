@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar', [
        'title' => 'Espace organisateur',
        'subtitle' => 'Événements, ventes et agents',
        'icon' => 'chart-bar',
        'sections' => [
            [
                'title' => 'Pilotage',
                'items' => [
                    ['label' => 'Tableau de bord', 'route' => 'organizer.dashboard', 'icon' => 'chart-bar'],
                ],
            ],
            [
                'title' => 'Ventes',
                'items' => [
                    ['label' => 'Événements', 'route' => 'organizer.events.index', 'icon' => 'calendar', 'pattern' => 'organizer.events.*'],
                    ['label' => 'Artistes', 'route' => 'organizer.artists.index', 'icon' => 'star', 'pattern' => 'organizer.artists.*'],
                    ['label' => 'Réservations', 'route' => 'organizer.reservations.index', 'icon' => 'list'],
                    ['label' => 'Stock physique', 'route' => 'organizer.stocks.index', 'icon' => 'coins'],
                    ['label' => 'Point de vente', 'route' => 'organizer.pos.index', 'icon' => 'store', 'pattern' => 'organizer.pos.*'],
                ],
            ],
            [
                'title' => 'Contrôle',
                'items' => [
                    ['label' => 'Utilisateurs', 'route' => 'organizer.users.index', 'icon' => 'users'],
                    ['label' => 'Validations', 'route' => 'organizer.validations.index', 'icon' => 'scan', 'pattern' => 'organizer.validations.*'],
                    ['label' => 'Avis', 'route' => 'organizer.reviews.index', 'icon' => 'inbox', 'pattern' => 'organizer.reviews.*'],
                ],
            ],
            [
                'title' => 'Finances',
                'items' => [
                    ['label' => 'Rapports', 'route' => 'organizer.reports.index', 'icon' => 'document', 'pattern' => 'organizer.reports.*'],
                    ['label' => 'Retraits', 'route' => 'organizer.payouts.index', 'icon' => 'wallet'],
                    ['label' => 'Sessions de paiement', 'route' => 'organizer.payment-sessions.index', 'icon' => 'credit-card', 'pattern' => 'organizer.payment-sessions.*'],
                ],
            ],
        ],
        'cta' => ['label' => 'Nouvel événement', 'route' => 'organizer.events.create'],
    ])
@endsection