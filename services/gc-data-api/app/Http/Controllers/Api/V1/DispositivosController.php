<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Rbac\RbacService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class DispositivosController
{
    public function __construct(
        private readonly RbacService $rbac,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $uid = (int) $request->attributes->get('remote_uid', 0);
        $canViewAll = $uid > 0 ? $this->rbac->canViewAll($uid, 'dispositivos') : false;

        $limit = max(1, min(200, (int) $request->query('limit', 50)));
        $q = DB::table('dispositivos_cliente as dc')
            ->leftJoin('usuarios as u', 'u.id', '=', 'dc.instalador_id')
            ->select('dc.*', 'u.nombre as instalador_nombre', 'u.numero_documento as instalador_documento')
            ->orderBy('dc.id', 'desc');

        // Instalador no-admin: solo ve los suyos
        if (!$canViewAll && $uid > 0) {
            $q->where('dc.instalador_id', $uid);
        }

        $rows = $q->limit($limit)->get();
        return response()->json(['ok' => true, 'data' => $rows]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $uid = (int) $request->attributes->get('remote_uid', 0);
        $canViewAll = $uid > 0 ? $this->rbac->canViewAll($uid, 'dispositivos') : false;

        $row = DB::table('dispositivos_cliente as dc')
            ->leftJoin('usuarios as u', 'u.id', '=', 'dc.instalador_id')
            ->select('dc.*', 'u.nombre as instalador_nombre', 'u.numero_documento as instalador_documento')
            ->where('dc.id', $id)
            ->first();
        if (!$row) {
            return response()->json(['ok' => false, 'error' => 'Not found'], 404);
        }

        if (!$canViewAll && $uid > 0) {
            if ((int) $row->instalador_id !== $uid) {
                return response()->json(['ok' => false, 'error' => 'Forbidden'], 403);
            }
        }

        $fotos = DB::table('dispositivo_fotos')->where('dispositivo_id', $id)->orderBy('id', 'desc')->get();

        return response()->json([
            'ok' => true,
            'data' => [
                'dispositivo' => $row,
                'fotos' => $fotos,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $uid = (int) $request->attributes->get('remote_uid', 0);
        if ($uid <= 0) {
            return response()->json(['ok' => false, 'error' => 'Unauthorized'], 401);
        }

        $payload = $request->validate([
            'cliente_wa' => ['nullable', 'string', 'max:30'],
            'modelo_cerradura' => ['nullable', 'string', 'max:120'],
            'serial_cerradura' => ['nullable', 'string', 'max:120'],
            'direccion' => ['nullable', 'string'],
            'fecha_instalacion' => ['nullable', 'date'],
            'gps_lat' => ['nullable', 'numeric'],
            'gps_lng' => ['nullable', 'numeric'],
            'notas_instalacion' => ['nullable', 'string'],
            // URLs ya procesadas por el frontend/servicio de archivos
            'foto_url' => ['nullable', 'string'],
            'foto_thumb_url' => ['nullable', 'string'],
            'fotos' => ['nullable', 'array', 'max:5'],
            'fotos.*.url' => ['required_with:fotos', 'string'],
            'fotos.*.thumb_url' => ['nullable', 'string'],
        ]);

        $fotos = collect($payload['fotos'] ?? [])
            ->filter(fn ($foto) => is_array($foto) && !empty($foto['url']))
            ->take(5)
            ->values();

        if (empty($payload['foto_url']) && $fotos->isNotEmpty()) {
            $payload['foto_url'] = (string) $fotos[0]['url'];
            $payload['foto_thumb_url'] = $fotos[0]['thumb_url'] ?? null;
        }

        $id = (int) DB::table('dispositivos_cliente')->insertGetId([
            'cliente_wa' => $payload['cliente_wa'] ?? null,
            'modelo_cerradura' => $payload['modelo_cerradura'] ?? null,
            'serial_cerradura' => $payload['serial_cerradura'] ?? null,
            'direccion' => $payload['direccion'] ?? null,
            'fecha_instalacion' => $payload['fecha_instalacion'] ?? null,
            'instalador_id' => $uid,
            'foto_url' => $payload['foto_url'] ?? null,
            'foto_thumb_url' => $payload['foto_thumb_url'] ?? null,
            'gps_lat' => $payload['gps_lat'] ?? null,
            'gps_lng' => $payload['gps_lng'] ?? null,
            'notas_instalacion' => $payload['notas_instalacion'] ?? null,
            'creado_en' => now(),
        ]);

        if ($fotos->isEmpty() && !empty($payload['foto_url'])) {
            $fotos = collect([[
                'url' => (string) $payload['foto_url'],
                'thumb_url' => $payload['foto_thumb_url'] ?? null,
            ]]);
        }

        if ($fotos->isNotEmpty()) {
            $seen = [];
            $rows = [];
            foreach ($fotos as $foto) {
                $url = (string) ($foto['url'] ?? '');
                if ($url === '' || isset($seen[$url])) {
                    continue;
                }
                $seen[$url] = true;
                $rows[] = [
                    'dispositivo_id' => $id,
                    'url' => $url,
                    'thumb_url' => $foto['thumb_url'] ?? null,
                    'tipo' => 'cerradura',
                    'tamano_bytes' => null,
                    'mime' => null,
                    'subido_por' => $uid,
                    'created_at' => now(),
                ];
            }
            if ($rows !== []) {
                DB::table('dispositivo_fotos')->insert($rows);
            }
        }

        DB::table('audit_log')->insert([
            'usuario_id' => $uid,
            'accion' => 'dispositivo_created',
            'entidad' => 'dispositivos_cliente',
            'entidad_id' => (string) $id,
            'payload' => DB::raw("'{}'::jsonb"),
            'ip' => $request->ip(),
            'created_at' => now(),
        ]);

        $fresh = DB::table('dispositivos_cliente as dc')
            ->leftJoin('usuarios as u', 'u.id', '=', 'dc.instalador_id')
            ->select('dc.*', 'u.nombre as instalador_nombre', 'u.numero_documento as instalador_documento')
            ->where('dc.id', $id)
            ->first();
        return response()->json(['ok' => true, 'data' => $fresh]);
    }
}
