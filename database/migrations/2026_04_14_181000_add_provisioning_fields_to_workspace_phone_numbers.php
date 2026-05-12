<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspace_phone_numbers', function (Blueprint $table) {
            $table->string('provisioning_mode', 40)->nullable()->after('forwarding_number');
            $table->string('external_provider', 60)->nullable()->after('provisioning_mode');
        });
    }

    public function down(): void
    {
        Schema::table('workspace_phone_numbers', function (Blueprint $table) {
            $table->dropColumn([
                'provisioning_mode',
                'external_provider',
            ]);
        });
    }
};
