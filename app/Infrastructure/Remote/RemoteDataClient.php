<?php

namespace App\Infrastructure\Remote;

use App\Infrastructure\Http\RemoteApiClient;

final class RemoteDataClient
{
    public function __construct(
        private readonly RemoteApiClient $api,
    ) {
    }

    /** @return array{kpis?:array<string,mixed>,series?:array<string,mixed>,error?:string} */
    public function dashboard(): array
    {
        $res = $this->api->request()->get('/api/v1/dashboard');
        return $res->successful()
            ? (array) $res->json('data', [])
            : ['error' => (string) ($res->json('error') ?: 'No se pudo cargar dashboard.')];
    }

    /** @return array<int,mixed> */
    public function clientes(int $limit = 50, ?string $q = null): array
    {
        $params = ['limit' => $limit];
        if (is_string($q) && trim($q) !== '') {
            $params['q'] = trim($q);
        }
        $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $res = $this->api->request()->get('/api/v1/clientes?' . $query);
        return $res->successful() ? (array) $res->json('data', []) : [];
    }

    /** @return array<int,mixed> */
    public function roles(): array
    {
        $res = $this->api->request()->get('/api/v1/roles');
        return $res->successful() ? (array) $res->json('data', []) : [];
    }

    /** @param array{codigo:string,nombre:string,protegido?:bool} $payload */
    public function crearRol(array $payload): array
    {
        $res = $this->api->request()->post('/api/v1/roles', $payload);
        return $res->successful() ? (array) $res->json('data', []) : ['error' => $res->json('error')];
    }

    /** @param array{codigo:string,nombre:string} $payload */
    public function actualizarRol(int $id, array $payload): array
    {
        $res = $this->api->request()->put("/api/v1/roles/{$id}", $payload);
        return $res->successful() ? (array) $res->json('data', []) : ['error' => $res->json('error')];
    }

    public function eliminarRol(int $id): bool
    {
        $res = $this->api->request()->delete("/api/v1/roles/{$id}");
        return $res->successful();
    }

    /** @return array<string,mixed> */
    public function cliente(int $id): array
    {
        $res = $this->api->request()->get("/api/v1/clientes/{$id}");
        return $res->successful() ? (array) $res->json('data', []) : [];
    }

    /** @param array<string,mixed> $payload */
    public function crearCliente(array $payload): array
    {
        $res = $this->api->request()->post('/api/v1/clientes', $payload);
        return $res->successful() ? (array) $res->json('data', []) : ['error' => $res->json('error')];
    }

    /** @param array<string,mixed> $payload */
    public function actualizarCliente(int $id, array $payload): array
    {
        $res = $this->api->request()->put("/api/v1/clientes/{$id}", $payload);
        return $res->successful() ? (array) $res->json('data', []) : ['error' => $res->json('error')];
    }

    public function eliminarCliente(int $id): bool
    {
        $res = $this->api->request()->delete("/api/v1/clientes/{$id}");
        return $res->successful();
    }

    /** @return array<int,mixed> */
    public function tickets(int $limit = 50, ?string $q = null): array
    {
        $params = ['limit' => $limit];
        if (is_string($q) && trim($q) !== '') {
            $params['q'] = $q;
        }
        $res = $this->api->request()->get('/api/v1/tickets', $params);
        return $res->successful() ? (array) $res->json('data', []) : [];
    }

    /** @return array<string,mixed> */
    public function ticket(string $ticketId): array
    {
        $res = $this->api->request()->get("/api/v1/tickets/{$ticketId}");
        return $res->successful() ? (array) $res->json('data', []) : ['error' => $res->json('error')];
    }

    public function asignarTicket(string $ticketId, int $tecnicoId, ?string $comentario = null): bool
    {
        $res = $this->api->request()->post("/api/v1/tickets/{$ticketId}/asignar", [
            'tecnico_id' => $tecnicoId,
            'comentario' => $comentario,
        ]);
        return $res->successful();
    }

    public function cerrarTicket(string $ticketId): bool
    {
        $res = $this->api->request()->post("/api/v1/tickets/{$ticketId}/cerrar");
        return $res->successful();
    }

    /** @return array<int,mixed> */
    public function ventas(int $limit = 50, ?string $q = null): array
    {
        $params = ['limit' => $limit];
        if (is_string($q) && trim($q) !== '') {
            $params['q'] = $q;
        }
        $res = $this->api->request()->get('/api/v1/ventas', $params);
        return $res->successful() ? (array) $res->json('data', []) : [];
    }

    /** @return array<int,mixed> */
    public function ventasReport(string $from, string $to, int $limit = 200): array
    {
        $q = ['limit' => max(1, min(200, $limit))];
        if ($from !== '') $q['from'] = $from;
        if ($to !== '') $q['to'] = $to;
        $res = $this->api->request()->get('/api/v1/ventas', $q);
        return $res->successful() ? (array) $res->json('data', []) : [];
    }

    /** @return array<string,mixed> */
    public function venta(int $id): array
    {
        $res = $this->api->request()->get("/api/v1/ventas/{$id}");
        return $res->successful() ? (array) $res->json('data', []) : ['error' => $res->json('error')];
    }

    /** @return array<int,mixed> */
    public function ventasStats(?string $from = null, ?string $to = null): array
    {
        $q = array_filter(['from' => $from, 'to' => $to]);
        $res = $this->api->request()->get('/api/v1/ventas/stats', $q);
        return $res->successful() ? (array) $res->json('data', []) : [];
    }

    /** @return array<string,mixed> */
    public function ventasNextCorrelativo(?int $sucursalId = null, ?string $tipoDocumento = null, ?string $serie = null): array
    {
        $q = array_filter([
            'sucursal_id' => $sucursalId,
            'tipo_documento' => $tipoDocumento,
            'serie' => $serie,
        ], fn ($v) => $v !== null && $v !== '');
        $res = $this->api->request()->get('/api/v1/ventas/next-correlativo', $q);
        return $res->successful() ? (array) $res->json('data', []) : ['error' => $res->json('error')];
    }

    /** @param array<string,mixed> $payload */
    public function crearVenta(array $payload): array
    {
        $res = $this->api->request()->post('/api/v1/ventas', $payload);
        if ($res->successful()) {
            return (array) $res->json('data', []);
        }

        $error = $res->json('error');
        if (!is_string($error) || $error === '') {
            $error = $res->json('message');
        }
        if (!is_string($error) || $error === '') {
            $body = (string) $res->body();
            $body = trim($body);
            if ($body !== '' && strlen($body) > 300) {
                $body = substr($body, 0, 300) . '…';
            }
            $error = $body !== '' ? ('HTTP ' . $res->status() . ': ' . $body) : ('HTTP ' . $res->status());
        }

        return ['error' => $error];
    }

    /** @param array<string,mixed> $payload */
    public function pagarVenta(int $id, array $payload = []): array
    {
        $res = $this->api->request()->post("/api/v1/ventas/{$id}/pagar", $payload);
        return $res->successful() ? (array) $res->json('data', []) : ['error' => $res->json('error')];
    }

    /** @param array<string,mixed> $payload */
    public function anularVenta(int $id, array $payload = []): array
    {
        $res = $this->api->request()->post("/api/v1/ventas/{$id}/anular", $payload);
        return $res->successful() ? (array) $res->json('data', []) : ['error' => $res->json('error')];
    }

    /** @return array<int,mixed> */
    public function stock(): array
    {
        $res = $this->api->request()->get('/api/v1/stock');
        return $res->successful() ? (array) $res->json('data', []) : [];
    }

    /** @return array<int,mixed> */
    public function productos(int $limit = 50, ?string $q = null): array
    {
        $params = ['limit' => $limit];
        if (is_string($q) && trim($q) !== '') {
            $params['q'] = $q;
        }
        $res = $this->api->request()->get('/api/v1/productos', $params);
        return $res->successful() ? (array) $res->json('data', []) : [];
    }

    /** @return array<string,mixed> */
    public function producto(int $id): array
    {
        $res = $this->api->request()->get("/api/v1/productos/{$id}");
        return $res->successful() ? (array) $res->json('data', []) : ['error' => $res->json('error')];
    }

    /** @return array<int,mixed> */
    /** @param array<int,int> $productoIds */
    public function productosStockData(int $limit = 500, array $productoIds = []): array
    {
        $q = ['limit' => $limit];
        if (count($productoIds) > 0) {
            $productoIds = array_values(array_unique(array_filter(array_map('intval', $productoIds), fn ($v) => $v > 0)));
            if (count($productoIds) > 0) {
                $q['producto_ids'] = implode(',', array_slice($productoIds, 0, 800));
            }
        }
        $res = $this->api->request()->get('/api/v1/productos/stock-data', $q);
        return $res->successful() ? (array) $res->json('data', []) : [];
    }

    /** @return array<int,mixed> */
    public function productosKardexData(int $limit = 500, ?string $from = null, ?string $to = null): array
    {
        $q = array_filter([
            'limit' => $limit,
            'from' => $from,
            'to' => $to,
        ], fn ($v) => $v !== null && $v !== '');
        $res = $this->api->request()->get('/api/v1/productos/kardex-data', $q);
        return $res->successful() ? (array) $res->json('data', []) : [];
    }

    /** @param array<string,mixed> $payload */
    public function movimientoStock(array $payload): bool
    {
        $res = $this->api->request()->post('/api/v1/productos/movimiento-stock', $payload);
        return $res->successful();
    }

    /** @param array<string,mixed> $payload */
    public function crearProducto(array $payload): array
    {
        $res = $this->api->request()->post('/api/v1/productos', $payload);
        return $res->successful() ? (array) $res->json('data', []) : ['error' => $res->json('error')];
    }

    /** @param array<string,mixed> $payload */
    public function actualizarProducto(int $id, array $payload): array
    {
        $res = $this->api->request()->put("/api/v1/productos/{$id}", $payload);
        return $res->successful() ? (array) $res->json('data', []) : ['error' => $res->json('error')];
    }

    public function eliminarProducto(int $id): bool
    {
        $res = $this->api->request()->delete("/api/v1/productos/{$id}");
        return $res->successful();
    }

    /** @return array<string,mixed> */
    public function importProductos(string $filename, string $contents): array
    {
        $res = $this->api->request()
            ->attach('file', $contents, $filename)
            ->post('/api/v1/productos/import');

        return $res->successful() ? (array) $res->json('data', []) : ['error' => $res->json('error')];
    }

    /** @return array<int,mixed> */
    public function comisiones(int $limit = 50, ?string $periodo = null, ?int $vendedorId = null): array
    {
        $q = ['limit' => $limit];
        if (is_string($periodo) && $periodo !== '') {
            $q['periodo'] = $periodo;
        }
        if ($vendedorId) {
            $q['vendedor_id'] = $vendedorId;
        }
        $res = $this->api->request()->get('/api/v1/comisiones', $q);
        return $res->successful() ? (array) $res->json('data', []) : [];
    }

    /** @return array<string,mixed> */
    public function comisionesVentasPeriodo(string $periodo, ?float $porcentaje = null, ?int $vendedorId = null, ?float $instaladorFee = null, ?string $ventaIds = null): array
    {
        $q = ['periodo' => $periodo];
        if ($porcentaje !== null && $porcentaje > 0) {
            $q['porcentaje'] = $porcentaje;
        }
        if ($instaladorFee !== null && $instaladorFee >= 0) {
            $q['instalador_fee'] = $instaladorFee;
        }
        if (is_string($ventaIds) && trim($ventaIds) !== '') {
            $q['venta_ids'] = trim($ventaIds);
        }
        if ($vendedorId) {
            $q['vendedor_id'] = $vendedorId;
        }
        $res = $this->api->request()->get('/api/v1/comisiones/ventas-periodo', $q);
        return $res->successful() ? (array) $res->json('data', []) : ['error' => $res->json('error')];
    }

    public function comisionAprobar(int $id): bool
    {
        $res = $this->api->request()->post("/api/v1/comisiones/{$id}/aprobar");
        return $res->successful();
    }

    /** @return array<string,mixed> */
    public function comisionesAprobarBulk(string $periodo, ?int $vendedorId = null, ?string $ventaIds = null): array
    {
        $payload = ['periodo' => $periodo];
        if ($vendedorId) {
            $payload['vendedor_id'] = $vendedorId;
        }
        if (is_string($ventaIds) && trim($ventaIds) !== '') {
            $payload['venta_ids'] = trim($ventaIds);
        }
        $res = $this->api->request()->post('/api/v1/comisiones/aprobar', $payload);
        return $res->successful() ? (array) $res->json('data', []) : ['error' => $res->json('error')];
    }

    /** @return array<string,mixed> */
    public function comisionesLiquidar(string $periodo, ?int $vendedorId = null, ?float $porcentaje = null, ?float $instaladorFee = null, ?string $referencia = null, ?string $ventaIds = null, ?array $fees = null): array
    {
        $payload = ['periodo' => $periodo];
        if ($vendedorId) {
            $payload['vendedor_id'] = $vendedorId;
        }
        if ($porcentaje !== null && $porcentaje >= 0) {
            $payload['porcentaje'] = $porcentaje;
        }
        if ($instaladorFee !== null && $instaladorFee >= 0) {
            $payload['instalador_fee'] = $instaladorFee;
        }
        if ($referencia !== null && $referencia !== '') {
            $payload['referencia'] = $referencia;
        }
        if (is_string($ventaIds) && trim($ventaIds) !== '') {
            $payload['venta_ids'] = trim($ventaIds);
        }
        if (is_array($fees) && $fees) {
            $payload['fees'] = $fees;
        }
        $res = $this->api->request()->post('/api/v1/comisiones/liquidar', $payload);
        return $res->successful() ? (array) $res->json('data', []) : ['error' => $res->json('error')];
    }

    /** @return array<string,mixed> */
    public function comisionesPagar(string $periodo, ?int $vendedorId = null, ?string $referencia = null, ?string $ventaIds = null): array
    {
        $payload = ['periodo' => $periodo];
        if ($vendedorId) {
            $payload['vendedor_id'] = $vendedorId;
        }
        if ($referencia !== null && $referencia !== '') {
            $payload['referencia'] = $referencia;
        }
        if (is_string($ventaIds) && trim($ventaIds) !== '') {
            $payload['venta_ids'] = trim($ventaIds);
        }
        $res = $this->api->request()->post('/api/v1/comisiones/pagar', $payload);
        return $res->successful() ? (array) $res->json('data', []) : ['error' => $res->json('error')];
    }

    /** @return array{csv?:string,error?:string} */
    public function comisionesExportCsv(?string $periodo = null): array
    {
        $q = [];
        if (is_string($periodo) && $periodo !== '') {
            $q['periodo'] = $periodo;
        }
        $res = $this->api->request()->get('/api/v1/comisiones/export', $q);
        return $res->successful()
            ? ['csv' => (string) $res->body()]
            : ['error' => (string) ($res->json('error') ?: 'No se pudo exportar.')];
    }

    /** @return array<int,mixed> */
    public function sucursales(int $limit = 50): array
    {
        $res = $this->api->request()->get('/api/v1/sucursales', ['limit' => $limit]);
        return $res->successful() ? (array) $res->json('data', []) : [];
    }

    /** @return array<int,mixed> */
    public function auditoria(int $limit = 50): array
    {
        $res = $this->api->request()->get('/api/v1/auditoria', ['limit' => $limit]);
        return $res->successful() ? (array) $res->json('data', []) : [];
    }

    /** @return array<int,mixed> */
    public function usuarios(int $limit = 50, ?string $q = null, array $filters = []): array
    {
        $params = ['limit' => $limit];
        if (is_string($q) && trim($q) !== '') {
            $params['q'] = $q;
        }
        foreach (['dashboard_activo', 'role_codes'] as $k) {
            if (array_key_exists($k, $filters) && $filters[$k] !== null && $filters[$k] !== '') {
                $params[$k] = $filters[$k];
            }
        }
        $res = $this->api->request()->get('/api/v1/usuarios', $params);
        return $res->successful() ? (array) $res->json('data', []) : [];
    }

    /** @return array<int,mixed> */
    public function soporteVideos(int $limit = 50): array
    {
        $res = $this->api->request()->get('/api/v1/soporte-videos', ['limit' => $limit]);
        return $res->successful() ? (array) $res->json('data', []) : [];
    }

    /** @return array<string,mixed> */
    public function soporteVideo(int $id): array
    {
        $res = $this->api->request()->get("/api/v1/soporte-videos/{$id}");
        return $res->successful() ? (array) $res->json('data', []) : ['error' => $res->json('error')];
    }

    /** @return array<int,mixed> */
    public function agenda(int $limit = 100, array $params = []): array
    {
        $q = array_filter(['limit' => $limit, ...$params], fn ($v) => $v !== null && $v !== '');
        $res = $this->api->request()->get('/api/v1/agenda', $q);
        return $res->successful() ? (array) $res->json('data', []) : [];
    }

    /** @return array<string,mixed> */
    public function agendaItem(int $id): array
    {
        $res = $this->api->request()->get("/api/v1/agenda/{$id}");
        return $res->successful() ? (array) $res->json('data', []) : ['error' => $this->apiError($res, 'No se pudo obtener la agenda.')];
    }

    /** @param array<string,mixed> $payload */
    public function agendaCrear(array $payload): array
    {
        $res = $this->api->request()->post('/api/v1/agenda', $payload);
        return $res->successful() ? (array) $res->json('data', []) : ['error' => $this->apiError($res, 'No se pudo crear la agenda.')];
    }

    /** @param array<string,mixed> $payload */
    public function agendaActualizar(int $id, array $payload): array
    {
        $res = $this->api->request()->put("/api/v1/agenda/{$id}", $payload);
        return $res->successful() ? (array) $res->json('data', []) : ['error' => $this->apiError($res, 'No se pudo actualizar la agenda.')];
    }

    public function agendaEliminar(int $id): bool
    {
        $res = $this->api->request()->delete("/api/v1/agenda/{$id}");
        return $res->successful();
    }

    /** @return array<string,mixed> */
    public function permisosMatrix(): array
    {
        $res = $this->api->request()->get('/api/v1/permisos/matrix');
        return $res->successful() ? (array) $res->json('data', []) : ['error' => $res->json('error')];
    }

    /** @param array<int,array{rol_id:int,modulo_id:int,accion_id:int,permitido:bool}> $changes */
    public function permisosUpdate(array $changes): bool
    {
        $res = $this->api->request()->post('/api/v1/permisos', [
            'changes' => $changes,
        ]);
        return $res->successful();
    }

    /** @return array<int,mixed> */
    public function dispositivos(int $limit = 50): array
    {
        $res = $this->api->request()->get('/api/v1/dispositivos', ['limit' => $limit]);
        return $res->successful() ? (array) $res->json('data', []) : [];
    }

    /** @return array<string,mixed> */
    public function dispositivo(int $id): array
    {
        $res = $this->api->request()->get("/api/v1/dispositivos/{$id}");
        return $res->successful() ? (array) $res->json('data', []) : ['error' => $res->json('error')];
    }

    /** @param array<string,mixed> $payload */
    public function crearDispositivo(array $payload): array
    {
        $res = $this->api->request()->post('/api/v1/dispositivos', $payload);
        return $res->successful() ? (array) $res->json('data', []) : ['error' => $this->apiError($res, 'No se pudo registrar la evidencia de instalación.')];
    }

    /** @return array<int,mixed> */
    public function sucursalesAll(int $limit = 200): array
    {
        $res = $this->api->request()->get('/api/v1/sucursales', ['limit' => $limit]);
        return $res->successful() ? (array) $res->json('data', []) : [];
    }

    /** @param array<string,mixed> $payload */
    public function crearSucursal(array $payload): array
    {
        $res = $this->api->request()->post('/api/v1/sucursales', $payload);
        return $res->successful() ? (array) $res->json('data', []) : ['error' => $res->json('error')];
    }

    /** @param array<string,mixed> $payload */
    public function actualizarSucursal(int $id, array $payload): array
    {
        $res = $this->api->request()->put("/api/v1/sucursales/{$id}", $payload);
        return $res->successful() ? (array) $res->json('data', []) : ['error' => $res->json('error')];
    }

    public function eliminarSucursal(int $id): bool
    {
        $res = $this->api->request()->delete("/api/v1/sucursales/{$id}");
        return $res->successful();
    }

    /** @return array<int,mixed> */
    public function usuariosAll(int $limit = 200): array
    {
        $res = $this->api->request()->get('/api/v1/usuarios', ['limit' => $limit]);
        return $res->successful() ? (array) $res->json('data', []) : [];
    }

    /** @param array<string,mixed> $payload */
    public function crearUsuario(array $payload): array
    {
        $res = $this->api->request()->post('/api/v1/usuarios', $payload);
        return $res->successful() ? (array) $res->json() : ['error' => $res->json('error')];
    }

    /** @param array<string,mixed> $payload */
    public function actualizarUsuario(int $id, array $payload): array
    {
        $res = $this->api->request()->put("/api/v1/usuarios/{$id}", $payload);
        return $res->successful() ? (array) $res->json('data', []) : ['error' => $res->json('error')];
    }

    public function eliminarUsuario(int $id): bool
    {
        $res = $this->api->request()->delete("/api/v1/usuarios/{$id}");
        return $res->successful();
    }

    /** @return array<int,mixed> */
    public function usuarioPermisos(int $id): array
    {
        $res = $this->api->request()->get("/api/v1/usuarios/{$id}/permisos");
        return $res->successful() ? (array) $res->json('data', []) : [];
    }

    /** @param array<int,array{modulo_id:int,accion_id:int,permitido:bool}> $changes */
    public function usuarioPermisosUpdate(int $id, array $changes): bool
    {
        $res = $this->api->request()->post("/api/v1/usuarios/{$id}/permisos", [
            'changes' => $changes,
        ]);
        return $res->successful();
    }

    private function apiError(mixed $res, string $fallback): string
    {
        $error = $res->json('error');
        if (is_string($error) && trim($error) !== '') {
            return trim($error);
        }

        $message = $res->json('message');
        if (is_string($message) && trim($message) !== '') {
            return trim($message);
        }

        $errors = $res->json('errors');
        if (is_array($errors)) {
            $first = collect($errors)->flatten()->first();
            if (is_string($first) && trim($first) !== '') {
                return trim($first);
            }
        }

        return $fallback;
    }
}
