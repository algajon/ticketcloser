<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('assistant_configs', function (Blueprint $table) {
            $table->string('fallback_phone')->nullable()->after('intake_params');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assistant_configs', function (Blueprint $table) {
            $table->dropColumn('fallback_phone');
        });
    }
};
