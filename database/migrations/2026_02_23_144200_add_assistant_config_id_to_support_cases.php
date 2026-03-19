<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('support_cases', function (Blueprint $table) {
            $table->foreignId('assistant_config_id')
                ->nullable()
                ->after('workspace_id')
                ->constrained('assistant_configs')
                ->nullOnDelete();

            $table->index(['workspace_id', 'assistant_config_id']);
        });
    }

    public function down(): void
    {
        Schema::table('support_cases', function (Blueprint $table) {
            $table->dropForeign(['assistant_config_id']);
            $table->dropIndex(['workspace_id', 'assistant_config_id']);
            $table->dropColumn('assistant_config_id');
        });
    }
};
