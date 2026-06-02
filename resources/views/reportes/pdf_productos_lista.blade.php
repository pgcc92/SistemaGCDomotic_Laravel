@php
    $productos = $productos ?? [];
    $stock = $stock ?? [];
    $filters = $filters ?? [];
    $stockMap = [];
    $val = static function ($row, array $keys, $default = null) {
        foreach ($keys as $key) {
            if (is_array($row) && array_key_exists($key, $row)) return $row[$key];
            if (is_object($row) && property_exists($row, $key)) return $row->{$key};
        }
        return $default;
    };

    foreach (($stock ?: []) as $r) {
        $pid = (int) $val($r, ['producto_id', 'id'], 0);
        $st = (float) $val($r, ['stock_total', 'stock', 'stock_actual', 'cantidad', 'saldo'], 0);
        if ($pid > 0) {
            $stockMap[$pid] = ($stockMap[$pid] ?? 0) + $st;
        }
    }
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Productos</title>
    @include('reportes.partials.pdf_base_styles')
</head>
<body>
    <div class="header">
        <div class="brand">
            @if($logoDataUri)
                <img class="logo" src="{{ $logoDataUri }}" alt="Logo">
            @endif
            <div>
                <h1>{{ $branding->systemName ?? 'GC Domotic Dashboard' }}</h1>
                <div class="muted">Reporte: Productos (lista) • {{ $generatedAt->format('d/m/Y H:i') }}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Listado de productos</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Producto</th>
                    <th>Modelo</th>
                    <th class="right">Precio</th>
                    <th class="right">Stock</th>
                    @if(($format ?? 'a4') === 'a4')
                        <th>Categoría</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($productos as $p)
                    @php
                        $id = (int) $val($p, ['id'], 0);
                        $precio = (float) $val($p, ['precio'], 0);
                        $stk = (float)($stockMap[$id] ?? (float) $val($p, ['stock_total', 'stock'], 0));
                        $stkClass = $stk <= 1 ? 'badge badge-danger' : ($stk <= 5 ? 'badge badge-warn' : 'badge badge-success');
                    @endphp
                    <tr>
                        <td>{{ $val($p, ['sku'], '—') }}</td>
                        <td>{{ $val($p, ['nombre'], '—') }}</td>
                        <td>{{ $val($p, ['modelo'], '—') }}</td>
                        <td class="right">{{ number_format($precio, 2) }}</td>
                        <td class="right"><span class="{{ $stkClass }}">{{ rtrim(rtrim(number_format($stk, 2), '0'), '.') }}</span></td>
                        @if(($format ?? 'a4') === 'a4')
                            @php
                                $cat = (string) $val($p, ['categoria'], '');
                            @endphp
                            <td>
                                @if($cat !== '')
                                    <span class="badge badge-info">{{ $cat }}</span>
                                @else
                                    —
                                @endif
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="muted" style="margin-top:6px;">Total: {{ count($productos) }} producto(s).</div>
    </div>

    <div class="footer">
        {{ $branding->systemName ?? 'GC Domotic Dashboard' }} • Generado el {{ $generatedAt->format('d/m/Y H:i') }}
    </div>
</body>
</html>
