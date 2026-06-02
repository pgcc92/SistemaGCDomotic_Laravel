<?php

namespace App\Providers;

use App\Domain\Tenant\TenantContext;
use App\Infrastructure\Db\SchemaIntrospector;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantContext::class, fn () => new TenantContext());
        $this->app->singleton(SchemaIntrospector::class, fn () => new SchemaIntrospector());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
