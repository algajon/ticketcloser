<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('assistant_configs', function (Blueprint $table) {
            if (!Schema::hasColumn('assistant_configs', 'intake_params')) {
                $table->json('intake_params')->nullable()->after('system_prompt');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assistant_configs', function (Blueprint $table) {
            if (Schema::hasColumn('assistant_configs', 'intake_params')) {
                $table->dropColumn('intake_params');
            }
        });
    }
};
