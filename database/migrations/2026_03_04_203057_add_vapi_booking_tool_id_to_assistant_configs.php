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
            $table->string('vapi_booking_tool_id')->nullable()->after('vapi_tool_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assistant_configs', function (Blueprint $table) {
            $table->dropColumn('vapi_booking_tool_id');
        });
    }
};
