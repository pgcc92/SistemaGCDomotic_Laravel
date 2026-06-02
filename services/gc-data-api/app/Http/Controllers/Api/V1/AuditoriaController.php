<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class AuditoriaController
{
    public function index(Request $request): JsonResponse
    {
        $limit = max(1, min(200, (int) $request->query('limit', 50)));
        $rows = DB::table('audit_log as a')
            ->leftJoin('usuarios as u', 'a.usuario_id', '=', 'u.id')
            ->select([
                'a.id',
                'a.usuario_id',
                'a.accion',
                'a.entidad',
                'a.entidad_id',
                'a.payload',
                'a.ip',
                'a.created_at',
                DB::raw('u.nombre as usuario_nombre'),
                DB::raw('u.numero_documento as usuario_documento'),
                DB::raw('u.email as usuario_email'),
            ])
            ->orderBy('a.id', 'desc')
            ->limit($limit)
            ->get();
        return response()->json(['ok' => true, 'data' => $rows]);
    }
}
