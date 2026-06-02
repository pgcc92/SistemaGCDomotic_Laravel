<?php

namespace App\Http\Middleware;

use App\Domain\Tenant\TenantContext;
use App\Infrastructure\ConfigStore\ConfigStore;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveTenant
{
    public function __construct(
        private readonly TenantContext $tenant,
        private readonly ConfigStore $configStore,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = (string) config('gc.tenant.default', 'default');

        if ((string) config('gc.tenant.mode') === 'subdomain') {
            $host = (string) $request->getHost();
            $base = (string) config('gc.tenant.base_domain');
            if ($base !== '' && str_ends_with($host, $base)) {
                $sub = trim(str_replace($base, '', $host), '.');
                if ($sub !== '') {
                    $tenantId = $sub;
                }
            }
        }

        $this->tenant->setTenant($tenantId);
        $this->tenant->setBranding($this->configStore->getBranding($tenantId));

        return $next($request);
    }
}

