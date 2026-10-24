<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DexPayWebhookController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventReviewController;
use App\Http\Controllers\FavorisController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MyReservationsController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\Organizer\ArtistController as OrganizerArtist;
use App\Http\Controllers\Organizer\DashboardController as OrganizerDashboard;
use App\Http\Controllers\Organizer\EventController as OrganizerEvent;
use App\Http\Controllers\Organizer\PaymentSessionController as OrganizerPaymentSession;
use App\Http\Controllers\Organizer\PayoutController as OrganizerPayout;
use App\Http\Controllers\Organizer\PointOfSaleController as OrganizerPointOfSale;
use App\Http\Controllers\Organizer\ReportController as OrganizerReport;
use App\Http\Controllers\Organizer\ReservationController as OrganizerReservation;
use App\Http\Controllers\Organizer\ReviewController as OrganizerReview;
use App\Http\Controllers\Organizer\StockController as OrganizerStock;
use App\Http\Controllers\Organizer\TicketTypeController as OrganizerTicketType;
use App\Http\Controllers\Organizer\UserController as OrganizerUser;
use App\Http\Controllers\Organizer\ValidationHistoryController as OrganizerValidations;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PointOfSaleController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\WhatsAppTicketController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

/* ------------------------------------------------------------------ */
/*  Pages publiques */
/* ------------------------------------------------------------------ */

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/evenements/{event}', [EventController::class, 'show'])->name('events.show');
Route::post('/evenements/{event}/avis', [EventReviewController::class, 'store'])
    ->name('events.reviews.store')
    ->middleware('throttle:5,1');
Route::get('/favoris', [FavorisController::class, 'index'])->name('favoris.index');

/* ------------------------------------------------------------------ */
/*  Réservation + paiement (avec ou sans compte) */
/* ------------------------------------------------------------------ */

Route::get('/evenements/{event}/reserver', [BookingController::class, 'create'])->name('booking.create');
Route::post('/evenements/{event}/reserver', [BookingController::class, 'store'])->name('booking.store');

Route::get('/paiement/{reference}', [PaymentController::class, 'show'])->name('payment.show');
Route::post('/paiement/{reference}/demarrer', [PaymentController::class, 'start'])->name('payment.start');
Route::get('/paiement/{reference}/succes', [PaymentController::class, 'success'])->name('payment.success');
Route::get('/paiement/{reference}/echec', [PaymentController::class, 'failed'])->name('payment.failed');
Route::get('/paiement/{reference}/statut', [PaymentController::class, 'status'])->name('payment.status');

Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
Route::get('/tickets/{ticket}/telecharger', [TicketController::class, 'download'])->name('tickets.download');
Route::get('/reservation/{reference}/tickets', [TicketController::class, 'reservationTickets'])->name('tickets.reservation');
Route::get('/reservation/{reference}/telecharger', [TicketController::class, 'downloadReservation'])->name('reservations.download');

Route::post('/reservation/{reference}/whatsapp', [WhatsAppTicketController::class, 'send'])
    ->middleware('signed')
    ->name('reservations.whatsapp');

/* ------------------------------------------------------------------ */
/*  Webhooks DEXCHANGE PAY */
/* ------------------------------------------------------------------ */

Route::post('/webhooks/dexpay', [DexPayWebhookController::class, 'handle'])
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->name('webhooks.dexpay');

/* ------------------------------------------------------------------ */
/*  Authentification */
/* ------------------------------------------------------------------ */

Route::middleware('guest')->group(function () {
    Route::get('/connexion', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/connexion', [AuthController::class, 'login'])->name('login.store');
    Route::get('/inscription', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/inscription', [AuthController::class, 'register'])->name('register.store');
});

Route::post('/deconnexion', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

/* Bascule thème clair/sombre des tableaux de bord (sombre par défaut). */
Route::post('/theme', [ThemeController::class, 'update'])->middleware('auth')->name('theme.update');

/* ------------------------------------------------------------------ */
/*  Espace client */
/* ------------------------------------------------------------------ */

/* ------------------------------------------------------------------ */
/*  Espace agent */
/*  Les pages sont accessibles sans compte : elles affichent alors la */
/*  page « Veuillez vous connecter avec un compte agent pour scanner ». */
/* ------------------------------------------------------------------ */

Route::prefix('agent')->name('agent.')->group(function () {
    Route::get('/', [AgentController::class, 'index'])->name('index');
    Route::get('/scanner', [AgentController::class, 'scan'])->name('scan');
    Route::get('/resultat/{validation}', [AgentController::class, 'result'])->name('result');

    Route::middleware('auth')->group(function () {
        Route::post('/valider', [AgentController::class, 'validate'])->name('validate');
        Route::post('/valider-code', [AgentController::class, 'manual'])->name('manual');
    });
});

/* ------------------------------------------------------------------ */
/*  Espaces authentifiés */
/* ------------------------------------------------------------------ */

Route::middleware('auth')->group(function () {
    Route::get('/mes-reservations', [MyReservationsController::class, 'index'])->name('reservations.index');
    Route::get('/mes-reservations/{reference}', [MyReservationsController::class, 'show'])->name('reservations.show');
    Route::get('/profil', [ProfilController::class, 'show'])->name('profil.show');

    /* ---- Espace organisateur ---- */
    Route::prefix('organisateur')->name('organizer.')->middleware('role:organizer,admin')->group(function () {
        Route::get('/', [OrganizerDashboard::class, 'index'])->name('dashboard');

        Route::get('/evenements', [OrganizerEvent::class, 'index'])->name('events.index');
        Route::get('/evenements/creer', [OrganizerEvent::class, 'create'])->name('events.create');
        Route::post('/evenements', [OrganizerEvent::class, 'store'])->name('events.store');
        Route::get('/evenements/{event}/modifier', [OrganizerEvent::class, 'edit'])->name('events.edit');
        Route::put('/evenements/{event}', [OrganizerEvent::class, 'update'])->name('events.update');
        Route::delete('/evenements/{event}', [OrganizerEvent::class, 'destroy'])->name('events.destroy');

        Route::post('/evenements/{event}/photos', [OrganizerEvent::class, 'storeImages'])->name('events.images.store');
        Route::post('/evenements/{event}/photos/{image}/deplacer', [OrganizerEvent::class, 'moveImage'])->name('events.images.move');
        Route::delete('/evenements/{event}/photos/{image}', [OrganizerEvent::class, 'destroyImage'])->name('events.images.destroy');

        Route::get('/artistes', [OrganizerArtist::class, 'index'])->name('artists.index');
        Route::get('/artistes/creer', [OrganizerArtist::class, 'create'])->name('artists.create');
        Route::post('/artistes', [OrganizerArtist::class, 'store'])->name('artists.store');
        Route::get('/artistes/{artist}/modifier', [OrganizerArtist::class, 'edit'])->name('artists.edit');
        Route::put('/artistes/{artist}', [OrganizerArtist::class, 'update'])->name('artists.update');
        Route::delete('/artistes/{artist}', [OrganizerArtist::class, 'destroy'])->name('artists.destroy');

        Route::get('/avis', [OrganizerReview::class, 'index'])->name('reviews.index');
        Route::delete('/avis/{review}', [OrganizerReview::class, 'destroy'])->name('reviews.destroy');

        Route::get('/evenements/{event}/types-tickets', [OrganizerTicketType::class, 'index'])->name('events.ticket-types');
        Route::post('/evenements/{event}/types-tickets', [OrganizerTicketType::class, 'store'])->name('events.ticket-types.store');
        Route::put('/types-tickets/{ticketType}', [OrganizerTicketType::class, 'update'])->name('ticket-types.update');
        Route::delete('/types-tickets/{ticketType}', [OrganizerTicketType::class, 'destroy'])->name('ticket-types.destroy');

        Route::get('/utilisateurs', [OrganizerUser::class, 'index'])->name('users.index');
        Route::post('/utilisateurs', [OrganizerUser::class, 'store'])->name('users.store');
        Route::post('/utilisateurs/{target}/agent', [OrganizerUser::class, 'toggleAgent'])->name('users.toggle-agent');
        Route::post('/utilisateurs/{target}/actif', [OrganizerUser::class, 'toggleActive'])->name('users.toggle-active');
        Route::delete('/utilisateurs/{target}', [OrganizerUser::class, 'destroy'])->name('users.destroy');

        Route::get('/validations', [OrganizerValidations::class, 'index'])->name('validations.index');
        Route::get('/validations/par-agent', [OrganizerValidations::class, 'byAgent'])->name('validations.by-agent');

        Route::get('/reservations', [OrganizerReservation::class, 'index'])->name('reservations.index');
        Route::get('/reservations/{reservation}/telecharger', [OrganizerReservation::class, 'download'])->name('reservations.pdf');
        Route::get('/reservations/{reservation}/billets', [OrganizerReservation::class, 'downloadTickets'])->name('reservations.tickets');
        Route::get('/reservations/{reservation}/cartes', [OrganizerReservation::class, 'cards'])->name('reservations.cards');
        Route::post('/reservations/{reservation}/rembourser', [OrganizerReservation::class, 'refund'])->name('reservations.refund');
        Route::delete('/reservations/{reservation}', [OrganizerReservation::class, 'destroy'])->name('reservations.destroy');

        Route::get('/rapports', [OrganizerReport::class, 'index'])->name('reports.index');
        Route::get('/rapports/{event}', [OrganizerReport::class, 'show'])->name('reports.show');
        Route::get('/rapports/{event}/pdf', [OrganizerReport::class, 'download'])->name('reports.pdf');

        Route::get('/stocks', [OrganizerStock::class, 'index'])->name('stocks.index');
        Route::post('/stocks', [OrganizerStock::class, 'store'])->name('stocks.store');
        Route::put('/stocks/{allocation}', [OrganizerStock::class, 'update'])->name('stocks.update');
        Route::delete('/stocks/{allocation}', [OrganizerStock::class, 'destroy'])->name('stocks.destroy');

        Route::get('/points-de-vente', [OrganizerPointOfSale::class, 'index'])->name('pos.index');
        Route::get('/points-de-vente/{pointOfSale}', [OrganizerPointOfSale::class, 'show'])->name('pos.show');

        Route::get('/retraits', [OrganizerPayout::class, 'index'])->name('payouts.index');
        Route::post('/retraits', [OrganizerPayout::class, 'store'])->name('payouts.store');
        Route::post('/retraits/{payout}/sync', [OrganizerPayout::class, 'sync'])->name('payouts.sync');

        Route::get('/sessions-de-paiement', [OrganizerPaymentSession::class, 'index'])->name('payment-sessions.index');
        Route::post('/sessions-de-paiement/{payment}/sync', [OrganizerPaymentSession::class, 'sync'])->name('payment-sessions.sync');
    });

    /* ---- Espace point de vente ---- */
    Route::prefix('point-de-vente')->name('pos.')->middleware('role:user,admin,organizer')->group(function () {
        Route::get('/', [PointOfSaleController::class, 'dashboard'])->name('dashboard');
        Route::post('/ventes', [PointOfSaleController::class, 'store'])->name('sales.store');
    });

    /* ---- Administration ---- */
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/organisateurs', [AdminController::class, 'organizers'])->name('organizers.index');
        Route::get('/organisateurs/{organizer}', [AdminController::class, 'showOrganizer'])->name('organizers.show');
        Route::post('/organisateurs', [AdminController::class, 'storeOrganizer'])->name('organizers.store');
        Route::put('/organisateurs/{organizer}', [AdminController::class, 'updateOrganizer'])->name('organizers.update');
        Route::delete('/organisateurs/{organizer}', [AdminController::class, 'destroyOrganizer'])->name('organizers.destroy');
        Route::get('/utilisateurs', [AdminController::class, 'users'])->name('users.index');
        Route::patch('/utilisateurs/{target}/verifier', [AdminController::class, 'verify'])->name('users.verify');
    });
});

/* ------------------------------------------------------------------ */
/*  SEO */
/* ------------------------------------------------------------------ */

Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');

/* ------------------------------------------------------------------ */
/*  PWA */
/* ------------------------------------------------------------------ */

Route::get('/manifest.webmanifest', [PwaController::class, 'manifest'])->name('pwa.manifest');
Route::get('/sw.js', [PwaController::class, 'serviceWorker'])->name('pwa.sw');
Route::get('/hors-ligne', [PwaController::class, 'offline'])->name('pwa.offline');

Route::redirect('/dashboard', '/mes-reservations')->name('dashboard');
