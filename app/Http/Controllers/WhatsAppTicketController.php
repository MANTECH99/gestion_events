<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Services\WhatsApp\WhatsAppCloudClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Throwable;

class WhatsAppTicketController extends Controller
{
    public function __construct(private readonly WhatsAppCloudClient $whatsapp) {}

    /**
     * Reçoit l'image du billet générée par le navigateur du client et l'envoie
     * sur son WhatsApp via l'API Cloud (modèle utilitaire). Un seul envoi par
     * réservation : les appels suivants sont ignorés.
     */
    public function send(Request $request, string $reference): JsonResponse
    {
        $reservation = Reservation::where('reference', $reference)->firstOrFail();

        if (! $reservation->isPaid()) {
            return response()->json(['sent' => false, 'reason' => 'unpaid'], 422);
        }

        if ($reservation->hasBeenSentToWhatsApp()) {
            return response()->json(['sent' => false, 'reason' => 'already_sent']);
        }

        if (! $this->whatsapp->enabled()) {
            return response()->json(['sent' => false, 'reason' => 'disabled'], 503);
        }

        $phone = whatsapp_phone($reservation->customer_phone);

        if ($phone === null) {
            return response()->json(['sent' => false, 'reason' => 'invalid_phone'], 422);
        }

        $request->validate(['image' => ['required', 'string']]);

        [$mime, $binary] = $this->decodeImage($request->string('image')->toString());

        if ($binary === null) {
            return response()->json(['sent' => false, 'reason' => 'invalid_image'], 422);
        }

        // Le bouton du modèle vaut « https://billeteriexpress.com/{{1}} » : on
        // ne transmet que le chemin, sans host ni « / » initial. Signature
        // relative pour rester valable même si le domaine diffère de l'app.
        $buttonUrl = ltrim(
            URL::signedRoute('tickets.reservation', ['reference' => $reservation->reference], absolute: false),
            '/'
        );

        try {
            $this->whatsapp->sendTicket(
                $phone,
                $binary,
                $mime,
                [(string) $reservation->customer_name, (string) $reservation->reference],
                $buttonUrl,
            );
        } catch (Throwable $e) {
            Log::warning('Échec de l\'envoi WhatsApp du billet', [
                'reservation' => $reservation->reference,
                'message' => $e->getMessage(),
            ]);

            return response()->json(['sent' => false, 'reason' => 'api_error'], 502);
        }

        $reservation->forceFill(['whatsapp_sent_at' => now()])->save();

        return response()->json(['sent' => true]);
    }

    /**
     * Décode une image en data-URL (PNG/JPEG/WebP) vers son mime et son binaire.
     *
     * @return array{0: string, 1: string|null}
     */
    private function decodeImage(string $data): array
    {
        if (preg_match('#^data:(image/(?:png|jpe?g|webp));base64,(.+)$#is', $data, $matches) !== 1) {
            return ['', null];
        }

        $binary = base64_decode(preg_replace('/\s+/', '', $matches[2]), true);

        $size = $binary === false ? 0 : strlen($binary);

        if ($binary === false || $size < 100 || $size > 8 * 1024 * 1024) {
            return [$matches[1], null];
        }

        return [$matches[1], $binary];
    }
}
