<?php

namespace App\Providers;

use App\Services\DexPay\DexPayAccount;
use App\Services\DexPay\DexPayCheckout;
use App\Services\DexPay\DexPayClient;
use App\Services\Icon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Client DexPay partagé : une seule instance (cache HTTP, config).
        $this->app->singleton(DexPayClient::class, fn () => DexPayClient::make());

        $this->app->singleton(
            DexPayCheckout::class,
            fn ($app) => new DexPayCheckout($app->make(DexPayClient::class))
        );

        $this->app->singleton(
            DexPayAccount::class,
            fn ($app) => new DexPayAccount($app->make(DexPayClient::class))
        );
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Paginator::useTailwind();
        Model::preventLazyLoading(false);
        Icon::register();

        Password::defaults(fn () => Password::min(8));

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Empêche l'affichage d'un événement en brouillon au public.
        Blade::if('published', fn ($event) => $event->status !== 'draft');

        $this->shareViewData();
    }

    /** Données communes à toutes les vues (navigation, badge « agent »…). */
    private function shareViewData(): void
    {
        View::composer('*', function ($view) {
            $user = auth()->user();

            $view->with('currentUser', $user);
        });
    }
}
