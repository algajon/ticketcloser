<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('calendar_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('provider'); // google, calendly
            $table->text('tokens_encrypted'); // JSON of access/refresh tokens, AES-encrypted
            $table->timestamp('expires_at')->nullable();
            $table->string('calendly_scheduling_link')->nullable(); // for Calendly link-based flow
            $table->json('metadata')->nullable(); // calendar_id, email, etc.
            $table->timestamps();

            $table->unique(['workspace_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_connections');
    }
};
