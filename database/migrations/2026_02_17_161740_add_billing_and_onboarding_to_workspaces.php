<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->integer('credits_balance')->default(0);
            $table->string('onboarding_step', 30)->nullable(); // company/billing/voice/intake/test/done
        });
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropColumn(['credits_balance', 'onboarding_step']);
        });
    }
};
