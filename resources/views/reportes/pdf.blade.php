@php
    $k = $dashboard['kpis'] ?? [];
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reporte</title>
    <style>
        @page { margin: {{ ($format ?? 'a4') === 'a4' ? '18mm' : '6mm' }}; }
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: {{ ($format ?? 'a4') === 'a4' ? '11px' : '9px' }}; }
        .muted { color: #64748b; }
        .row { width: 100%; }
        .header { border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 12px; }
        .brand { display: flex; align-items: center; gap: 10px; }
        .brand h1 { font-size: {{ ($format ?? 'a4') === 'a4' ? '16px' : '12px' }}; margin: 0; }
        .logo { width: {{ ($format ?? 'a4') === 'a4' ? '90px' : '60px' }}; height: auto; }
        .kpis { display: grid; grid-template-columns: {{ ($format ?? 'a4') === 'a4' ? 'repeat(4, 1fr)' : 'repeat(1, 1fr)' }}; gap: 8px; }
        .card { border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px; }
        .card .label { font-size: 10px; color: #64748b; }
        .card .value { font-size: {{ ($format ?? 'a4') === 'a4' ? '15px' : '12px' }}; font-weight: 700; margin-top: 4px; }
        .card .sub { font-size: 10px; color: #64748b; margin-top: 2px; }
        .section { margin-top: 14px; }
        .section h2 { font-size: {{ ($format ?? 'a4') === 'a4' ? '12px' : '10px' }}; margin: 0 0 6px 0; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border-bottom: 1px solid #eef2f7; padding: 6px 4px; text-align: left; }
        .table th { font-size: 10px; color: #475569; }
        .right { text-align: right; }
        .footer { margin-top: 14px; border-top: 1px solid #e2e8f0; padding-top: 8px; font-size: 9px; color: #64748b; }
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
                <div class="muted">Reporte resumido • {{ $generatedAt->format('d/m/Y H:i') }}</div>
            </div>
        </div>
    </div>

    <div class="kpis">
        <div class="card">
            <div class="label">Ventas del mes</div>
            <div class="value">{{ number_format((float)($k['ventas_mes_total'] ?? 0), 2) }}</div>
            <div class="sub">{{ (int)($k['ventas_mes_count'] ?? 0) }} venta(s)</div>
        </div>
        <div class="card">
            <div class="label">Ventas pagadas (mes)</div>
            <div class="value">{{ number_format((float)($k['ventas_mes_pagadas_total'] ?? 0), 2) }}</div>
            <div class="sub">{{ (int)($k['ventas_mes_pagadas_count'] ?? 0) }} pagada(s)</div>
        </div>
        <div class="card">
            <div class="label">Comisiones pendientes</div>
            <div class="value">{{ number_format((float)($k['comisiones_pendientes_total'] ?? 0), 2) }}</div>
            <div class="sub">{{ (int)($k['comisiones_pendientes_count'] ?? 0) }} comisión(es)</div>
        </div>
        <div class="card">
            <div class="label">Stock bajo</div>
            <div class="value">{{ (int)($k['stock_bajo_count'] ?? 0) }}</div>
            <div class="sub">Productos con stock ≤ 1</div>
        </div>

        <div class="card">
            <div class="label">Tickets abiertos</div>
            <div class="value">{{ (int)($k['tickets_abiertos_count'] ?? 0) }}</div>
            <div class="sub">En curso</div>
        </div>
        <div class="card">
            <div class="label">Agenda pendientes</div>
            <div class="value">{{ (int)($k['agenda_pendientes_count'] ?? 0) }}</div>
            <div class="sub">Servicios por realizar</div>
        </div>
        <div class="card">
            <div class="label">Agenda hoy</div>
            <div class="value">{{ (int)($k['agenda_hoy_count'] ?? 0) }}</div>
            <div class="sub">Actividades del día</div>
        </div>
        <div class="card">
            <div class="label">Ventas pendientes (mes)</div>
            <div class="value">{{ (int)($k['ventas_mes_pendientes_count'] ?? 0) }}</div>
            <div class="sub">Por cobrar</div>
        </div>
    </div>

    @if(($format ?? 'a4') === 'a4')
        @php
            $series = $dashboard['series']['ventas_30d'] ?? [];
        @endphp
        <div class="section">
            <h2>Ventas (últimos 30 días, pagadas)</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th class="right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(array_slice($series, -10) as $r)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($r['date'] ?? now())->format('d/m') }}</td>
                            <td class="right">{{ number_format((float)($r['total'] ?? 0), 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="muted" style="margin-top:6px;">(Muestra solo los últimos 10 días por espacio.)</div>
        </div>
    @endif

    <div class="footer">
        {{ $branding->systemName ?? 'GC Domotic Dashboard' }} • Generado el {{ $generatedAt->format('d/m/Y H:i') }}
    </div>
</body>
</html>
