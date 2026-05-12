<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('voice_configs')
            ->where('recording_enabled', false)
            ->update(['recording_enabled' => true]);

        match (DB::getDriverName()) {
            'mysql' => DB::statement('ALTER TABLE voice_configs ALTER COLUMN recording_enabled SET DEFAULT 1'),
            'pgsql' => DB::statement('ALTER TABLE voice_configs ALTER COLUMN recording_enabled SET DEFAULT true'),
            default => null,
        };
    }

    public function down(): void
    {
        match (DB::getDriverName()) {
            'mysql' => DB::statement('ALTER TABLE voice_configs ALTER COLUMN recording_enabled SET DEFAULT 0'),
            'pgsql' => DB::statement('ALTER TABLE voice_configs ALTER COLUMN recording_enabled SET DEFAULT false'),
            default => null,
        };
    }
};
