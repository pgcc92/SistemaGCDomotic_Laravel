<?php

namespace App\Support;

final class AppVersion
{
    private ?string $current = null;

    public function current(): string
    {
        if ($this->current !== null) {
            return $this->current;
        }

        $version = trim((string) env('APP_VERSION', ''));
        if ($version !== '') {
            return $this->current = $version;
        }

        $version = $this->gitVersion();
        if ($version !== '') {
            return $this->current = $version;
        }

        $manifest = public_path('build/manifest.json');
        if (is_file($manifest)) {
            return $this->current = substr(sha1_file($manifest) ?: (string) filemtime($manifest), 0, 12);
        }

        return $this->current = 'dev';
    }

    private function gitVersion(): string
    {
        $head = base_path('.git/HEAD');
        if (!is_file($head)) {
            return '';
        }

        $value = trim((string) file_get_contents($head));
        if ($value === '') {
            return '';
        }

        if (!str_starts_with($value, 'ref:')) {
            return substr($value, 0, 12);
        }

        $ref = trim(substr($value, 4));
        $refFile = base_path(".git/{$ref}");
        if (is_file($refFile)) {
            return substr(trim((string) file_get_contents($refFile)), 0, 12);
        }

        $packed = base_path('.git/packed-refs');
        if (!is_file($packed)) {
            return '';
        }

        foreach (file($packed, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            if (str_starts_with($line, '#') || str_starts_with($line, '^')) {
                continue;
            }

            [$hash, $packedRef] = array_pad(preg_split('/\s+/', $line, 2) ?: [], 2, '');
            if ($packedRef === $ref && $hash !== '') {
                return substr($hash, 0, 12);
            }
        }

        return '';
    }
}
