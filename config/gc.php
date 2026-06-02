<?php

return [
    'tenant' => [
        // single | subdomain
        'mode' => env('TENANT_MODE', 'single'),
        'default' => env('TENANT_DEFAULT', 'default'),
        // Ej: midominio.com (sin esquema). Se usa solo en modo subdomain.
        'base_domain' => env('TENANT_BASE_DOMAIN', ''),
    ],

    'branding' => [
        'colors' => [
            // Formato: "R G B" (espacio-separado) para usar con CSS vars y Tailwind rgb(var(--...)/alpha)
            // Paleta GC Domotic (default): #0CAF7D y #0C1444
            'primary' => env('BRANDING_PRIMARY_RGB', '12 175 125'),
            'secondary' => env('BRANDING_SECONDARY_RGB', '12 20 68'),
        ],
        'font_family' => env('BRANDING_FONT_FAMILY', 'Figtree'),
        'dark_mode_enabled' => env('BRANDING_DARK_MODE_ENABLED', true),
        'login_gradient' => [
            'from' => env('BRANDING_LOGIN_GRADIENT_FROM_RGB', '12 175 125'), // #0CAF7D
            'to' => env('BRANDING_LOGIN_GRADIENT_TO_RGB', '12 20 68'), // #0C1444
        ],
    ],

    // Capa de servicios para consumir la "BD remota" vía APIs (no conexión directa desde este dashboard).
    'remote_api' => [
        'base_url' => env('REMOTE_API_BASE_URL', 'http://localhost:8001'),
        'api_key' => env('REMOTE_API_KEY', ''),
        'timeout_seconds' => env('REMOTE_API_TIMEOUT', 10),
        'endpoints' => [
            'branding_get' => env('REMOTE_API_BRANDING_GET', '/api/v1/branding'),
            'branding_put' => env('REMOTE_API_BRANDING_PUT', '/api/v1/branding'),
        ],
    ],

    // file | api
    'config_store_driver' => env('CONFIG_STORE_DRIVER', 'file'),
];
