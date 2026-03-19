<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('queue_assistant_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('queue_id')->constrained('queues')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->text('system_prompt')->nullable();
            $table->string('voice_provider')->nullable();
            $table->string('voice_id')->nullable();
            $table->string('vapi_assistant_id')->nullable();
            $table->string('vapi_tool_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_assistant_configs');
    }
};
