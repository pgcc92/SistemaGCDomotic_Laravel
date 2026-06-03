<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use RuntimeException;
use Throwable;

final class UploadService
{
    /**
     * Guarda y procesa una imagen bajo {GC_UPLOAD_ROOT}/{category}/YYYY/MM/{uuid}.{ext}
     * e intenta crear un thumbnail {uuid}_thumb.{ext}.
     *
     * Devuelve URLs relativas:
     * - url: /files/{category}/{YYYY}/{MM}/{file}
     * - thumb_url: /files/{category}/{YYYY}/{MM}/{uuid}_thumb.{ext} | null
     *
     * @return array{url:string,thumb_url:?string,filename:string,thumb_filename:?string,year:string,month:string}
     */
    public function saveImage(UploadedFile $file, string $category): array
    {
        $category = trim($category);
        if (!in_array($category, ['settings', 'productos', 'dispositivos'], true)) {
            throw new RuntimeException('Categoría de imagen inválida.');
        }

        if (!$file->isValid()) {
            throw new RuntimeException('Upload inválido.');
        }

        $tmpPath = $file->getPathname();
        if (!is_uploaded_file($tmpPath)) {
            throw new RuntimeException('Upload inválido (no es un archivo subido).');
        }

        $maxBytes = 10 * 1024 * 1024; // 10MB
        $size = (int) ($file->getSize() ?? 0);
        if ($size <= 0 || $size > $maxBytes) {
            throw new RuntimeException('La imagen supera el tamaño máximo (10MB).');
        }

        $mime = strtolower((string) $file->getMimeType());
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!array_key_exists($mime, $allowed)) {
            throw new RuntimeException('Tipo de imagen no permitido. Solo JPG/PNG/WebP.');
        }

        $ext = $allowed[$mime];

        $year = now()->format('Y');
        $month = now()->format('m');
        $uuid = (string) Str::uuid();

        $dir = $this->path($category, $year, $month);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('No se pudo crear el directorio de destino.');
        }
        if (!is_writable($dir)) {
            throw new RuntimeException('El almacenamiento de imágenes no tiene permisos de escritura.');
        }

        $filename = "{$uuid}.{$ext}";
        $fullPath = $dir . DIRECTORY_SEPARATOR . $filename;

        try {
            $file->move($dir, $filename);

            $manager = new ImageManager(new Driver());
            $image = $manager->read($fullPath)->orient();
            $image->resizeDown(480, 480);
            $image->save($fullPath, ['quality' => 85]);
        } catch (Throwable $exception) {
            @unlink($fullPath);
            throw new RuntimeException('No se pudo guardar o procesar la imagen.', 0, $exception);
        }

        $thumbFilename = "{$uuid}_thumb.{$ext}";
        $thumbFullPath = $dir . DIRECTORY_SEPARATOR . $thumbFilename;
        $thumbUrl = null;
        try {
            $thumb = $manager->read($fullPath)->orient();
            $thumb->coverDown(320, 240);
            $thumb->save($thumbFullPath, ['quality' => 80]);
            if (is_file($thumbFullPath)) {
                $thumbUrl = "/files/{$category}/{$year}/{$month}/{$thumbFilename}";
            }
        } catch (Throwable $exception) {
            @unlink($thumbFullPath);
            Log::warning('No se pudo generar la miniatura de la imagen.', [
                'category' => $category,
                'file' => $filename,
                'error' => $exception->getMessage(),
            ]);
        }

        return [
            'url' => "/files/{$category}/{$year}/{$month}/{$filename}",
            'thumb_url' => $thumbUrl,
            'filename' => $filename,
            'thumb_filename' => $thumbUrl ? $thumbFilename : null,
            'year' => $year,
            'month' => $month,
        ];
    }

    /** @param array<int,?string> $urls */
    public function deleteMany(array $urls): void
    {
        foreach ($urls as $url) {
            $this->deleteByUrl($url);
        }
    }

    public function deleteByUrl(?string $url): void
    {
        if (!is_string($url) || !preg_match(
            '#^/files/(settings|productos|dispositivos)/(\d{4})/(0[1-9]|1[0-2])/([0-9a-f-]{36})(?:_thumb)?\.(jpg|png|webp)$#i',
            $url,
            $matches,
        )) {
            return;
        }

        $directory = $this->path($matches[1], $matches[2], $matches[3]);
        foreach (["{$matches[4]}.{$matches[5]}", "{$matches[4]}_thumb.{$matches[5]}"] as $filename) {
            $path = $directory . DIRECTORY_SEPARATOR . $filename;
            if (is_file($path) && !@unlink($path)) {
                Log::warning('No se pudo eliminar una imagen huérfana.', ['path' => $path]);
            }
        }
    }

    private function root(): string
    {
        $root = (string) config('gc_uploads.root', base_path('storage'));
        return rtrim($root, "/\\");
    }

    private function path(string $category, string $year, string $month): string
    {
        return $this->root() . DIRECTORY_SEPARATOR . $category . DIRECTORY_SEPARATOR . $year . DIRECTORY_SEPARATOR . $month;
    }
}
