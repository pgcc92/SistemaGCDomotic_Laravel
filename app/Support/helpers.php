<?php

declare(strict_types=1);

if (!function_exists('file_url')) {
    /**
     * Normaliza URLs guardadas en BD para evitar mixed-content y forzar rutas relativas.
     *
     * Reglas:
     * - Si empieza con "/" => se usa tal cual.
     * - Si es "http(s)://..." => devuelve solo el path (y query si existe).
     * - Si es null/vacío => null.
     */
    function file_url(?string $value): ?string
    {
        $value = $value !== null ? trim($value) : null;
        if ($value === null || $value === '') {
            return null;
        }

        if (str_starts_with($value, '/')) {
            return $value;
        }

        if (preg_match('/^https?:\\/\\//i', $value) === 1) {
            $parts = parse_url($value);
            if (!is_array($parts)) {
                return $value;
            }
            $path = $parts['path'] ?? '';
            $query = $parts['query'] ?? null;

            if ($path === '' && $query === null) {
                return $value;
            }

            return $query ? ($path.'?'.$query) : $path;
        }

        return $value;
    }
}

