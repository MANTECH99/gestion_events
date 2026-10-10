<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Génération des QR codes (SVG — net à l'impression, aucun GD requis).
 */
class QrCodeService
{
    public const DARK = '#0f172a';

    public const LIGHT = '#ffffff';

    public function svg(string $payload, int $size = 320, int $margin = 2): string
    {
        $writer = new Writer(
            new ImageRenderer(
                new RendererStyle($size, $margin),
                new SvgImageBackEnd,
                self::DARK,
                self::LIGHT
            )
        );

        return $writer->writeString($payload);
    }

    /** SVG encodé en data-URI, utilisable directement dans un <img src>. */
    public function dataUri(string $payload, int $size = 320): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($this->svg($payload, $size));
    }
}
