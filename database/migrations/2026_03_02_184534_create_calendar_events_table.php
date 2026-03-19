<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('case_id')->constrained('support_cases')->cascadeOnDelete();
            $table->foreignId('suggested_event_id')->nullable()->constrained('suggested_events')->nullOnDelete();
            $table->string('provider'); // google, calendly, ics
            $table->string('provider_event_id')->nullable(); // Google event ID or Calendly UUID
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('timezone', 64)->default('UTC');
            $table->string('status')->default('created'); // created, canceled, rescheduled
            $table->string('url')->nullable(); // Google event link or Calendly scheduling URL
            $table->json('payload')->nullable(); // full provider response
            $table->timestamps();

            $table->index(['workspace_id', 'case_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
