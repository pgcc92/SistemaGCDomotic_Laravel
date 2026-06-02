<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Login del dashboard (API key -> emite token de usuario). Más laxo en dev.
        RateLimiter::for('auth_login', function (Request $request) {
            $apiKey = (string) $request->header('X-API-Key', '');
            if ($apiKey === '') {
                $auth = (string) $request->header('Authorization', '');
                if (preg_match('/^Bearer\\s+(?<t>.+)$/i', $auth, $m)) {
                    $apiKey = (string) ($m['t'] ?? '');
                }
            }

            $bucket = $request->ip().'|'.substr(hash('sha1', $apiKey), 0, 12);

            return Limit::perMinute((int) env('GC_AUTH_LOGIN_PER_MINUTE', 30))->by($bucket);
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
