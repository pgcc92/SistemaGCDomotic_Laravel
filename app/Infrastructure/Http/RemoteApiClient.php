<?php

namespace App\Infrastructure\Http;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class RemoteApiClient
{
    public function request(?string $tenantId = null): PendingRequest
    {
        $baseUrl = rtrim((string) config('gc.remote_api.base_url'), '/');
        $apiKey = (string) config('gc.remote_api.api_key');

        $request = Http::baseUrl($baseUrl)
            ->acceptJson()
            ->timeout((int) config('gc.remote_api.timeout_seconds', 10));

        $logAuth = (bool) config('app.debug');

        // Si hay token de usuario en sesión, usamos Bearer (por usuario).
        $userToken = session('remote_user_token');
        if (is_string($userToken) && $userToken !== '') {
            $request = $request->withToken($userToken);
            if ($logAuth) {
                Log::debug('Remote API auth mode: bearer', ['base_url' => $baseUrl]);
            }
        } elseif ($apiKey !== '') {
            // Fallback: API key de integración (n8n / service-to-service).
            $request = $request->withHeaders(['X-API-Key' => $apiKey]);
            if ($logAuth) {
                Log::debug('Remote API auth mode: api_key', ['base_url' => $baseUrl]);
            }
        } else {
            if ($logAuth) {
                Log::warning('Remote API auth mode: none', ['base_url' => $baseUrl]);
            }
        }

        if ($tenantId) {
            $request = $request->withHeaders(['X-Tenant' => $tenantId]);
        }

        return $request;
    }
}
