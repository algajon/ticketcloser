<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'marketing_opted_in_at')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('marketing_opted_in_at')->nullable()->after('terms_version');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'marketing_opted_in_at')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('marketing_opted_in_at');
        });
    }
};
