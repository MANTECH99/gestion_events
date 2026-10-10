<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Ticket;
use App\Models\Validation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Agrégats des tableaux de bord organisateur / admin.
 *
 * Toutes les méthodes acceptent une période (from / to) et une liste d'IDs
 * d'événements pour le cloisonnement multi-tenant.
 */
class StatsService
{
    /**
     * @param  array<int, int>|null  $eventIds
     * @return array{total_tickets:int, total_revenue:int, reservations:int, paid_reservations:int, validations:int, validations_valid:int, validations_invalid:int, tickets_valid:int, tickets_used:int, tickets_online:int, tickets_physical:int, reservations_online:int, reservations_physical:int, revenue_online:int, revenue_physical:int}
     */
    public function summary(?Carbon $from = null, ?Carbon $to = null, ?array $eventIds = null): array
    {
        $paid = $this->paidQuery($from, $to, $eventIds)
            ->selectRaw("
                COUNT(*) as reservations_count,
                COALESCE(SUM(reservations.quantity), 0) as tickets_total,
                COALESCE(SUM(reservations.total_amount), 0) as revenue_total,
                COALESCE(SUM(CASE WHEN reservations.channel = 'physical' THEN reservations.quantity ELSE 0 END), 0) as tickets_physical,
                COALESCE(SUM(CASE WHEN reservations.channel = 'physical' THEN reservations.total_amount ELSE 0 END), 0) as revenue_physical,
                COALESCE(SUM(CASE WHEN reservations.channel = 'physical' THEN 1 ELSE 0 END), 0) as reservations_physical
            ")
            ->first();

        $ticketsTotal = (int) ($paid->tickets_total ?? 0);
        $ticketsPhysical = (int) ($paid->tickets_physical ?? 0);
        $revenueTotal = (int) ($paid->revenue_total ?? 0);
        $revenuePhysical = (int) ($paid->revenue_physical ?? 0);
        $reservationsTotal = (int) ($paid->reservations_count ?? 0);
        $reservationsPhysical = (int) ($paid->reservations_physical ?? 0);

        return [
            'total_tickets' => $ticketsTotal,
            'total_revenue' => $revenueTotal,
            'reservations' => $this->allQuery($from, $to, $eventIds)->count(),
            'paid_reservations' => $reservationsTotal,
            'tickets_online' => $ticketsTotal - $ticketsPhysical,
            'tickets_physical' => $ticketsPhysical,
            'reservations_online' => $reservationsTotal - $reservationsPhysical,
            'reservations_physical' => $reservationsPhysical,
            'revenue_online' => $revenueTotal - $revenuePhysical,
            'revenue_physical' => $revenuePhysical,
            'validations' => $this->validationsQuery($from, $to, $eventIds)->count(),
            'validations_valid' => $this->validationsQuery($from, $to, $eventIds)->where('status', Validation::STATUS_VALID)->count(),
            'validations_invalid' => $this->validationsQuery($from, $to, $eventIds)->where('status', Validation::STATUS_INVALID)->count(),
            'tickets_valid' => $this->ticketsQuery($from, $to, $eventIds)->where('status', Ticket::STATUS_VALID)->count(),
            'tickets_used' => $this->ticketsQuery($from, $to, $eventIds)->where('status', Ticket::STATUS_USED)->count(),
        ];
    }

    /**
     * Évolution des ventes (jour par jour) sur la période, 7 jours par défaut.
     *
     * @param  array<int, int>|null  $eventIds
     * @return array<int, array{date:string, label:string, tickets:int, revenue:int, reservations:int}>
     */
    public function salesEvolution(?Carbon $from = null, ?Carbon $to = null, ?array $eventIds = null): array
    {
        $start = ($from ? $from->copy() : now()->subDays(6))->startOfDay();
        $end = ($to ? $to->copy() : now())->endOfDay();

        if ($start->diffInDays($end) > 92) {
            $start = $end->copy()->subDays(92);
        }

        $rows = $this->paidQuery($from, $to, $eventIds)
            ->whereBetween('reservations.created_at', [$start, $end])
            ->selectRaw('DATE(reservations.created_at) as day, COUNT(*) as reservations_count, SUM(reservations.total_amount) as revenue_total, SUM(reservations.quantity) as tickets_total')
            ->groupBy('day')
            ->get();

        $indexed = $rows->keyBy('day');

        $series = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $row = $indexed->get($key);

            $series[] = [
                'date' => $key,
                'label' => $cursor->translatedFormat('d M'),
                'tickets' => (int) ($row->tickets_total ?? 0),
                'revenue' => (int) ($row->revenue_total ?? 0),
                'reservations' => (int) ($row->reservations_count ?? 0),
            ];

            $cursor->addDay();
        }

        return $series;
    }

    /**
     * Top 5 événements les plus vendus.
     *
     * @param  array<int, int>|null  $eventIds
     * @return array<int, array{id:int, title:string, revenue:int, tickets:int, reservations:int}>
     */
    public function topEvents(?Carbon $from = null, ?Carbon $to = null, ?array $eventIds = null, int $limit = 5): array
    {
        return $this->groupByEvent($from, $to, $eventIds)
            ->sortByDesc('tickets')
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * Revenu par événement (décroissant).
     *
     * @param  array<int, int>|null  $eventIds
     * @return array<int, array{id:int, title:string, revenue:int, tickets:int, reservations:int}>
     */
    public function revenueByEvent(?Carbon $from = null, ?Carbon $to = null, ?array $eventIds = null): array
    {
        return $this->groupByEvent($from, $to, $eventIds)
            ->sortByDesc('revenue')
            ->values()
            ->all();
    }

    /**
     * Tickets par événement, ventilés en ligne / physiques.
     *
     * @param  array<int, int>|null  $eventIds
     * @return array<int, array{id:int, title:string, online:int, physical:int, total:int, revenue:int, reservations:int}>
     */
    public function ticketsByEvent(?Carbon $from = null, ?Carbon $to = null, ?array $eventIds = null): array
    {
        return $this->groupByEvent($from, $to, $eventIds)
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int>|null  $eventIds
     * @return Collection<int, array{id:int, title:string, revenue:int, tickets:int, reservations:int, online:int, physical:int, total:int}>
     */
    protected function groupByEvent(?Carbon $from, ?Carbon $to, ?array $eventIds)
    {
        return $this->paidQuery($from, $to, $eventIds)
            ->join('events', 'events.id', '=', 'reservations.event_id')
            ->groupBy('reservations.event_id', 'events.title')
            ->select([
                'reservations.event_id as id',
                'events.title as title',
            ])
            ->selectRaw('
                COUNT(*) as reservations_total,
                SUM(reservations.total_amount) as revenue_total,
                SUM(reservations.quantity) as tickets_total,
                SUM(CASE WHEN reservations.channel = \'physical\' THEN reservations.quantity ELSE 0 END) as physical_total,
                SUM(CASE WHEN reservations.channel <> \'physical\' OR reservations.channel IS NULL THEN reservations.quantity ELSE 0 END) as online_total
            ')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'title' => (string) $row->title,
                'revenue' => (int) $row->revenue_total,
                'tickets' => (int) $row->tickets_total,
                'reservations' => (int) $row->reservations_total,
                'online' => (int) $row->online_total,
                'physical' => (int) $row->physical_total,
                'total' => (int) $row->tickets_total,
            ])
            ->values();
    }

    /* ------------------------------------------------------------------ */
    /* Scopes internes */
    /* ------------------------------------------------------------------ */

    /** @param  array<int, int>|null  $eventIds */
    protected function allQuery(?Carbon $from, ?Carbon $to, ?array $eventIds): Builder
    {
        return Reservation::query()
            ->when($from, fn (Builder $q) => $q->whereDate('reservations.created_at', '>=', $from->toDateString()))
            ->when($to, fn (Builder $q) => $q->whereDate('reservations.created_at', '<=', $to->toDateString()))
            ->when($eventIds !== null, fn (Builder $q) => $q->whereIn('reservations.event_id', $eventIds));
    }

    /** @param  array<int, int>|null  $eventIds */
    protected function paidQuery(?Carbon $from, ?Carbon $to, ?array $eventIds): Builder
    {
        return $this->allQuery($from, $to, $eventIds)->where('reservations.payment_status', Reservation::STATUS_PAID);
    }

    /** @param  array<int, int>|null  $eventIds */
    protected function ticketsQuery(?Carbon $from, ?Carbon $to, ?array $eventIds): Builder
    {
        return Ticket::query()
            ->when($from, fn (Builder $q) => $q->whereDate('tickets.created_at', '>=', $from->toDateString()))
            ->when($to, fn (Builder $q) => $q->whereDate('tickets.created_at', '<=', $to->toDateString()))
            ->when($eventIds !== null, fn (Builder $q) => $q->whereIn('tickets.event_id', $eventIds));
    }

    /** @param  array<int, int>|null  $eventIds */
    protected function validationsQuery(?Carbon $from, ?Carbon $to, ?array $eventIds): Builder
    {
        return Validation::query()
            ->when($from, fn (Builder $q) => $q->whereDate('validations.created_at', '>=', $from->toDateString()))
            ->when($to, fn (Builder $q) => $q->whereDate('validations.created_at', '<=', $to->toDateString()))
            ->when($eventIds !== null, fn (Builder $q) => $q->whereIn('validations.event_id', $eventIds));
    }
}
