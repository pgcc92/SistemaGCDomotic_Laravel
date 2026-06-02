<?php

namespace App\Http\Controllers;

use App\Infrastructure\Remote\RemoteDataClient;
use App\Services\UploadService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

final class AgendaController
{
    public function __construct(
        private readonly RemoteDataClient $data,
        private readonly UploadService $upload,
    ) {
    }

    public function index(): View
    {
        return view('agenda.index');
    }

    public function data(Request $request)
    {
        // Nota: `all=1` habilita visibilidad general cuando el usuario tiene permiso `agenda.ver_general`.
        // Se envía al API remoto para que aplique el scope correcto.
        $params = $request->only(['from', 'to', 'estado', 'tecnico_id', 'q', 'all']);
        return response()->json([
            'ok' => true,
            'data' => $this->data->agenda(200, $params),
        ]);
    }

    public function tecnicosData(Request $request): JsonResponse
    {
        $limit = max(1, min(200, (int) $request->query('limit', 200)));
        $q = trim((string) $request->query('q', ''));

        $filters = [
            'dashboard_activo' => 1,
            // Solo roles operativos para agenda.
            // Nota: esto no otorga permisos, solo limita el catálogo visible.
            'role_codes' => 'tecnico,instalador',
        ];

        return response()->json([
            'ok' => true,
            'data' => $this->data->usuarios($limit, $q !== '' ? $q : null, $filters),
        ]);
    }

    public function show(int $id)
    {
        $res = $this->data->agendaItem($id);
        if (isset($res['error'])) {
            return response()->json(['ok' => false, 'error' => (string) $res['error']], 422);
        }
        return response()->json(['ok' => true, 'data' => $res]);
    }

    public function store(Request $request)
    {
        $payload = $request->validate([
            'tipo' => ['required', 'string', 'max:20'],
            'estado' => ['required', 'string', 'max:20'],
            'venta_id' => ['nullable', 'integer'],
            'ticket_id' => ['nullable', 'string', 'max:50'],
            'cliente_id' => ['nullable', 'integer'],
            'cliente_wa' => ['nullable', 'string', 'max:30'],
            'tecnico_id' => ['nullable', 'integer'],
            'sucursal_id' => ['nullable', 'integer'],
            'titulo' => ['nullable', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'fecha_programada' => ['required', 'date'],
            'duracion_min' => ['nullable', 'integer', 'min:5', 'max:1440'],
            'prioridad' => ['nullable', 'string', 'max:20'],
            'notas' => ['nullable', 'string'],
        ]);

        $res = $this->data->agendaCrear($payload);
        if (isset($res['error'])) {
            return response()->json(['ok' => false, 'error' => (string) $res['error']], 422);
        }
        return response()->json(['ok' => true, 'data' => $res], 201);
    }

    public function update(Request $request, int $id)
    {
        $payload = $request->validate([
            'tipo' => ['nullable', 'string', 'max:20'],
            'estado' => ['nullable', 'string', 'max:20'],
            'venta_id' => ['nullable', 'integer'],
            'ticket_id' => ['nullable', 'string', 'max:50'],
            'cliente_id' => ['nullable', 'integer'],
            'cliente_wa' => ['nullable', 'string', 'max:30'],
            'tecnico_id' => ['nullable', 'integer'],
            'sucursal_id' => ['nullable', 'integer'],
            'titulo' => ['nullable', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'fecha_programada' => ['nullable', 'date'],
            'duracion_min' => ['nullable', 'integer', 'min:5', 'max:1440'],
            'prioridad' => ['nullable', 'string', 'max:20'],
            'notas' => ['nullable', 'string'],
        ]);

        $res = $this->data->agendaActualizar($id, $payload);
        if (isset($res['error'])) {
            return response()->json(['ok' => false, 'error' => (string) $res['error']], 422);
        }
        return response()->json(['ok' => true, 'data' => $res]);
    }

    public function destroy(int $id)
    {
        $ok = $this->data->agendaEliminar($id);
        return $ok
            ? response()->json(['ok' => true])
            : response()->json(['ok' => false, 'error' => 'No se pudo eliminar.'], 422);
    }

    public function complete(Request $request, int $id)
    {
        $payload = $request->validate([
            'terminado_at' => ['required', 'date'],
            'notas' => ['nullable', 'string'],
            'foto' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'fotos' => ['nullable', 'array', 'max:5'],
            'fotos.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'gps_lat' => ['nullable', 'numeric'],
            'gps_lng' => ['nullable', 'numeric'],
        ]);

        $agenda = $this->data->agendaItem($id);
        if (isset($agenda['error'])) {
            return response()->json(['ok' => false, 'error' => (string) $agenda['error']], 422);
        }

        $files = [];
        foreach (($request->file('fotos') ?: []) as $file) {
            if ($file) {
                $files[] = $file;
            }
        }
        if ($files === [] && $request->hasFile('foto') && $request->file('foto')) {
            $files[] = $request->file('foto');
        }
        if (count($files) > 5) {
            return response()->json(['ok' => false, 'error' => 'Puedes subir máximo 5 imágenes.'], 422);
        }

        $fotosPayload = [];
        foreach ($files as $file) {
            $saved = $this->upload->saveImage($file, 'dispositivos');
            $fotosPayload[] = [
                'url' => $saved['url'],
                'thumb_url' => $saved['thumb_url'],
            ];
        }

        $fotoUrl = $fotosPayload[0]['url'] ?? null;
        $fotoThumb = $fotosPayload[0]['thumb_url'] ?? null;

        $terminadoAt = (string) $payload['terminado_at'];
        $notaExtra = trim((string) ($payload['notas'] ?? ''));
        $titulo = (string) ($agenda['titulo'] ?? '');
        $tipo = (string) ($agenda['tipo'] ?? '');

        $metaModelo = null;
        $metaSerial = null;
        $metaDireccion = null;
        $agendaNotasRaw = (string) ($agenda['notas'] ?? '');
        if (preg_match('/\\[INSTALACION\\]([^\\n]*)/i', $agendaNotasRaw, $m)) {
            $json = trim((string) ($m[1] ?? ''));
            if ($json !== '') {
                try {
                    $meta = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
                    $metaModelo = is_array($meta) ? (string) ($meta['modelo'] ?? '') : '';
                    $metaSerial = is_array($meta) ? (string) ($meta['serial'] ?? '') : '';
                    $metaDireccion = is_array($meta) ? (string) ($meta['direccion'] ?? '') : '';
                    $metaModelo = trim($metaModelo) !== '' ? trim($metaModelo) : null;
                    $metaSerial = trim($metaSerial) !== '' ? trim($metaSerial) : null;
                    $metaDireccion = trim($metaDireccion) !== '' ? trim($metaDireccion) : null;
                } catch (\Throwable) {
                    // ignore
                }
            }
        }

        $notasInstalacion = trim(implode("\n", array_filter([
            "Evidencia agenda #{$id}" . ($titulo !== '' ? " — {$titulo}" : ''),
            $tipo !== '' ? "Tipo: {$tipo}" : null,
            "Terminado: {$terminadoAt}",
            $notaExtra !== '' ? $notaExtra : null,
        ], fn ($v) => is_string($v) && trim($v) !== '')));

        $clienteWa = (string) ($agenda['cliente_wa'] ?? '');
        $fechaInstalacion = null;
        try {
            $fechaInstalacion = \Carbon\Carbon::parse($terminadoAt)->toDateString();
        } catch (\Throwable) {
            $fechaInstalacion = null;
        }

        $dispositivo = $this->data->crearDispositivo([
            'cliente_wa' => $clienteWa !== '' ? $clienteWa : null,
            'modelo_cerradura' => $metaModelo,
            'serial_cerradura' => $metaSerial,
            'direccion' => $metaDireccion ?: ((string) ($agenda['descripcion'] ?? '') ?: null),
            'fecha_instalacion' => $fechaInstalacion,
            'gps_lat' => $payload['gps_lat'] ?? null,
            'gps_lng' => $payload['gps_lng'] ?? null,
            'notas_instalacion' => $notasInstalacion !== '' ? $notasInstalacion : null,
            'foto_url' => $fotoUrl,
            'foto_thumb_url' => $fotoThumb,
            'fotos' => $fotosPayload,
        ]);

        if (isset($dispositivo['error'])) {
            return response()->json(['ok' => false, 'error' => (string) $dispositivo['error']], 422);
        }

        $dispositivoId = (int) ($dispositivo['id'] ?? 0);

        $update = [
            'estado' => 'REALIZADA',
            'terminado_at' => $terminadoAt,
            'evidencia_dispositivo_id' => $dispositivoId > 0 ? $dispositivoId : null,
        ];

        $existingNotas = trim((string) ($agenda['notas'] ?? ''));
        $updateNotas = trim(implode("\n\n", array_filter([$existingNotas, $notasInstalacion])));
        if ($updateNotas !== '') {
            $update['notas'] = $updateNotas;
        }

        $updated = $this->data->agendaActualizar($id, $update);
        if (isset($updated['error'])) {
            return response()->json(['ok' => false, 'error' => (string) $updated['error']], 422);
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'agenda' => $updated,
                'evidencia_dispositivo' => $dispositivo,
            ],
        ]);
    }
}
