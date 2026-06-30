<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'welcome_email_sent_at')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('welcome_email_sent_at')->nullable()->after('marketing_opted_in_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'welcome_email_sent_at')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('welcome_email_sent_at');
        });
    }
};
