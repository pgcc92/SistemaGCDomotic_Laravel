<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class SoporteVideosController
{
    public function index(Request $request): JsonResponse
    {
        $limit = max(1, min(200, (int) $request->query('limit', 50)));
        $rows = DB::table('soporte_videos')->orderBy('id', 'desc')->limit($limit)->get();
        return response()->json(['ok' => true, 'data' => $rows]);
    }

    public function show(int $id): JsonResponse
    {
        $row = DB::table('soporte_videos')->where('id', $id)->first();
        if (!$row) {
            return response()->json(['ok' => false, 'error' => 'Not found'], 404);
        }
        return response()->json(['ok' => true, 'data' => $row]);
    }
}

