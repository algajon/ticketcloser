<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspace_phone_numbers', function (Blueprint $table) {
            $table->string('vapi_credential_id', 120)->nullable()->after('external_provider');
        });
    }

    public function down(): void
    {
        Schema::table('workspace_phone_numbers', function (Blueprint $table) {
            $table->dropColumn('vapi_credential_id');
        });
    }
};
