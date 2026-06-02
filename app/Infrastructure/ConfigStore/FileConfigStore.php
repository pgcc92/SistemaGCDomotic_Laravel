<?php

namespace App\Infrastructure\ConfigStore;

use App\Domain\Tenant\Branding;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;

final class FileConfigStore implements ConfigStore
{
    public function __construct(
        private readonly Filesystem $files,
    ) {
    }

    private function path(string $tenantId): string
    {
        $safeTenant = preg_replace('/[^a-zA-Z0-9_-]/', '', $tenantId) ?: 'default';
        return storage_path("app/tenants/{$safeTenant}.json");
    }

    public function getBranding(string $tenantId): Branding
    {
        $path = $this->path($tenantId);

        if (!$this->files->exists($path)) {
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

        $json = json_decode($this->files->get($path), true);
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
        $path = $this->path($tenantId);
        $dir = dirname($path);
        if (!$this->files->exists($dir)) {
            $this->files->makeDirectory($dir, 0755, true);
        }

        $current = $this->getBranding($tenantId);

        $next = [
            'system_name' => (string) ($payload['system_name'] ?? $current->systemName),
            'sidebar_name' => (string) ($payload['sidebar_name'] ?? $current->sidebarName ?? $current->systemName),
            'logo_url' => $payload['logo_url'] ?? $current->logoUrl,
            'login_logo_url' => $payload['login_logo_url'] ?? $current->loginLogoUrl,
            'favicon_url' => $payload['favicon_url'] ?? $current->faviconUrl,
            'colors' => [
                'primary' => (string) Arr::get($payload, 'colors.primary', $current->colors['primary']),
                'secondary' => (string) Arr::get($payload, 'colors.secondary', $current->colors['secondary']),
            ],
            'font_family' => $payload['font_family'] ?? $current->fontFamily,
            'dark_mode_enabled' => (bool) ($payload['dark_mode_enabled'] ?? $current->darkModeEnabled),
            'login_gradient' => [
                'from' => (string) Arr::get($payload, 'login_gradient.from', $current->loginGradient['from'] ?? '16 185 129'),
                'to' => (string) Arr::get($payload, 'login_gradient.to', $current->loginGradient['to'] ?? '2 6 23'),
            ],
        ];

        $this->files->put($path, json_encode($next, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $this->getBranding($tenantId);
    }
}
