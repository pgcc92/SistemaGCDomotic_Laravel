<?php

namespace App\Http\Controllers;

use App\Infrastructure\Http\RemoteApiClient;
use Illuminate\Http\JsonResponse;

final class MeController
{
    public function __construct(
        private readonly RemoteApiClient $api,
    ) {
    }

    public function rbac(): JsonResponse
    {
        $res = $this->api->request()->get('/api/v1/rbac/me');
        if (!$res->ok()) {
            return response()->json([
                'ok' => false,
                'status' => $res->status(),
                'error' => $res->json('error') ?: 'No se pudo obtener RBAC remoto.',
                'body' => $res->body(),
            ], 502);
        }

        return response()->json($res->json());
    }
}

