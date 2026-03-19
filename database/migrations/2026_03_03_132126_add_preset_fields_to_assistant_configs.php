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
            $table->string('preset_key')->nullable()->after('is_active');
            $table->json('override_params')->nullable()->after('preset_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assistant_configs', function (Blueprint $table) {
            $table->dropColumn(['preset_key', 'override_params']);
        });
    }
};
