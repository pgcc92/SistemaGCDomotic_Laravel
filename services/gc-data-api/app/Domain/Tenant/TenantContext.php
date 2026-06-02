<?php

namespace App\Domain\Tenant;

final class TenantContext
{
    private string $tenantId = 'default';

    public function tenantId(): string
    {
        return $this->tenantId;
    }

    public function setTenant(string $tenantId): void
    {
        $tenantId = trim($tenantId);
        $this->tenantId = $tenantId === '' ? 'default' : $tenantId;
    }
}

