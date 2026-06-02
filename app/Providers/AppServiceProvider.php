<?php

namespace App\Providers;

use App\Domain\Tenant\TenantContext;
use App\Infrastructure\ConfigStore\ConfigStore;
use App\Infrastructure\ConfigStore\FileConfigStore;
use App\Infrastructure\ConfigStore\RemoteApiConfigStore;
use App\Infrastructure\Http\RemoteApiClient;
use App\Support\LocalSqlite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantContext::class, fn () => new TenantContext());

        $this->app->singleton(RemoteApiClient::class, fn () => new RemoteApiClient());

        $this->app->bind(ConfigStore::class, function ($app) {
            $driver = (string) config('gc.config_store_driver', 'file');
            return match ($driver) {
                'api' => $app->make(RemoteApiConfigStore::class),
                default => $app->make(FileConfigStore::class),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Evita errores comunes en local cuando se usa SQLite y el archivo aún no existe.
        LocalSqlite::ensureDatabaseFileExists();
    }
}
