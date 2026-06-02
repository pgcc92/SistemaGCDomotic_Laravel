<?php

namespace App\Http\Controllers;

use App\Infrastructure\Remote\RemoteDataClient;
use Illuminate\Http\JsonResponse;

final class ModuleDataController
{
    public function __construct(
        private readonly RemoteDataClient $data,
    ) {
    }

    public function __invoke(string $module = ''): JsonResponse
    {
        if ($module === '') {
            $module = (string) request()?->route('module', 'modulo');
        }

        $limit = max(1, min(500, (int) request()->query('limit', 200)));
        $q = trim((string) request()->query('q', ''));
        $q = $q !== '' ? $q : null;
        $isTypeahead = request()->boolean('typeahead');

        $rows = match ($module) {
            'clientes' => $this->clientes($limit, $q, $isTypeahead),
            'tickets' => $this->data->tickets($limit, $q),
            'ventas' => $this->data->ventas($limit, $q),
            'productos' => ($q ? $this->data->productos($limit, $q) : $this->data->stock()),
            'comisiones' => $this->data->comisiones($limit),
            'sucursales' => $this->data->sucursales($limit),
            'auditoria' => $this->data->auditoria($limit),
            'usuarios' => $this->data->usuarios($limit, $q, [
                'dashboard_activo' => request()->query('dashboard_activo'),
                'role_codes' => request()->query('role_codes'),
            ]),
            'soporte_videos' => $this->data->soporteVideos($limit),
            default => [],
        };

        return response()->json([
            'ok' => true,
            'data' => $rows,
        ]);
    }

    /** @return array<int,mixed> */
    private function clientes(int $limit, ?string $q, bool $isTypeahead): array
    {
        if ($isTypeahead && $q === null) {
            return [];
        }

        $rows = $this->data->clientes($limit, $q);
        if ($q === null) {
            return $rows;
        }

        $terms = preg_split('/\s+/u', mb_strtolower($q), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter($rows, static function (mixed $row) use ($terms): bool {
            $values = is_array($row) ? $row : (array) $row;
            $haystack = mb_strtolower(implode(' ', array_map(
                static fn (mixed $value): string => is_scalar($value) ? (string) $value : '',
                $values,
            )));

            foreach ($terms as $term) {
                if (!str_contains($haystack, $term)) {
                    return false;
                }
            }

            return true;
        }));
    }
}
