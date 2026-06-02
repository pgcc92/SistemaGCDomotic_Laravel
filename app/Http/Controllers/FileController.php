<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class FileController
{
    public function show(Request $request, string $cat, string $y, string $m, string $file): BinaryFileResponse
    {
        $this->assertValidPath($cat, $y, $m, $file, allowSettings: false);
        $path = base_path("storage/{$cat}/{$y}/{$m}/{$file}");

        if (!is_file($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Cache-Control' => 'private, max-age=86400', // 1 día
        ]);
    }

    public function showSettings(Request $request, string $y, string $m, string $file): BinaryFileResponse
    {
        $this->assertValidPath('settings', $y, $m, $file, allowSettings: true);
        $path = base_path("storage/settings/{$y}/{$m}/{$file}");

        if (!is_file($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Cache-Control' => 'public, max-age=604800', // 7 días
        ]);
    }

    private function assertValidPath(string $cat, string $y, string $m, string $file, bool $allowSettings): void
    {
        $allowedCats = $allowSettings ? ['settings'] : ['productos', 'dispositivos'];
        if (!in_array($cat, $allowedCats, true)) {
            abort(404);
        }

        if (!preg_match('/^\\d{4}$/', $y)) {
            abort(404);
        }

        if (!preg_match('/^(0[1-9]|1[0-2])$/', $m)) {
            abort(404);
        }

        // UUID + opcional _thumb, extensión jpg/png/webp
        if (!preg_match('/^[0-9a-fA-F-]{36}(_thumb)?\\.(jpg|png|webp)$/', $file)) {
            abort(404);
        }
    }
}
