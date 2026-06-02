<?php

namespace App\Infrastructure\Remote;

use App\Infrastructure\Http\RemoteApiClient;
use Illuminate\Support\Facades\Cache;

final class RemoteRbacClient
{
    public function __construct(
        private readonly RemoteApiClient $api,
    ) {
    }

    /** @return array<string,array<string,bool>> */
    public function myPermissions(): array
    {
        $userToken = session('remote_user_token');
        $keySuffix = is_string($userToken) && $userToken !== '' ? sha1($userToken) : 'guest';
        $cacheKey = "rbac.me.{$keySuffix}";

        // Cache corto: permite que cambios de permisos se reflejen rápido (sidebar/RBAC).
        return Cache::remember($cacheKey, 10, function () {
            $res = $this->api->request()->get('/api/v1/rbac/me');
            if (!$res->ok()) {
                return [];
            }
            $p = $res->json('data.permissions');
            return is_array($p) ? $p : [];
        });
    }
}
