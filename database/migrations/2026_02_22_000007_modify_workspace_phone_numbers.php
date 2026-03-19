<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('workspace_phone_numbers', function (Blueprint $table) {
            // allow associating multiple phone numbers and attach them to assistants
            if (!Schema::hasColumn('workspace_phone_numbers', 'assistant_id')) {
                $table->foreignId('assistant_id')->nullable()->constrained('assistant_configs')->nullOnDelete();
            }

            // To remove the unique index on workspace_id safely we must drop any foreign key
            // that depends on it, drop the unique index, and then recreate the foreign key.
            try {
                $table->dropForeign(['workspace_id']);
            } catch (\Throwable $e) {
                // ignore if not present
            }

            try {
                $table->dropUnique(['workspace_id']);
            } catch (\Throwable $e) {
                // ignore if not present
            }

            try {
                $table->foreign('workspace_id')->references('id')->on('workspaces')->cascadeOnDelete();
            } catch (\Throwable $e) {
                // ignore if foreign key cannot be created
            }
        });
    }

    public function down(): void
    {
        Schema::table('workspace_phone_numbers', function (Blueprint $table) {
            if (Schema::hasColumn('workspace_phone_numbers', 'assistant_id')) {
                $table->dropConstrainedForeignId('assistant_id');
            }

            // re-add unique constraint (best-effort)
            try {
                $table->unique('workspace_id');
            } catch (\Throwable $e) {
                // ignore
            }
        });
    }
};
