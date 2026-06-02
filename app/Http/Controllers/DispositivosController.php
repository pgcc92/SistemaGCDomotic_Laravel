<?php

namespace App\Http\Controllers;

use App\Infrastructure\Remote\RemoteDataClient;
use App\Services\UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

final class DispositivosController
{
    public function __construct(
        private readonly RemoteDataClient $data,
        private readonly UploadService $upload,
    ) {
    }

    public function index(): View
    {
        return view('dispositivos.index');
    }

    public function data(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => $this->data->dispositivos(500),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $res = $this->data->dispositivo($id);
        if (isset($res['error'])) {
            return response()->json(['ok' => false, 'error' => (string) $res['error']], 422);
        }
        return response()->json(['ok' => true, 'data' => $res]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'cliente_wa' => ['nullable', 'string', 'max:30'],
            'modelo_cerradura' => ['nullable', 'string', 'max:120'],
            'serial_cerradura' => ['nullable', 'string', 'max:120'],
            'direccion' => ['nullable', 'string'],
            'fecha_instalacion' => ['nullable', 'date'],
            'gps_lat' => ['nullable', 'numeric'],
            'gps_lng' => ['nullable', 'numeric'],
            'notas_instalacion' => ['nullable', 'string'],
            'foto_url' => ['nullable', 'string'],
            'foto_thumb_url' => ['nullable', 'string'],
            'foto' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'fotos' => ['nullable', 'array', 'max:5'],
            'fotos.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $savedFotos = [];
        $files = $request->file('fotos', []);
        if (!is_array($files)) {
            $files = [];
        }
        if (count($files) > 5) {
            return response()->json(['ok' => false, 'error' => 'Puedes subir máximo 5 imágenes.'], 422);
        }

        try {
            foreach ($files as $file) {
                if ($file) {
                    $saved = $this->upload->saveImage($file, 'dispositivos');
                    $savedFotos[] = [
                        'url' => $saved['url'],
                        'thumb_url' => $saved['thumb_url'],
                    ];
                }
            }

            if ($savedFotos === [] && $request->hasFile('foto')) {
                $file = $request->file('foto');
                if ($file) {
                    $saved = $this->upload->saveImage($file, 'dispositivos');
                    $savedFotos[] = [
                        'url' => $saved['url'],
                        'thumb_url' => $saved['thumb_url'],
                    ];
                }
            }
        } catch (Throwable $exception) {
            $this->upload->deleteMany(array_column($savedFotos, 'url'));
            report($exception);

            return response()->json(['ok' => false, 'error' => 'No se pudieron guardar las imágenes.'], 422);
        }

        if ($savedFotos !== []) {
            $payload['foto_url'] = $savedFotos[0]['url'];
            $payload['foto_thumb_url'] = $savedFotos[0]['thumb_url'];
            $payload['fotos'] = $savedFotos;
        }

        try {
            $res = $this->data->crearDispositivo($payload);
        } catch (Throwable $exception) {
            $this->upload->deleteMany(array_column($savedFotos, 'url'));
            report($exception);

            return response()->json(['ok' => false, 'error' => 'No se pudo conectar con el servicio de datos. Inténtalo nuevamente.'], 503);
        }

        if (isset($res['error'])) {
            $this->upload->deleteMany(array_column($savedFotos, 'url'));
            return response()->json(['ok' => false, 'error' => (string) $res['error']], 422);
        }
        return response()->json(['ok' => true, 'data' => $res]);
    }
}
