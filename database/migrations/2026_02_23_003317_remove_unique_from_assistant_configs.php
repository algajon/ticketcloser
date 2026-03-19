<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('assistant_configs', function (Blueprint $table) {
            // Drop foreign key first if it exists using the standard index
            $table->dropForeign(['workspace_id']);
            $table->dropUnique(['workspace_id']);

            // Re-add foreign key (this will create a non-unique index automatically)
            $table->foreign('workspace_id')
                ->references('id')
                ->on('workspaces')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assistant_configs', function (Blueprint $table) {
            $table->dropForeign(['workspace_id']);
            $table->unique('workspace_id');
            $table->foreign('workspace_id')
                ->references('id')
                ->on('workspaces')
                ->cascadeOnDelete();
        });
    }
};
