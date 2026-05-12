<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->string('default_phone_provisioning_mode', 40)->nullable()->after('default_language_code');
            $table->string('default_external_phone_provider', 40)->nullable()->after('default_phone_provisioning_mode');
            $table->string('default_vapi_credential_id', 120)->nullable()->after('default_external_phone_provider');
        });
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropColumn([
                'default_phone_provisioning_mode',
                'default_external_phone_provider',
                'default_vapi_credential_id',
            ]);
        });
    }
};
