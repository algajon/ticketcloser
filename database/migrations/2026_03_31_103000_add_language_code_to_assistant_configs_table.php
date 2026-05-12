<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assistant_configs', function (Blueprint $table) {
            $table->string('language_code', 20)->nullable()->after('voice_id');
        });
    }

    public function down(): void
    {
        Schema::table('assistant_configs', function (Blueprint $table) {
            $table->dropColumn('language_code');
        });
    }
};
