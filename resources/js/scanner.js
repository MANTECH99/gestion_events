import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode';
import { toast } from './utils';

/**
 * Scanner de QR code pour les agents de contrôle.
 *
 * Utilise html5-qrcode (caméra arrière si disponible). Une saisie manuelle
 * du code reste possible en cas de caméra indisponible (permissions, HTTPS
 * manquant sur mobile, etc.).
 */
function ticketScanner(config) {
    return {
        config,
        scanner: null,
        running: false,
        busy: false,
        error: '',
        manual: '',
        lastResult: null,
        cameraSupported: false,

        async init() {
            await this.start();
        },

        async start() {
            if (this.running) return;

            this.error = '';

            // L'API BarcodeDetector native est plus rapide quand elle existe.
            this.cameraSupported = 'BarcodeDetector' in window || Html5Qrcode.getCameras !== undefined;

            try {
                this.scanner = new Html5Qrcode('qr-reader', {
                    formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
                    verbose: false,
                });

                const cameras = await Html5Qrcode.getCameras();

                if (!cameras || cameras.length === 0) {
                    throw new Error('Aucune caméra détectée.');
                }

                const back = cameras.find((c) => /back|rear|arrière|environment/i.test(c.label));
                const cameraId = back ? back.id : cameras[0].id;

                await this.scanner.start(
                    cameraId,
                    { fps: 12, qrbox: { width: 260, height: 260 }, aspectRatio: 1 },
                    (decoded) => this.handle(decoded),
                    () => {
                        /* trames non reconnues : ignorées */
                    }
                );

                this.running = true;
            } catch (e) {
                this.running = false;
                this.error =
                    e?.name === 'NotAllowedError'
                        ? "Accès à la caméra refusé. Autorisez-le dans votre navigateur ou saisissez le code manuellement."
                        : `Caméra indisponible : ${e?.message ?? e}. Saisissez le code manuellement ci-dessous.`;
            }
        },

        async stop() {
            if (this.scanner && this.running) {
                try {
                    await this.scanner.stop();
                    this.scanner.clear();
                } catch (e) {
                    /* ignore */
                }
            }

            this.running = false;
        },

        async handle(code) {
            if (this.busy || !code) return;

            this.busy = true;
            this.lastResult = { code, ok: null, message: 'Vérification…' };

            // Pause la caméra le temps de la requête pour éviter les doubles scans.
            if (this.running) {
                try {
                    await this.scanner.pause(true);
                } catch (e) {
                    /* ignore */
                }
            }

            try {
                const data = await this.validate(code);

                this.lastResult = {
                    code,
                    ok: data.outcome === 'valid',
                    outcome: data.outcome,
                    message: data.message,
                    title: data.title,
                    ticket: data.ticket,
                };

                this.playSound(data.outcome === 'valid');

                if (data.outcome === 'invalid') {
                    toast('Ticket invalide', 'error');
                }
            } catch (e) {
                this.lastResult = { code, ok: false, message: 'Erreur de communication.', title: 'Erreur' };
            } finally {
                this.busy = false;

                if (this.running) {
                    try {
                        await this.scanner.pause(false);
                    } catch (e) {
                        /* ignore */
                    }
                }
            }
        },

        async submitManual() {
            const code = this.manual.trim();

            if (!code) return;

            this.manual = '';
            await this.handle(code);
        },

        async validate(code) {
            const response = await fetch(this.config.validateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify({ code, event_id: this.config.eventId ?? null }),
            });

            if (response.status === 401 || response.status === 403) {
                window.location.href = '/connexion';
                return { outcome: 'invalid', message: 'Session expirée.', title: 'Erreur' };
            }

            if (!response.ok) {
                const data = await response.json().catch(() => ({}));

                throw new Error(data.message || 'Vérification impossible.');
            }

            return response.json();
        },

        playSound(success) {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.type = success ? 'sine' : 'sawtooth';
                osc.frequency.value = success ? 880 : 220;
                gain.gain.setValueAtTime(0.08, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + (success ? 0.35 : 0.5));
                osc.start();
                osc.stop(ctx.currentTime + (success ? 0.35 : 0.5));
            } catch (e) {
                /* le son est un bonus, pas une fonctionnalité critique */
            }
        },

        destroy() {
            this.stop();
        },
    };
}

window.ticketScanner = ticketScanner;