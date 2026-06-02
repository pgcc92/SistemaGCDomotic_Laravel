@php
    $productos = $productos ?? [];
    $kardex = $kardex ?? [];
    $kardexByProducto = [];
    $val = static function ($row, array $keys, $default = null) {
        foreach ($keys as $key) {
            if (is_array($row) && array_key_exists($key, $row)) return $row[$key];
            if (is_object($row) && property_exists($row, $key)) return $row->{$key};
        }
        return $default;
    };
    $fmtDate = static function ($value) {
        try {
            return $value ? \Illuminate\Support\Carbon::parse($value)->format('d/m/Y H:i') : '—';
        } catch (\Throwable) {
            return (string) ($value ?: '—');
        }
    };

    foreach (($kardex ?: []) as $m) {
        $pid = (int) $val($m, ['producto_id'], 0);
        if ($pid <= 0) continue;
        $kardexByProducto[$pid] = $kardexByProducto[$pid] ?? [];
        $kardexByProducto[$pid][] = $m;
    }
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Detalle de productos</title>
    @include('reportes.partials.pdf_base_styles')
    <style>
        .prod { page-break-inside: avoid; margin-bottom: 14px; }
        .prod-title { font-weight: 700; font-size: 12px; margin: 0; }
        .prod-meta { margin-top: 2px; font-size: 10px; color: #64748b; }
        .pill { display:inline-block; border:1px solid #e2e8f0; border-radius: 999px; padding: 2px 8px; font-size: 9px; margin-right: 6px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">
            @if($logoDataUri)
                <img class="logo" src="{{ $logoDataUri }}" alt="Logo">
            @endif
            <div>
                <h1>{{ $branding->systemName ?? 'GC Domotic Dashboard' }}</h1>
                <div class="muted">Reporte: Productos (detalle + kardex) • {{ $generatedAt->format('d/m/Y H:i') }}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Detalle de productos</h2>
        <div class="muted" style="margin-bottom:8px;">
            Incluye movimientos recientes por producto (según disponibilidad). Recomendado: A4.
        </div>

        @foreach($productos as $p)
            @php
                $id = (int) $val($p, ['id'], 0);
                $movs = $kardexByProducto[$id] ?? [];
            @endphp
            <div class="prod card">
                <div>
                    <div class="prod-title">
                        {{ $val($p, ['nombre'], '—') }}
                    </div>
                    <div class="prod-meta">
                        <span class="pill">SKU: {{ $val($p, ['sku'], '—') }}</span>
                        @php
                            $modelo = (string) $val($p, ['modelo'], '');
                        @endphp
                        @if($modelo !== '')
                            <span class="pill">Modelo: {{ $modelo }}</span>
                        @endif
                        @php
                            $cat = (string) $val($p, ['categoria'], '');
                        @endphp
                        @if($cat !== '' && ($format ?? 'a4') === 'a4')
                            <span class="pill">Categoría: {{ $cat }}</span>
                        @endif
                    </div>
                </div>

                <div style="margin-top:10px;">
                    <div style="font-weight:600; font-size:10px; margin-bottom:4px;">Kardex / movimientos</div>
                    @if(count($movs) === 0)
                        <div class="muted">Sin movimientos registrados (o no cargados).</div>
                    @else
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Tipo</th>
                                    <th class="right">Cant</th>
                                    @if(($format ?? 'a4') === 'a4')
                                        <th>Origen</th>
                                        <th>Destino</th>
                                        <th>Motivo</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(array_slice($movs, 0, ($format ?? 'a4') === 'a4' ? 8 : 4) as $m)
                                    <tr>
                                        <td>{{ $fmtDate($val($m, ['created_at', 'fecha'], null)) }}</td>
                                        <td>{{ $val($m, ['tipo'], '—') }}</td>
                                        <td class="right">{{ rtrim(rtrim(number_format((float) $val($m, ['cantidad'], 0), 2), '0'), '.') }}</td>
                                        @if(($format ?? 'a4') === 'a4')
                                            <td>{{ $val($m, ['sucursal_origen'], '—') }}</td>
                                            <td>{{ $val($m, ['sucursal_destino'], '—') }}</td>
                                            <td>{{ $val($m, ['motivo'], '—') }}</td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="muted" style="margin-top:6px;">
                            Mostrando {{ min(count($movs), ($format ?? 'a4') === 'a4' ? 8 : 4) }} de {{ count($movs) }} movimiento(s) cargado(s).
                        </div>
                    @endif
                </div>
            </div>
        @endforeach

        <div class="muted" style="margin-top:6px;">Total productos: {{ count($productos) }}.</div>
    </div>

    <div class="footer">
        {{ $branding->systemName ?? 'GC Domotic Dashboard' }} • Generado el {{ $generatedAt->format('d/m/Y H:i') }}
    </div>
</body>
</html>
