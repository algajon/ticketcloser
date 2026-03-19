<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assistant_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();

            $table->string('name')->default('Ticketcloser Assistant');
            $table->longText('system_prompt')->nullable();

            $table->string('voice_provider')->nullable(); // e.g. "vapi" or "elevenlabs"
            $table->string('voice_id')->nullable();

            $table->string('vapi_tool_id')->nullable()->index();
            $table->string('vapi_assistant_id')->nullable()->index();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // MVP: one assistant config per workspace
            $table->unique('workspace_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_configs');
    }
};