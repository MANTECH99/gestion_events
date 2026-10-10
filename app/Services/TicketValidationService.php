<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Validation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Contrôle d'entrée : scan d'un QR code par un agent.
 *
 * Un ticket déjà scanné est marqué « utilisé » et toute tentative
 * ultérieure est refusée.
 */
class TicketValidationService
{
    /**
     * @return array{
     *   outcome: string, ticket: ?Ticket, validation: Validation,
     *   title: string, message: string, tone: string
     * }
     */
    public function scan(User $agent, string $rawCode, ?Event $event = null): array
    {
        $code = $this->normalize($rawCode);

        $ticket = Ticket::query()->with(['event', 'ticketType'])->where('code', $code)->first();

        if (! $ticket) {
            return $this->reject($agent, null, $event, $rawCode, Validation::MSG_UNKNOWN);
        }

        // Cloisonnement : un agent ne peut valider que les billets des événements
        // de son organisateur (les admins contrôlent tout).
        if (! $agent->isAdmin() && ! in_array((int) $ticket->event_id, $this->allowedEventIds($agent), true)) {
            return $this->reject($agent, $ticket, $event, $rawCode, Validation::MSG_EVENT_MISMATCH);
        }

        // Contrôle de l'événement contrôlé (agents d'un organisateur multi-événements).
        if ($event && $ticket->event_id !== $event->id) {
            return $this->reject($agent, $ticket, $event, $rawCode, Validation::MSG_EVENT_MISMATCH);
        }

        return DB::transaction(function () use ($agent, $ticket, $event, $rawCode) {
            $locked = Ticket::query()->lockForUpdate()->find($ticket->id);

            if ($locked->status === Ticket::STATUS_USED) {
                return $this->reject($agent, $locked, $event, $rawCode, Validation::MSG_ALREADY_USED);
            }

            if (in_array($locked->status, [Ticket::STATUS_REFUNDED, Ticket::STATUS_CANCELLED], true)) {
                return $this->reject($agent, $locked, $event, $rawCode, Validation::MSG_EXPIRED);
            }

            $locked->update([
                'status' => Ticket::STATUS_USED,
                'used_at' => now(),
                'validated_by' => $agent->id,
            ]);

            $validation = Validation::create([
                'ticket_id' => $locked->id,
                'event_id' => $locked->event_id,
                'agent_id' => $agent->id,
                'ticket_code' => $locked->code,
                'status' => Validation::STATUS_VALID,
                'message' => Validation::MSG_WELCOME,
                'scanned_code' => $rawCode,
            ]);

            return [
                'outcome' => 'valid',
                'ticket' => $locked->fresh(['event', 'ticketType']),
                'validation' => $validation,
                'title' => 'Entrée autorisée',
                'message' => Validation::MSG_WELCOME,
                'tone' => 'success',
            ];
        });
    }

    /** @return array<int, int> IDs des événements contrôlés par l'agent. */
    private function allowedEventIds(User $agent): array
    {
        return Event::query()
            ->where('organizer_id', $agent->organizer_id ?? $agent->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return array{outcome: string, ticket: ?Ticket, validation: Validation, title: string, message: string, tone: string}
     */
    private function reject(User $agent, ?Ticket $ticket, ?Event $event, string $rawCode, string $message): array
    {
        $validation = Validation::create([
            'ticket_id' => $ticket?->id,
            'event_id' => $ticket?->event_id ?? $event?->id,
            'agent_id' => $agent->id,
            'ticket_code' => $ticket?->code,
            'status' => Validation::STATUS_INVALID,
            'message' => $message,
            'scanned_code' => $rawCode,
        ]);

        return [
            'outcome' => 'invalid',
            'ticket' => $ticket,
            'validation' => $validation,
            'title' => 'Ticket invalide',
            'message' => $message,
            'tone' => 'danger',
        ];
    }

    /** Extrait un code de ticket depuis une URL de billet, un JSON de QR ou le code brut. */
    public function normalize(string $raw): string
    {
        $raw = trim($raw);

        if (str_starts_with($raw, '{')) {
            $decoded = json_decode($raw, true);

            if (is_array($decoded)) {
                $raw = (string) ($decoded['code'] ?? $decoded['ticket'] ?? $raw);
            }
        }

        if (preg_match('#/tickets/([A-Za-z0-9\-]+)#', $raw, $m)) {
            $raw = $m[1];
        }

        return strtoupper(trim($raw));
    }

    /** Normalise un code saisi manuellement (tolère la casse et les espaces). */
    public static function cleanInput(string $value): string
    {
        return Str::upper(preg_replace('/\s+/', '', trim($value)) ?? '');
    }
}
