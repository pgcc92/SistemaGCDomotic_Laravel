<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class BrandingController
{
    private function tenantId(Request $request): string
    {
        $t = (string) $request->header('X-Tenant', $request->header('X-TENANT', ''));
        $t = trim($t);
        if ($t === '') {
            $t = (string) config('gc.tenant.default', 'default');
        }
        $t = preg_replace('/[^a-zA-Z0-9_-]/', '', $t) ?: 'default';
        return $t;
    }

    public function show(Request $request): JsonResponse
    {
        $tenant = $this->tenantId($request);
        $key = "branding:{$tenant}";

        $row = DB::table('app_config')->where('clave', $key)->first();
        $val = $row?->valor;
        if (is_string($val)) {
            $decoded = json_decode($val, true);
            $val = is_array($decoded) ? $decoded : null;
        } elseif (is_object($val)) {
            $val = (array) $val;
        }

        $defaults = [
            'system_name' => 'GC Dashboard',
            'sidebar_name' => 'GC Dashboard',
            'logo_url' => null,
            'login_logo_url' => null,
            'favicon_url' => null,
            'colors' => [
                'primary' => '79 70 229',
                'secondary' => '14 165 233',
            ],
            'font_family' => 'Figtree',
            'dark_mode_enabled' => true,
            'login_gradient' => [
                'from' => '16 185 129',
                'to' => '2 6 23',
            ],
        ];

        $out = $defaults;
        if (is_array($val)) {
            $out = array_replace_recursive($out, $val);
        }

        return response()->json([
            'ok' => true,
            'tenant' => $tenant,
            ...$out,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $tenant = $this->tenantId($request);
        $key = "branding:{$tenant}";

        $data = $request->validate([
            'system_name' => ['required', 'string', 'max:120'],
            'sidebar_name' => ['nullable', 'string', 'max:120'],
            'logo_url' => ['nullable', 'string', 'max:500'],
            'login_logo_url' => ['nullable', 'string', 'max:500'],
            'favicon_url' => ['nullable', 'string', 'max:500'],
            'colors.primary' => ['required', 'regex:/^\\d{1,3}\\s\\d{1,3}\\s\\d{1,3}$/'],
            'colors.secondary' => ['required', 'regex:/^\\d{1,3}\\s\\d{1,3}\\s\\d{1,3}$/'],
            'font_family' => ['nullable', 'string', 'max:80'],
            'dark_mode_enabled' => ['nullable', 'boolean'],
            'login_gradient.from' => ['required', 'regex:/^\\d{1,3}\\s\\d{1,3}\\s\\d{1,3}$/'],
            'login_gradient.to' => ['required', 'regex:/^\\d{1,3}\\s\\d{1,3}\\s\\d{1,3}$/'],
        ]);

        $payload = [
            'system_name' => (string) $data['system_name'],
            'sidebar_name' => $data['sidebar_name'] ?? null,
            'logo_url' => $data['logo_url'] ?? null,
            'login_logo_url' => $data['login_logo_url'] ?? null,
            'favicon_url' => $data['favicon_url'] ?? null,
            'colors' => [
                'primary' => (string) data_get($data, 'colors.primary'),
                'secondary' => (string) data_get($data, 'colors.secondary'),
            ],
            'font_family' => $data['font_family'] ?? null,
            'dark_mode_enabled' => (bool) ($data['dark_mode_enabled'] ?? false),
            'login_gradient' => [
                'from' => (string) data_get($data, 'login_gradient.from'),
                'to' => (string) data_get($data, 'login_gradient.to'),
            ],
        ];

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        DB::statement(
            "insert into app_config (clave, valor, updated_at) values (?, ?::jsonb, now())
             on conflict (clave) do update set valor = excluded.valor, updated_at = excluded.updated_at",
            [$key, $json]
        );

        return response()->json([
            'ok' => true,
        ]);
    }
}
