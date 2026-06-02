<?php

namespace App\Http\Controllers;

use App\Infrastructure\Remote\RemoteDataClient;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

final class SoporteVideosWebController
{
    public function __construct(
        private readonly RemoteDataClient $data,
    ) {
    }

    public function index(): View
    {
        return view('soporte-videos.index');
    }

    public function data(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => $this->data->soporteVideos(500),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $res = $this->data->soporteVideo($id);
        if (isset($res['error'])) {
            return response()->json(['ok' => false, 'error' => (string) $res['error']], 422);
        }
        return response()->json(['ok' => true, 'data' => $res]);
    }
}

