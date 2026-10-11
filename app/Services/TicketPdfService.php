<?php

namespace App\Services;

use App\Models\Ticket;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Génère le PDF des billets.
 *
 * En priorité, la carte HTML réelle (celle montrée dans l'application) est
 * imprimée via un navigateur headless (Chrome/Edge ou wkhtmltopdf) car un
 * moteur de navigateur rend fidèlement la carte Tailwind (dégradé, icônes,
 * portrait). Si aucun navigateur n'est disponible (serveur mutualisé), on
 * bascule sur dompdf (pur PHP, aucune dépendance système) via le template
 * `tickets.print-pdf`.
 */
class TicketPdfService
{
    public function __construct(private readonly QrCodeService $qr) {}

    /**
     * @param  Collection<int, Ticket>  $tickets
     * @return string Contenu binaire du PDF.
     */
    public function render(Collection $tickets): string
    {
        $tickets->each(fn (Ticket $ticket) => $ticket->loadMissing([
            'event.organizer', 'ticketType', 'reservation', 'validator',
        ]));

        $engine = config('services.ticket_pdf.engine');

        if ($engine === 'dompdf') {
            return $this->renderWithDompdf($tickets);
        }

        if ($engine === 'browser' || ($engine === null && $this->binary() !== null)) {
            try {
                return $this->renderWithBrowser($tickets);
            } catch (RuntimeException $e) {
                // Navigateur indisponible/échoué : on retombe sur dompdf, sauf si
                // l'opérateur a explicitement exigé le moteur navigateur.
                if ($engine === 'browser') {
                    throw $e;
                }
            }
        }

        return $this->renderWithDompdf($tickets);
    }

    public static function isAvailable(): bool
    {
        return class_exists(Pdf::class)
            || (new self(app(QrCodeService::class)))->binary() !== null;
    }

    /** Impression fidèle de la carte Tailwind via un navigateur headless. */
    private function renderWithBrowser(Collection $tickets): string
    {
        $html = view('tickets.print', [
            'tickets' => $tickets,
            'qr' => fn (Ticket $ticket) => $this->qr->svg($ticket->qrPayload(), 512),
            'css' => $this->compiledCss(),
        ])->render();

        $workDir = storage_path('app/ticket-pdf/'.Str::uuid());
        File::ensureDirectoryExists($workDir);

        $htmlPath = $workDir.'/ticket.html';
        $pdfPath = $workDir.'/ticket.pdf';

        File::put($htmlPath, $html);

        try {
            $this->print($htmlPath, $pdfPath, $workDir.'/chrome-profile');

            if (! File::exists($pdfPath) || File::size($pdfPath) === 0) {
                throw new RuntimeException('La génération du PDF a échoué (fichier vide).');
            }

            $bytes = File::get($pdfPath);
        } finally {
            File::deleteDirectory($workDir);
        }

        return $bytes;
    }

    /** Repli 100 % PHP (dompdf) — aucune dépendance système requise. */
    private function renderWithDompdf(Collection $tickets): string
    {
        $html = view('tickets.print-pdf', [
            'tickets' => $tickets,
            'qr' => fn (Ticket $ticket) => $this->qr->dataUri($ticket->qrPayload(), 320),
        ])->render();

        // 105 mm x 200 mm en points (1 mm = 2.834645669 pt).
        return Pdf::loadHtml($html)
            ->setPaper([0, 0, 297.64, 566.93], 'portrait')
            ->output();
    }

    private function print(string $htmlPath, string $pdfPath, string $profileDir): void
    {
        $binary = $this->binary();

        if ($binary === null) {
            throw new RuntimeException(
                'Aucun moteur de PDF trouvé. Installez Google Chrome / Edge (ou wkhtmltopdf) '
                .'ou renseignez TICKET_PDF_CHROME / TICKET_PDF_WKHTMLTOPDF dans le fichier .env.'
            );
        }

        if (File::name($binary) === 'wkhtmltopdf' || str_contains(strtolower($binary), 'wkhtmltopdf')) {
            $command = [
                $binary,
                '--quiet', '--enable-local-file-access',
                '--page-width', '105mm', '--page-height', '200mm',
                '--margin-top', '0', '--margin-right', '0',
                '--margin-bottom', '0', '--margin-left', '0',
                $htmlPath, $pdfPath,
            ];
        } else {
            $command = [
                $binary,
                '--headless=new',
                '--disable-gpu',
                '--no-sandbox',
                '--no-pdf-header-footer',
                '--disable-extensions',
                '--disable-dev-shm-usage',
                '--allow-file-access-from-files',
                '--user-data-dir='.$profileDir,
                '--print-to-pdf='.$pdfPath,
                $this->fileUrl($htmlPath),
            ];
        }

        $process = new Process($command);
        $process->setTimeout(60);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Échec du moteur PDF : '.trim($process->getErrorOutput() ?: $process->getOutput()));
        }
    }

    private function binary(): ?string
    {
        $configured = [
            config('services.ticket_pdf.chrome'),
            config('services.ticket_pdf.wkhtmltopdf'),
        ];

        foreach ($configured as $path) {
            if ($path && File::exists($path)) {
                return $path;
            }
        }

        $candidates = match (PHP_OS_FAMILY) {
            'Windows' => [
                'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
                'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
                getenv('LOCALAPPDATA').'\\Google\\Chrome\\Application\\chrome.exe',
                'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
                'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
                'C:\\Program Files\\wkhtmltopdf\\bin\\wkhtmltopdf.exe',
            ],
            'Darwin' => [
                '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
                '/Applications/Microsoft Edge.app/Contents/MacOS/Microsoft Edge',
                '/usr/local/bin/wkhtmltopdf',
            ],
            default => [
                '/usr/bin/google-chrome', '/usr/bin/google-chrome-stable',
                '/usr/bin/chromium', '/usr/bin/chromium-browser',
                '/usr/bin/microsoft-edge', '/usr/bin/wkhtmltopdf',
            ],
        };

        foreach ($candidates as $path) {
            if ($path && File::exists($path)) {
                return $path;
            }
        }

        return (new ExecutableFinder)->find('google-chrome')
            ?? (new ExecutableFinder)->find('chromium')
            ?? (new ExecutableFinder)->find('chromium-browser')
            ?? (new ExecutableFinder)->find('google-chrome-stable')
            ?? (new ExecutableFinder)->find('microsoft-edge')
            ?? (new ExecutableFinder)->find('wkhtmltopdf');
    }

    /** CSS Tailwind compilé, avec les polices en URLs absolues utilisables en file://. */
    private function compiledCss(): string
    {
        $css = '';

        foreach (glob(public_path('build/assets/app-*.css')) ?: [] as $file) {
            $css .= File::get($file)."\n";
        }

        $assetBase = rtrim($this->fileUrl(public_path('build/assets')), '/').'/';

        foreach (glob(public_path('build/assets/fonts-*.css')) ?: [] as $file) {
            $css .= str_replace('/build/assets/', $assetBase, File::get($file))."\n";
        }

        return $css;
    }

    private function fileUrl(string $path): string
    {
        $path = str_replace('\\', '/', $path);

        return 'file:///'.str_replace(' ', '%20', ltrim($path, '/'));
    }
}
