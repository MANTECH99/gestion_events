import Alpine from 'alpinejs';

import './booking';
import './charts';
import './event-detail';
import './payment';
import './scanner';
import './pwa';
import './ticket-image';
import { toast } from './utils';

window.Alpine = Alpine;

/** Jeton CSRF pour les requêtes fetch. */
const csrf = document.querySelector('meta[name="csrf-token"]');

if (csrf && !document.querySelector('meta[name="csrf-token"]')) {
    const meta = document.createElement('meta');
    meta.name = 'csrf-token';
    meta.content = csrf.content;
    document.head.appendChild(meta);
}

Alpine.store('toast', { show: toast });

Alpine.start();