@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar', [
        'title' => 'Administration',
        'subtitle' => 'Supervision de la plateforme',
        'icon' => 'shield',
        'sections' => [
            [
                'title' => 'Pilotage',
                'items' => [
                    ['label' => 'Tableau de bord', 'route' => 'admin.dashboard', 'icon' => 'chart-bar'],
                ],
            ],
            [
                'title' => 'Gestion',
                'items' => [
                    ['label' => 'Organisateurs', 'route' => 'admin.organizers.index', 'icon' => 'building'],
                    ['label' => 'Utilisateurs', 'route' => 'admin.users.index', 'icon' => 'users'],
                    ['label' => 'Demandes partenaires', 'route' => 'admin.partner-requests.index', 'icon' => 'handshake'],
                    ['label' => 'Réservations', 'route' => 'organizer.reservations.index', 'icon' => 'list'],
                ],
            ],
        ],
    ])
@endsection