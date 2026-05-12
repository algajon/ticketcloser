<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('assistant_configs', function (Blueprint $table) {
            $table->string('model_name')->nullable()->after('language_code');
        });

        DB::table('assistant_configs')
            ->whereNull('model_name')
            ->update(['model_name' => 'gpt-4o-mini']);
    }

    public function down(): void
    {
        Schema::table('assistant_configs', function (Blueprint $table) {
            $table->dropColumn('model_name');
        });
    }
};
