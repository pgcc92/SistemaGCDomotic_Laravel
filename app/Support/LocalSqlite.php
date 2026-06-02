<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

final class LocalSqlite
{
    public static function ensureDatabaseFileExists(): void
    {
        $connection = (string) config('database.default');
        if ($connection !== 'sqlite') {
            return;
        }

        $database = (string) config('database.connections.sqlite.database');
        if ($database === '' || $database === ':memory:') {
            return;
        }

        // Si es relativo (ej: database/database.sqlite), lo resolvemos contra base_path().
        $path = str_starts_with($database, DIRECTORY_SEPARATOR) ? $database : base_path($database);

        // Laravel no normaliza un `DB_DATABASE=database/database.sqlite` a absoluto automáticamente.
        // Forzamos el path absoluto en runtime para evitar errores "Ensure this is an absolute path".
        config(['database.connections.sqlite.database' => $path]);

        $dir = dirname($path);
        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        if (!File::exists($path)) {
            File::put($path, '');
        }
    }
}
