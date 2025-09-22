<?php

namespace App\Providers;

use App\Support\AnilibriaClient;
use App\Support\AnimeCatalogService;
use App\Support\PosterStorage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
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
}
