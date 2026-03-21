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
        Schema::table('workspace_phone_numbers', function (Blueprint $table) {
            $table->string('forwarding_number')->nullable()->after('e164');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workspace_phone_numbers', function (Blueprint $table) {
            $table->dropColumn('forwarding_number');
        });
    }
};
