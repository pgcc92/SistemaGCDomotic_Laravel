@php
    $kardex = $kardex ?? [];
    $filters = $filters ?? [];
    $byProducto = [];
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
        $byProducto[$pid] = $byProducto[$pid] ?? [
            'sku' => (string) $val($m, ['sku'], ''),
            'nombre' => (string) $val($m, ['producto_nombre', 'nombre'], ''),
            'movs' => [],
        ];
        $byProducto[$pid]['movs'][] = $m;
    }
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Kardex</title>
    @include('reportes.partials.pdf_base_styles')
    <style>
        .tipo-ENTRADA { background:#dcfce7; border-color:#86efac; color:#166534; }
        .tipo-SALIDA, .tipo-VENTA { background:#fee2e2; border-color:#fca5a5; color:#991b1b; }
        .tipo-TRANSFER, .tipo-AJUSTE { background:#dbeafe; border-color:#93c5fd; color:#1e40af; }
        .tipo-DEVOLUCION { background:#ffedd5; border-color:#fdba74; color:#9a3412; }
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
                <div class="muted">
                    Reporte: Kardex / Movimientos • {{ $generatedAt->format('d/m/Y H:i') }}
                    @if(($filters['from'] ?? '') !== '' && ($filters['to'] ?? '') !== '')
                        • {{ \Illuminate\Support\Carbon::parse($filters['from'])->format('d/m/Y') }}–{{ \Illuminate\Support\Carbon::parse($filters['to'])->format('d/m/Y') }}
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Movimientos por producto</h2>
        <div class="muted" style="margin-bottom:6px;">
            Se agrupa 1×1 por producto. Para ticket se muestra resumido.
        </div>

        @foreach($byProducto as $pid => $info)
            <div class="card" style="margin-bottom:12px; page-break-inside:avoid;">
                <div style="font-weight:700; margin-bottom:6px;">
                    {{ ($info['sku'] ?? '') !== '' ? $info['sku'].' • ' : '' }}{{ $info['nombre'] ?? 'Producto' }}
                </div>
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
                        @foreach(array_slice($info['movs'] ?? [], 0, ($format ?? 'a4') === 'a4' ? 10 : 5) as $m)
                            @php
                                $tipo = (string) $val($m, ['tipo'], '—');
                                $tipoCls = 'badge tipo-'.preg_replace('/[^A-Z0-9_\\-]/', '', strtoupper($tipo));
                            @endphp
                            <tr>
                                <td>{{ $fmtDate($val($m, ['created_at', 'fecha'], null)) }}</td>
                                <td><span class="{{ $tipoCls }}">{{ $tipo }}</span></td>
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
                <div class="muted" style="margin-top:6px;">Movimientos: {{ count($info['movs'] ?? []) }}.</div>
            </div>
        @endforeach

        <div class="muted" style="margin-top:6px;">Productos con movimientos: {{ count($byProducto) }}.</div>
    </div>

    <div class="footer">
        {{ $branding->systemName ?? 'GC Domotic Dashboard' }} • Generado el {{ $generatedAt->format('d/m/Y H:i') }}
    </div>
</body>
</html>
