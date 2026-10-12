<?php

namespace App\Services\WhatsApp;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Client minimal de l'API WhatsApp Cloud (Meta) : téléversement d'un média
 * puis envoi d'un modèle utilitaire dont l'en-tête affiche l'image du billet.
 */
class WhatsAppCloudClient
{
    public function enabled(): bool
    {
        return (bool) config('whatsapp.enabled')
            && filled(config('whatsapp.token'))
            && filled(config('whatsapp.phone_number_id'));
    }

    /**
     * Envoie le billet en pièce jointe (modèle image + corps à 2 variables).
     *
     * @param  array<int, string>  $bodyVariables  paramètres du corps ({{1}}, {{2}}…)
     */
    public function sendTicket(
        string $to,
        string $imageBinary,
        string $mime,
        array $bodyVariables,
        ?string $buttonUrl = null,
    ): array {
        $mediaId = $this->uploadImage($imageBinary, $mime);

        return $this->sendTemplate($to, $bodyVariables, $mediaId, $buttonUrl);
    }

    /** Téléverse l'image et renvoie son identifiant média Meta. */
    public function uploadImage(string $binary, string $mime): string
    {
        $response = $this->request()
            ->attach('file', $binary, 'billet.jpg', ['Content-Type' => $mime])
            ->post($this->url('/media'), [
                'messaging_product' => 'whatsapp',
                'type' => $mime,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Échec du téléversement WhatsApp : '.$response->body());
        }

        $id = $response->json('id');

        if (! is_string($id) || $id === '') {
            throw new RuntimeException('Réponse WhatsApp inattendue (média sans identifiant).');
        }

        return $id;
    }

    /**
     * Envoie le modèle configuré au destinataire.
     *
     * @param  array<int, string>  $bodyVariables
     */
    public function sendTemplate(
        string $to,
        array $bodyVariables,
        ?string $imageId = null,
        ?string $buttonUrl = null,
    ): array {
        $components = [];

        if (config('whatsapp.template.header') === 'image' && $imageId !== null) {
            $components[] = [
                'type' => 'header',
                'parameters' => [
                    ['type' => 'image', 'image' => ['id' => $imageId]],
                ],
            ];
        }

        if ($bodyVariables !== []) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(
                    fn (string $text) => ['type' => 'text', 'text' => $text],
                    array_values($bodyVariables),
                ),
            ];
        }

        if (config('whatsapp.template.has_button') && $buttonUrl !== null) {
            $components[] = [
                'type' => 'button',
                'sub_type' => 'url',
                'index' => '0',
                'parameters' => [
                    ['type' => 'text', 'text' => $buttonUrl],
                ],
            ];
        }

        $response = $this->request()->post($this->url('/messages'), [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => config('whatsapp.template.name'),
                'language' => ['code' => config('whatsapp.template.lang')],
                'components' => $components,
            ],
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Échec de l\'envoi WhatsApp : '.$response->body());
        }

        return $response->json() ?? [];
    }

    private function request(): PendingRequest
    {
        if (! $this->enabled()) {
            throw new RuntimeException('WhatsApp n\'est pas configuré (WHATSAPP_ENABLED / WHATSAPP_TOKEN / WHATSAPP_PHONE_NUMBER_ID).');
        }

        return Http::withToken((string) config('whatsapp.token'))
            ->acceptJson()
            ->timeout((int) config('whatsapp.timeout', 20));
    }

    private function url(string $path): string
    {
        return rtrim((string) config('whatsapp.base_url'), '/')
            .'/'.config('whatsapp.api_version')
            .'/'.config('whatsapp.phone_number_id')
            .$path;
    }
}
