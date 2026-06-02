<?php

namespace App\Http\Controllers;

use App\Infrastructure\Remote\RemoteDataClient;
use App\Services\UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class ProductosController
{
    public function __construct(
        private readonly RemoteDataClient $data,
        private readonly UploadService $upload,
    ) {
    }

    public function index(): View
    {
        return view('productos.index');
    }

    public function show(int $id): View|JsonResponse
    {
        $res = $this->data->producto($id);

        if (request()->expectsJson()) {
            return response()->json([
                'ok' => !isset($res['error']),
                'data' => $res,
            ]);
        }

        return view('productos.show', [
            'id' => $id,
            'producto' => (array) ($res['producto'] ?? []),
            'stock' => (array) ($res['stock'] ?? []),
            'kardex' => (array) ($res['kardex'] ?? []),
            'error' => $res['error'] ?? null,
        ]);
    }

    public function create(): View
    {
        return view('productos.create');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $payload = $request->validate([
            'sku' => ['required', 'string', 'max:50'],
            'nombre' => ['required', 'string', 'max:200'],
            'precio' => ['nullable', 'numeric'],
            'costo' => ['nullable', 'numeric'],
            'moneda' => ['nullable', 'string', 'max:3'],
            'categoria' => ['nullable', 'string', 'max:100'],
            'modelo' => ['nullable', 'string', 'max:100'],
            'imagen_url' => ['nullable', 'string'],
            'imagen_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'remove_imagen' => ['nullable', 'boolean'],
            'descripcion' => ['nullable', 'string'],
            'stock_inicial_json' => ['nullable', 'string'],
        ]);

        $imagenUrl = $payload['imagen_url'] ?? null;
        if (!empty($payload['remove_imagen'])) {
            $imagenUrl = null;
        } elseif ($request->hasFile('imagen_file')) {
            $file = $request->file('imagen_file');
            if ($file) {
                try {
                    $saved = $this->upload->saveImage($file, 'productos');
                    $imagenUrl = $saved['url'];
                } catch (\Throwable $e) {
                    $msg = $e->getMessage() ?: 'No se pudo procesar la imagen.';
                    if ($request->expectsJson()) {
                        return response()->json(['ok' => false, 'error' => $msg], 422);
                    }
                    return back()->withErrors(['imagen_file' => $msg])->withInput();
                }
            }
        }

        $stockInicial = [];
        if (!empty($payload['stock_inicial_json'])) {
            $decoded = json_decode((string) $payload['stock_inicial_json'], true);
            if (!is_array($decoded)) {
                return back()->withErrors(['stock_inicial_json' => 'stock_inicial_json debe ser un JSON array.'])->withInput();
            }
            $stockInicial = $decoded;
        }

        $res = $this->data->crearProducto([
            'sku' => Str::upper(trim((string) $payload['sku'])),
            'nombre' => $payload['nombre'],
            'precio' => $payload['precio'] ?? 0,
            'costo' => $payload['costo'] ?? 0,
            'moneda' => $payload['moneda'] ?? 'PEN',
            'categoria' => $payload['categoria'] ?? null,
            'modelo' => $payload['modelo'] ?? null,
            'imagen_url' => $imagenUrl,
            'descripcion' => $payload['descripcion'] ?? null,
            'stock_inicial' => $stockInicial ?: null,
        ]);

        $id = (int) ($res['id'] ?? 0);
        if ($id <= 0) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'error' => $res['error'] ?? 'No se pudo crear el producto.'], 422);
            }
            return back()->withErrors(['sku' => $res['error'] ?? 'No se pudo crear el producto.'])->withInput();
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'data' => $res]);
        }

        return redirect()->route('productos.show', ['id' => $id])->with('status', 'Producto creado.');
    }

    public function edit(int $id): View
    {
        $res = $this->data->producto($id);

        return view('productos.edit', [
            'id' => $id,
            'producto' => (array) ($res['producto'] ?? []),
            'stock' => (array) ($res['stock'] ?? []),
            'error' => $res['error'] ?? null,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $payload = $request->validate([
            'sku' => ['nullable', 'string', 'max:50'],
            'nombre' => ['nullable', 'string', 'max:200'],
            'precio' => ['nullable', 'numeric'],
            'costo' => ['nullable', 'numeric'],
            'moneda' => ['nullable', 'string', 'max:3'],
            'categoria' => ['nullable', 'string', 'max:100'],
            'modelo' => ['nullable', 'string', 'max:100'],
            'imagen_url' => ['nullable', 'string'],
            'imagen_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'remove_imagen' => ['nullable', 'boolean'],
            'descripcion' => ['nullable', 'string'],
            'activo' => ['nullable', 'boolean'],
            'stock_meta_json' => ['nullable', 'string'],
        ]);

        $imagenUrl = $payload['imagen_url'] ?? null;
        if (!empty($payload['remove_imagen'])) {
            $imagenUrl = null;
        } elseif ($request->hasFile('imagen_file')) {
            $file = $request->file('imagen_file');
            if ($file) {
                try {
                    $saved = $this->upload->saveImage($file, 'productos');
                    $imagenUrl = $saved['url'];
                } catch (\Throwable $e) {
                    $msg = $e->getMessage() ?: 'No se pudo procesar la imagen.';
                    if ($request->expectsJson()) {
                        return response()->json(['ok' => false, 'error' => $msg], 422);
                    }
                    return back()->withErrors(['imagen_file' => $msg])->withInput();
                }
            }
        }

        $stockMeta = null;
        if (!empty($payload['stock_meta_json'])) {
            $decoded = json_decode((string) $payload['stock_meta_json'], true);
            if (!is_array($decoded)) {
                return back()->withErrors(['stock_meta_json' => 'stock_meta_json debe ser un JSON array.'])->withInput();
            }
            $stockMeta = $decoded;
        }

        $apiPayload = [];
        foreach (['sku', 'nombre', 'precio', 'costo', 'moneda', 'categoria', 'modelo', 'imagen_url', 'descripcion', 'activo'] as $k) {
            if (array_key_exists($k, $payload) && $payload[$k] !== null && $payload[$k] !== '') {
                $apiPayload[$k] = $k === 'sku' ? Str::upper(trim((string) $payload[$k])) : $payload[$k];
            }
        }
        if (array_key_exists('remove_imagen', $payload) && $payload['remove_imagen']) {
            $apiPayload['imagen_url'] = null;
        } elseif ($request->hasFile('imagen_file')) {
            $apiPayload['imagen_url'] = $imagenUrl;
        } elseif (array_key_exists('imagen_url', $payload)) {
            // si vino en el request, puede ser URL externa o vacío => null
            $apiPayload['imagen_url'] = ($imagenUrl === '' ? null : $imagenUrl);
        }
        if ($stockMeta !== null) {
            $apiPayload['stock_meta'] = $stockMeta;
        }

        $res = $this->data->actualizarProducto($id, $apiPayload);
        if (isset($res['error'])) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'error' => $res['error']], 422);
            }
            return back()->withErrors(['sku' => $res['error']])->withInput();
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'data' => $res]);
        }

        return redirect()->route('productos.show', ['id' => $id])->with('status', 'Producto actualizado.');
    }

    public function destroy(int $id): RedirectResponse|JsonResponse
    {
        $ok = $this->data->eliminarProducto($id);
        if (request()->expectsJson()) {
            return $ok
                ? response()->json(['ok' => true])
                : response()->json(['ok' => false, 'error' => 'No se pudo eliminar.'], 422);
        }
        return $ok
            ? redirect()->route('productos.index')->with('status', 'Producto eliminado.')
            : back()->withErrors(['producto' => 'No se pudo eliminar.']);
    }

    public function stockData(): RedirectResponse
    {
        return redirect()->route('productos.index');
    }

    public function stockDataJson(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => $this->data->productosStockData(2000),
        ]);
    }

    public function kardexData(): RedirectResponse
    {
        return redirect()->route('productos.index');
    }

    public function kardexDataJson(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => $this->data->productosKardexData(2000),
        ]);
    }

    public function movimientoForm(): View
    {
        return view('productos.movimiento');
    }

    public function movimiento(Request $request): RedirectResponse|JsonResponse
    {
        $payload = $request->validate([
            'producto_id' => ['required', 'integer', 'min:1'],
            'tipo' => ['required', 'string', 'max:20'],
            'cantidad' => ['required', 'integer', 'min:1'],
            'motivo' => ['nullable', 'string', 'max:150'],
            'sucursal_origen' => ['nullable', 'integer', 'min:1'],
            'sucursal_destino' => ['nullable', 'integer', 'min:1'],
        ]);

        $tipo = Str::upper((string) $payload['tipo']);
        if ($tipo === 'TRANSFER') {
            $o = (int) ($payload['sucursal_origen'] ?? 0);
            $d = (int) ($payload['sucursal_destino'] ?? 0);
            if ($o > 0 && $d > 0 && $o === $d) {
                return $request->expectsJson()
                    ? response()->json(['ok' => false, 'error' => 'La sucursal origen y destino no pueden ser la misma.'], 422)
                    : back()->withErrors(['sucursal_destino' => 'La sucursal origen y destino no pueden ser la misma.'])->withInput();
            }
        }

        $ok = $this->data->movimientoStock([
            'producto_id' => (int) $payload['producto_id'],
            'tipo' => $tipo,
            'cantidad' => (int) $payload['cantidad'],
            'motivo' => $payload['motivo'] ?? null,
            'sucursal_origen' => $payload['sucursal_origen'] !== null ? (int) $payload['sucursal_origen'] : null,
            'sucursal_destino' => $payload['sucursal_destino'] !== null ? (int) $payload['sucursal_destino'] : null,
        ]);

        if ($request->expectsJson()) {
            return $ok
                ? response()->json(['ok' => true])
                : response()->json(['ok' => false, 'error' => 'No se pudo registrar el movimiento.'], 422);
        }

        return $ok
            ? back()->with('status', 'Movimiento registrado.')
            : back()->withErrors(['producto_id' => 'No se pudo registrar el movimiento.']);
    }

    public function importForm(): View
    {
        return view('productos.import');
    }

    public function import(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $file = $request->file('file');
        if (!$file) {
            return back()->withErrors(['file' => 'Archivo inválido.']);
        }

        $contents = file_get_contents($file->getRealPath());
        if ($contents === false || trim($contents) === '') {
            return back()->withErrors(['file' => 'Archivo vacío.']);
        }

        $res = $this->data->importProductos($file->getClientOriginalName() ?: 'productos.csv', $contents);
        if (isset($res['error'])) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'error' => $res['error']], 422);
            }
            return back()->withErrors(['file' => $res['error']]);
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'data' => $res]);
        }

        return redirect()->route('productos.index')->with('status', 'Importación completada.');
    }
}
