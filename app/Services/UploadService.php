<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

final class UploadService
{
    /**
     * Guarda y procesa una imagen bajo storage/{category}/YYYY/MM/{uuid}.{ext}
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
        if ($category === '') {
            throw new \RuntimeException('Categoría inválida.');
        }

        if (!$file->isValid()) {
            throw new \RuntimeException('Upload inválido.');
        }

        $tmpPath = $file->getPathname();
        if (!is_uploaded_file($tmpPath)) {
            throw new \RuntimeException('Upload inválido (no es un archivo subido).');
        }

        $maxBytes = 10 * 1024 * 1024; // 10MB
        $size = (int) ($file->getSize() ?? 0);
        if ($size <= 0 || $size > $maxBytes) {
            throw new \RuntimeException('La imagen supera el tamaño máximo (10MB).');
        }

        $mime = strtolower((string) $file->getMimeType());
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!array_key_exists($mime, $allowed)) {
            throw new \RuntimeException('Tipo de imagen no permitido. Solo JPG/PNG/WebP.');
        }

        $ext = strtolower((string) ($file->getClientOriginalExtension() ?: $allowed[$mime]));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $ext = $allowed[$mime];
        }
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }

        $year = now()->format('Y');
        $month = now()->format('m');
        $uuid = (string) Str::uuid();

        $dir = base_path("storage/{$category}/{$year}/{$month}");
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('No se pudo crear el directorio de destino.');
        }

        $filename = "{$uuid}.{$ext}";
        $fullPath = $dir . DIRECTORY_SEPARATOR . $filename;

        $file->move($dir, $filename);

        $manager = new ImageManager(new Driver());
        $image = $manager->read($fullPath)->orient();
        $image->resizeDown(480, 480);
        $image->save($fullPath, ['quality' => 85]);

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
        } catch (\Throwable) {
            // thumbnail es best-effort; puede no existir
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
}

