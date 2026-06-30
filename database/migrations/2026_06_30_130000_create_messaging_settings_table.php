<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messaging_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('booking_confirmation_enabled')->default(true);
            $table->text('booking_confirmation_template');
            $table->string('signature', 160)->nullable();
            $table->string('brand_voice', 40)->default('warm');
            $table->boolean('include_ticket_number')->default(true);
            $table->boolean('include_issue_label')->default(true);
            $table->boolean('reply_capture_enabled')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messaging_settings');
    }
};
