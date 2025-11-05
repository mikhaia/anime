<?php

namespace App\Providers;

use App\Support\AnilibriaClient;
use App\Support\AnimeCatalogService;
use App\Support\PosterStorage;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(AnilibriaClient::class, function (): AnilibriaClient {
            return new AnilibriaClient();
        });

        $this->app->singleton(PosterStorage::class, function (): PosterStorage {
            return new PosterStorage();
        });

        $this->app->singleton(AnimeCatalogService::class, function ($app): AnimeCatalogService {
            return new AnimeCatalogService(
                $app->make(AnilibriaClient::class),
                $app->make(PosterStorage::class),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view) {
            $currentUser = \App\Support\Auth::user();
            $favoriteIds = [];

            if ($currentUser) {
                $favoriteIds = $currentUser->favorites()
                    ->pluck('anime_id')
                    ->map(static fn($value) => (int) $value)
                    ->all();
            }

            $view->with('currentUser', $currentUser);
            $view->with('loginError', \App\Support\Session::getFlash('login_error'));
            $view->with('openLoginModal', \App\Support\Session::getFlash('open_login_modal'));
            $view->with('loginEmail', \App\Support\Session::getFlash('login_email'));
            $view->with('loginRedirect', \App\Support\Session::getFlash('login_redirect'));
            $view->with('favoriteIds', $favoriteIds);
        });
    }
}
