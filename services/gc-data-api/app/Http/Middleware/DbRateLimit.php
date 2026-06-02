<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

final class DbRateLimit
{
    public function handle(Request $request, Closure $next, int $max = 10, int $seconds = 60): Response
    {
        $table = 'rate_limits';

        $endpoint = (string) $request->path(); // e.g. api/v1/auth/login
        $ip = (string) $request->ip();
        $doc = preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $request->input('documento', '')));
        $key = $ip.'|'.$doc;

        $windowStart = now()->startOfMinute(); // ventana 60s alineada al minuto

        $allowed = DB::transaction(function () use ($table, $key, $endpoint, $windowStart, $max) {
            $row = DB::table($table)
                ->where('clave', $key)
                ->where('endpoint', $endpoint)
                ->where('window_start', $windowStart)
                ->lockForUpdate()
                ->first();

            if (!$row) {
                DB::table($table)->insert([
                    'clave' => $key,
                    'endpoint' => $endpoint,
                    'count' => 1,
                    'window_start' => $windowStart,
                ]);
                return true;
            }

            $count = (int) $row->count;
            if ($count >= $max) {
                return false;
            }

            DB::table($table)->where('id', $row->id)->update(['count' => $count + 1]);
            return true;
        });

        if (!$allowed) {
            return response()->json([
                'ok' => false,
                'error' => 'Demasiados intentos. Espera 60 segundos e intenta de nuevo.',
            ], 429);
        }

        return $next($request);
    }
}

