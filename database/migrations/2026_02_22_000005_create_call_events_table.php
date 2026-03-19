<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('call_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('queue_id')->nullable()->constrained('queues')->nullOnDelete();
            $table->string('vapi_call_id')->nullable()->index();
            $table->string('from_number')->nullable();
            $table->string('to_number')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->decimal('cost', 10, 4)->nullable();
            $table->longText('transcript')->nullable();
            $table->string('recording_url')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_events');
    }
};
