<?php

namespace App\Domain\Tenant;

final class TenantContext
{
    private string $tenantId = 'default';
    private Branding $branding;

    public function __construct()
    {
        $this->branding = new Branding(
            systemName: config('app.name', 'GC Dashboard'),
            sidebarName: config('app.name', 'GC Dashboard'),
            logoUrl: null,
            loginLogoUrl: null,
            faviconUrl: null,
            colors: [
                'primary' => '79 70 229',
                'secondary' => '14 165 233',
            ],
            fontFamily: 'Figtree',
            darkModeEnabled: true,
            loginGradient: [
                'from' => '16 185 129', // emerald-500
                'to' => '2 6 23', // slate-950
            ],
        );
    }

    public function tenantId(): string
    {
        return $this->tenantId;
    }

    public function branding(): Branding
    {
        return $this->branding;
    }

    public function setTenant(string $tenantId): void
    {
        $tenantId = trim($tenantId);
        $this->tenantId = $tenantId === '' ? 'default' : $tenantId;
    }

    public function setBranding(Branding $branding): void
    {
        $this->branding = $branding;
    }
}
