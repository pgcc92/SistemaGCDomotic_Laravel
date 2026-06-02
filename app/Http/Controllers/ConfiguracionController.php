<?php

namespace App\Http\Controllers;

use App\Domain\Tenant\TenantContext;
use App\Infrastructure\ConfigStore\ConfigStore;
use App\Services\UploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ConfiguracionController
{
    public function __construct(
        private readonly TenantContext $tenant,
        private readonly ConfigStore $configStore,
        private readonly UploadService $uploads,
    ) {
    }

    public function edit(): View
    {
        return view('configuracion.edit', [
            'tenantId' => $this->tenant->tenantId(),
            'branding' => $this->tenant->branding(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'system_name' => ['required', 'string', 'max:120'],
            'sidebar_name' => ['nullable', 'string', 'max:120'],
            'primary_rgb' => ['required', 'regex:/^\\d{1,3}\\s\\d{1,3}\\s\\d{1,3}$/'],
            'secondary_rgb' => ['required', 'regex:/^\\d{1,3}\\s\\d{1,3}\\s\\d{1,3}$/'],
            'login_from_rgb' => ['required', 'regex:/^\\d{1,3}\\s\\d{1,3}\\s\\d{1,3}$/'],
            'login_to_rgb' => ['required', 'regex:/^\\d{1,3}\\s\\d{1,3}\\s\\d{1,3}$/'],
            'font_family' => ['nullable', 'string', 'max:80'],
            'dark_mode_enabled' => ['nullable', 'boolean'],
            'logo_file' => ['nullable', 'file', 'max:10240', 'mimetypes:image/jpeg,image/png,image/webp'],
            'login_logo_file' => ['nullable', 'file', 'max:10240', 'mimetypes:image/jpeg,image/png,image/webp'],
            'favicon_file' => ['nullable', 'file', 'max:10240', 'mimetypes:image/png,image/webp,image/jpeg'],
            'remove_logo' => ['nullable', 'boolean'],
            'remove_login_logo' => ['nullable', 'boolean'],
            'remove_favicon' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'system_name' => $validated['system_name'],
            'sidebar_name' => $validated['sidebar_name'] ?? null,
            'colors' => [
                'primary' => $validated['primary_rgb'],
                'secondary' => $validated['secondary_rgb'],
            ],
            'login_gradient' => [
                'from' => $validated['login_from_rgb'],
                'to' => $validated['login_to_rgb'],
            ],
            'font_family' => $validated['font_family'] ?? null,
            'dark_mode_enabled' => (bool) ($validated['dark_mode_enabled'] ?? false),
        ];

        // Branding assets (settings): logo, login logo, favicon
        if ((bool) ($validated['remove_logo'] ?? false)) {
            $payload['logo_url'] = null;
        }
        if ((bool) ($validated['remove_login_logo'] ?? false)) {
            $payload['login_logo_url'] = null;
        }
        if ((bool) ($validated['remove_favicon'] ?? false)) {
            $payload['favicon_url'] = null;
        }

        if ($request->hasFile('logo_file')) {
            $up = $this->uploads->saveImage($request->file('logo_file'), 'settings');
            $payload['logo_url'] = $up['url'];
        }
        if ($request->hasFile('login_logo_file')) {
            $up = $this->uploads->saveImage($request->file('login_logo_file'), 'settings');
            $payload['login_logo_url'] = $up['url'];
        }
        if ($request->hasFile('favicon_file')) {
            $up = $this->uploads->saveImage($request->file('favicon_file'), 'settings');
            $payload['favicon_url'] = $up['url'];
        }

        $this->configStore->putBranding($this->tenant->tenantId(), [
            ...$payload,
        ]);

        return back()->with('status', 'Configuración actualizada.');
    }
}
