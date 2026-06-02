@php
    $tickets = $tickets ?? [];
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
    // Compat: si el controlador ya lo calculó, lo usamos. Si no, lo calculamos acá.
    $byEstado = $byEstado ?? [];
    if (!is_array($byEstado) || count($byEstado) === 0) {
        $byEstado = [];
        foreach (($tickets ?: []) as $t) {
            $estado = (string) $val($t, ['estado'], '—');
            $byEstado[$estado] = ($byEstado[$estado] ?? 0) + 1;
        }
    }
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Tickets</title>
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
                <div class="muted">Reporte: Tickets (resumen) • {{ $generatedAt->format('d/m/Y H:i') }}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Resumen por estado</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Estado</th>
                    <th class="right">Cantidad</th>
                </tr>
            </thead>
            <tbody>
                @foreach($byEstado as $estado => $c)
                    <tr>
                        <td>{{ $estado }}</td>
                        <td class="right">{{ (int)$c }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Últimos tickets</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Ticket</th>
                    @if(($format ?? 'a4') === 'a4')
                        <th>Cliente</th>
                        <th>Asunto</th>
                    @endif
                    <th>Estado</th>
                    <th class="right">Actualizado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tickets as $t)
                    @php
                        $est = (string) $val($t, ['estado'], '—');
                        $estClass = match (strtoupper($est)) {
                            'ABIERTO' => 'badge badge-warn',
                            'CERRADO' => 'badge badge-success',
                            'PENDIENTE' => 'badge badge-warn',
                            default => 'badge badge-info',
                        };
                    @endphp
                    <tr>
                        <td>{{ $val($t, ['ticket_id', 'id'], '—') }}</td>
                        @if(($format ?? 'a4') === 'a4')
                            <td>{{ $val($t, ['cliente_nombre', 'nombre_cliente', 'cliente_wa', 'telefono'], '—') }}</td>
                            <td>{{ $val($t, ['asunto', 'resumen'], '—') }}</td>
                        @endif
                        <td><span class="{{ $estClass }}">{{ $est }}</span></td>
                        <td class="right">{{ $fmtDate($val($t, ['updated_at', 'created_at'], null)) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="muted" style="margin-top:6px;">Muestra {{ count($tickets) }} ticket(s) (últimos).</div>
    </div>

    <div class="footer">
        {{ $branding->systemName ?? 'GC Domotic Dashboard' }} • Generado el {{ $generatedAt->format('d/m/Y H:i') }}
    </div>
</body>
</html>
