<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final class HealthController
{
    public function __invoke(): JsonResponse
    {
        $dbOk = false;
        try {
            DB::select('select 1');
            $dbOk = true;
        } catch (\Throwable) {
            $dbOk = false;
        }

        return response()->json([
            'ok' => true,
            'service' => 'gc-data-api',
            'db' => $dbOk ? 'ok' : 'down',
        ]);
    }
}

