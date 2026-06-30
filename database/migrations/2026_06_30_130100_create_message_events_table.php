<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assistant_config_id')->nullable()->constrained('assistant_configs')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('support_case_id')->nullable()->constrained('support_cases')->nullOnDelete();
            $table->foreignId('calendar_event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 24)->default('sms');
            $table->string('direction', 16)->default('outbound');
            $table->string('status', 32)->default('drafted');
            $table->string('provider', 40)->nullable();
            $table->string('external_message_id')->nullable();
            $table->string('from_phone', 40)->nullable();
            $table->string('to_phone', 40)->nullable();
            $table->text('body')->nullable();
            $table->text('response_body')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'created_at']);
            $table->index(['workspace_id', 'status']);
            $table->index(['workspace_id', 'direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_events');
    }
};
