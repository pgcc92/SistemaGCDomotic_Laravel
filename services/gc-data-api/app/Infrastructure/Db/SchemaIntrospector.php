<?php

namespace App\Infrastructure\Db;

use Illuminate\Support\Facades\DB;

final class SchemaIntrospector
{
    public function hasTable(string $table, string $schema = 'public'): bool
    {
        $row = DB::selectOne(
            'select 1 as ok from information_schema.tables where table_schema = ? and table_name = ? limit 1',
            [$schema, $table]
        );
        return $row !== null;
    }

    public function existingColumns(string $table, array $candidates, string $schema = 'public'): array
    {
        if ($candidates === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($candidates), '?'));
        $bindings = array_merge([$schema, $table], array_values($candidates));

        $rows = DB::select(
            "select column_name from information_schema.columns where table_schema = ? and table_name = ? and column_name in ({$placeholders})",
            $bindings
        );

        return array_values(array_map(fn ($r) => (string) $r->column_name, $rows));
    }
}

