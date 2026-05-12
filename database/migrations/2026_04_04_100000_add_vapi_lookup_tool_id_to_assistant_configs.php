<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('assistant_configs', 'vapi_lookup_tool_id')) {
            return;
        }

        Schema::table('assistant_configs', function (Blueprint $table) {
            $table->string('vapi_lookup_tool_id')->nullable()->after('vapi_booking_tool_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('assistant_configs', 'vapi_lookup_tool_id')) {
            return;
        }

        Schema::table('assistant_configs', function (Blueprint $table) {
            $table->dropColumn('vapi_lookup_tool_id');
        });
    }
};
