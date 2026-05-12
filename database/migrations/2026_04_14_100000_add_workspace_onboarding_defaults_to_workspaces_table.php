<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->string('team_size', 40)->nullable()->after('use_case_details');
            $table->string('default_assistant_name')->nullable()->after('team_size');
            $table->string('default_preset_key', 60)->nullable()->after('default_assistant_name');
            $table->string('default_language_code', 20)->nullable()->after('default_preset_key');
        });
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropColumn([
                'team_size',
                'default_assistant_name',
                'default_preset_key',
                'default_language_code',
            ]);
        });
    }
};
