<?php

namespace App\Http\Middleware;

use App\Domain\Tenant\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveTenant
{
    public function __construct(
        private readonly TenantContext $tenant,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = (string) config('gc.tenant.default', 'default');

        // 1) Prefer header (útil para proxies / n8n / server-to-server).
        $headerTenant = trim((string) $request->header('X-Tenant', ''));
        if ($headerTenant !== '') {
            $tenantId = $headerTenant;
        }

        // 2) Si está habilitado, resuelve por subdominio.
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

        // Sanitiza para evitar inyección en logs/headers.
        $tenantId = preg_replace('/[^a-zA-Z0-9_-]/', '', $tenantId) ?: 'default';
        $this->tenant->setTenant($tenantId);

        return $next($request);
    }
}

