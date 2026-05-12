<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            if (! Schema::hasColumn('workspaces', 'logo_path')) {
                $table->string('logo_path')->nullable()->after('default_vapi_credential_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            if (Schema::hasColumn('workspaces', 'logo_path')) {
                $table->dropColumn('logo_path');
            }
        });
    }
};
