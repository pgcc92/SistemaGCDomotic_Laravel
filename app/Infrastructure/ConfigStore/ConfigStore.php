<?php

namespace App\Infrastructure\ConfigStore;

use App\Domain\Tenant\Branding;

interface ConfigStore
{
    public function getBranding(string $tenantId): Branding;

    /** @param array{system_name?:string,sidebar_name?:string|null,logo_url?:string|null,login_logo_url?:string|null,favicon_url?:string|null,colors?:array{primary?:string,secondary?:string},font_family?:string|null,dark_mode_enabled?:bool,login_gradient?:array{from?:string,to?:string}} $payload */
    public function putBranding(string $tenantId, array $payload): Branding;
}
