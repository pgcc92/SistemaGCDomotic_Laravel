@php
    $ventas = $ventas ?? [];
    $filters = $filters ?? [];
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Ventas</title>
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
                <div class="muted">
                    Reporte: Ventas (detalle)
                    • {{ $generatedAt->format('d/m/Y H:i') }}
                    @if(($filters['from'] ?? '') !== '' && ($filters['to'] ?? '') !== '')
                        • {{ \Illuminate\Support\Carbon::parse($filters['from'])->format('d/m/Y') }}–{{ \Illuminate\Support\Carbon::parse($filters['to'])->format('d/m/Y') }}
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Listado de ventas</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Código</th>
                    <th>Doc</th>
                    <th>Cliente</th>
                    <th>Estado</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $sum = 0.0;
                @endphp
                @foreach($ventas as $v)
                    @php
                        $total = (float)($v['total'] ?? $v->total ?? 0);
                        $sum += $total;
                    @endphp
                    <tr>
                        <td>{{ \Illuminate\Support\Carbon::parse($v['fecha_venta'] ?? $v->fecha_venta ?? now())->format('d/m/Y H:i') }}</td>
                        <td>{{ $v['venta_codigo'] ?? $v->venta_codigo ?? ('#'.($v['id'] ?? $v->id ?? '')) }}</td>
                        <td>{{ $v['tipo_documento'] ?? $v->tipo_documento ?? '—' }}</td>
                        @php
                            $cli = (string)($v['cliente_razon'] ?? $v->cliente_razon ?? '');
                            $cliDoc = (string)($v['cliente_doc_num'] ?? $v->cliente_doc_num ?? '');
                            $cliId = (string)($v['cliente_id'] ?? $v->cliente_id ?? '');
                        @endphp
                        <td>
                            <div style="font-weight:600;">
                                {{ $cli !== '' ? $cli : ($cliDoc !== '' ? $cliDoc : ($cliId !== '' ? ('Cliente #'.$cliId) : '—')) }}
                            </div>
                            @if(($format ?? 'a4') === 'a4' && $cli !== '' && $cliDoc !== '')
                                <div class="muted">{{ $cliDoc }}</div>
                            @endif
                        </td>
                        @php
                            $est = (string)($v['estado'] ?? $v->estado ?? '—');
                            $estClass = match (strtoupper($est)) {
                                'PAGADA' => 'badge badge-success',
                                'PENDIENTE' => 'badge badge-warn',
                                'ANULADA' => 'badge badge-danger',
                                default => 'badge badge-info',
                            };
                        @endphp
                        <td><span class="{{ $estClass }}">{{ $est }}</span></td>
                        <td class="right">{{ number_format($total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="muted" style="margin-top:6px;">
            Total ventas listadas: {{ count($ventas) }} • Suma: {{ number_format($sum, 2) }}
        </div>
    </div>

    <div class="footer">
        {{ $branding->systemName ?? 'GC Domotic Dashboard' }} • Generado el {{ $generatedAt->format('d/m/Y H:i') }}
    </div>
</body>
</html>
