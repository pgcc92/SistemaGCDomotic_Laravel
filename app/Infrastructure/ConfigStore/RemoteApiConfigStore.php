<?php

namespace App\Infrastructure\ConfigStore;

use App\Domain\Tenant\Branding;
use App\Infrastructure\Http\RemoteApiClient;
use Illuminate\Support\Arr;

final class RemoteApiConfigStore implements ConfigStore
{
    public function __construct(
        private readonly RemoteApiClient $api,
    ) {
    }

    public function getBranding(string $tenantId): Branding
    {
        $endpoint = (string) config('gc.remote_api.endpoints.branding_get', '/api/v1/branding');

        $res = $this->api->request($tenantId)->get($endpoint);
        if (!$res->ok()) {
            // Fallback a defaults locales si el endpoint remoto aún no existe.
            return new Branding(
                systemName: config('app.name', 'GC Dashboard'),
                sidebarName: config('app.name', 'GC Dashboard'),
                logoUrl: null,
                loginLogoUrl: null,
                faviconUrl: null,
                colors: [
                    'primary' => config('gc.branding.colors.primary'),
                    'secondary' => config('gc.branding.colors.secondary'),
                ],
                fontFamily: config('gc.branding.font_family'),
                darkModeEnabled: (bool) config('gc.branding.dark_mode_enabled', true),
                loginGradient: [
                    'from' => config('gc.branding.login_gradient.from', '16 185 129'),
                    'to' => config('gc.branding.login_gradient.to', '2 6 23'),
                ],
            );
        }

        $json = $res->json();
        $json = is_array($json) ? $json : [];

        return new Branding(
            systemName: (string) Arr::get($json, 'system_name', config('app.name', 'GC Dashboard')),
            sidebarName: Arr::get($json, 'sidebar_name', Arr::get($json, 'system_name', config('app.name', 'GC Dashboard'))),
            logoUrl: Arr::get($json, 'logo_url'),
            loginLogoUrl: Arr::get($json, 'login_logo_url'),
            faviconUrl: Arr::get($json, 'favicon_url'),
            colors: [
                'primary' => (string) Arr::get($json, 'colors.primary', config('gc.branding.colors.primary')),
                'secondary' => (string) Arr::get($json, 'colors.secondary', config('gc.branding.colors.secondary')),
            ],
            fontFamily: Arr::get($json, 'font_family', config('gc.branding.font_family')),
            darkModeEnabled: (bool) Arr::get($json, 'dark_mode_enabled', config('gc.branding.dark_mode_enabled', true)),
            loginGradient: [
                'from' => (string) Arr::get($json, 'login_gradient.from', config('gc.branding.login_gradient.from', '16 185 129')),
                'to' => (string) Arr::get($json, 'login_gradient.to', config('gc.branding.login_gradient.to', '2 6 23')),
            ],
        );
    }

    public function putBranding(string $tenantId, array $payload): Branding
    {
        $endpoint = (string) config('gc.remote_api.endpoints.branding_put', '/api/v1/branding');

        $res = $this->api->request($tenantId)->put($endpoint, $payload);
        if ($res->ok()) {
            return $this->getBranding($tenantId);
        }

        // Si remoto no soporta edición aún, mantenemos un fallback local.
        return $this->getBranding($tenantId);
    }
}
