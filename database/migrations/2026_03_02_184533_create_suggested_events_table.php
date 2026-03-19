<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('suggested_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('case_id')->constrained('support_cases')->cascadeOnDelete();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('timezone', 64)->default('UTC');
            $table->unsignedTinyInteger('confidence')->default(0); // 0–100
            $table->text('raw_text_span')->nullable(); // the original text that triggered detection
            $table->string('status')->default('pending'); // pending, confirmed, dismissed
            $table->timestamps();

            $table->index(['workspace_id', 'case_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suggested_events');
    }
};
