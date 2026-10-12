<?php

use App\Models\Event;
use Illuminate\Support\Carbon;

if (! function_exists('money')) {
    /** 12500 → « 12 500 FCFA » (XOF sans décimales). */
    function money(int|float|null $amount, bool $withCurrency = true): string
    {
        $formatted = number_format((float) ($amount ?? 0), 0, ',', ' ');

        return $withCurrency ? $formatted.' FCFA' : $formatted;
    }
}

if (! function_exists('dexpay_service_fee')) {
    /**
     * Surcharge de service répercutée au client (couverture du frais de retrait
     * du marchand). Basée sur les frais réels DexPay de l'opérateur choisi
     * (`provider_fee` / `provider_fee_type`), avec repli sur le taux configuré
     * (`dexpay.service_fee_rate`) quand l'opérateur n'est pas connu.
     *
     * @param  array<string, mixed>|null  $operator
     */
    function dexpay_service_fee(int $amount, ?array $operator = null): int
    {
        if ($amount <= 0) {
            return 0;
        }

        $hasOperatorFee = is_array($operator)
            && array_key_exists('fee', $operator)
            && $operator['fee'] !== null
            && $operator['fee'] !== '';

        if ($hasOperatorFee) {
            $fee = (float) $operator['fee'];
            $type = $operator['fee_type'] ?? 'percentage';
        } else {
            $fee = (float) config('dexpay.service_fee_rate', 0);
            $type = 'percentage';
        }

        if ($fee <= 0) {
            return 0;
        }

        return $type === 'fixed'
            ? (int) round($fee)
            : (int) round($amount * $fee / 100);
    }
}

if (! function_exists('money_short')) {
    /** Version compacte pour les graphiques : 1 250 000 → « 1,25 M ». */
    function money_short(int|float|null $amount): string
    {
        $amount = (float) ($amount ?? 0);

        if ($amount >= 1_000_000) {
            return rtrim(rtrim(number_format($amount / 1_000_000, 2, ',', ''), '0'), ',').' M';
        }

        if ($amount >= 1_000) {
            return rtrim(rtrim(number_format($amount / 1_000, 1, ',', ''), '0'), ',').' k';
        }

        return number_format($amount, 0, ',', ' ');
    }
}

if (! function_exists('event_date_fr')) {
    function event_date_fr(?Carbon $date, string $format = 'd/m/Y à H:i'): string
    {
        return $date?->translatedFormat($format) ?? '—';
    }
}

if (! function_exists('event_type_label')) {
    function event_type_label(?string $type): string
    {
        return Event::TYPES[$type ?? ''] ?? ucfirst((string) $type);
    }
}

if (! function_exists('whatsapp_phone')) {
    /**
     * Normalise un numéro de téléphone pour l'API WhatsApp (chiffres seuls,
     * sans « + »). Les formats internationaux (+221…, 00221…) sont conservés ;
     * un zéro initial ou un numéro local court reçoit l'indicatif par défaut
     * (`whatsapp.default_country_code`, ex. 221). Renvoie null si inexploitable.
     */
    function whatsapp_phone(?string $number): ?string
    {
        $raw = trim((string) $number);

        if ($raw === '') {
            return null;
        }

        $hasPlus = str_starts_with($raw, '+');
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if ($digits === '') {
            return null;
        }

        $code = preg_replace('/\D+/', '', (string) config('whatsapp.default_country_code', '221')) ?: '221';

        if ($hasPlus || str_starts_with($digits, $code)) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            $digits = ltrim($digits, '0');
        } elseif (strlen($digits) > 9) {
            return $digits;
        }

        return $digits === '' ? null : $code.$digits;
    }
}

if (! function_exists('ticket_type_icon')) {
    function ticket_type_icon(?string $type): string
    {
        return match ($type) {
            'concert' => '🎤',
            'festival' => '🎪',
            'sport' => '⚽',
            'theatre' => '🎭',
            'conference' => '🎓',
            'evening' => '🍸',
            default => '🎟️',
        };
    }
}
