<?php

return [
    'api' => [
        // Recomendado: Bearer token. También aceptamos X-API-Key por compatibilidad.
        'token' => env('GC_API_TOKEN', ''),
        // sha256 | hmac_sha256
        'hash_driver' => env('GC_API_KEY_HASH_DRIVER', 'sha256'),
        // Opcional: usuario "dueño" para modo GC_API_TOKEN (env).
        // Si se setea (>0), habilita RBAC para llamadas con GC_API_TOKEN.
        'created_by' => (int) env('GC_API_CREATED_BY', 0),
    ],

    'tenant' => [
        // single | subdomain
        'mode' => env('TENANT_MODE', 'subdomain'),
        'default' => env('TENANT_DEFAULT', 'default'),
        'base_domain' => env('TENANT_BASE_DOMAIN', ''),
    ],

    // Permite mapear nombres reales sin tocar código (cuando confirmemos el esquema).
    'tables' => [
        'clientes' => env('GC_TABLE_CLIENTES', 'clientes'),
        'tickets' => env('GC_TABLE_TICKETS', 'tickets'),
        'ventas' => env('GC_TABLE_VENTAS', 'ventas'),
        'productos' => env('GC_TABLE_PRODUCTOS', 'productos'),
        'stock_sucursal' => env('GC_TABLE_STOCK_SUCURSAL', 'stock_sucursal'),
        'movimientos_stock' => env('GC_TABLE_MOVIMIENTOS_STOCK', 'movimientos_stock'),
        'venta_items' => env('GC_TABLE_VENTA_ITEMS', 'venta_items'),
        'pagos' => env('GC_TABLE_PAGOS', 'pagos'),
        'comisiones' => env('GC_TABLE_COMISIONES', 'comisiones'),
        'comision_reglas' => env('GC_TABLE_COMISION_REGLAS', 'comision_reglas'),
        'documento_series' => env('GC_TABLE_DOCUMENTO_SERIES', 'documento_series'),
        'tipo_cambio' => env('GC_TABLE_TIPO_CAMBIO', 'tipo_cambio'),
        'mensajes_buffer' => env('GC_TABLE_MENSAJES_BUFFER', 'mensajes_buffer'),
        'agenda_instalaciones' => env('GC_TABLE_AGENDA', 'agenda_instalaciones'),
    ],

    'ventas' => [
        'default_serie_nota_venta' => env('GC_DEFAULT_NV_SERIE', 'NV01'),
        'igv_porcentaje_default' => env('GC_IGV_DEFAULT', 18.00),
    ],

    'stock' => [
        // Si tu BD tiene triggers que actualizan stock_sucursal a partir de movimientos_stock,
        // activa esto para evitar doble descuento/suma.
        'use_triggers' => (bool) env('GC_STOCK_USE_TRIGGERS', true),
    ],
];
