<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('support_cases', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();

            $table->foreignId('workspace_id')
                ->constrained('workspaces')
                ->cascadeOnDelete();

            $table->string('case_number')->unique();

            $table->string('title', 200);
            $table->text('description')->nullable();

            $table->string('category', 100)->nullable();
            $table->string('priority', 20)->default('normal'); // low/normal/high/critical
            $table->string('status', 30)->default('new'); // new/triaged/in_progress/waiting/resolved/closed

            $table->string('requester_phone', 30)->nullable();
            $table->string('requester_email', 200)->nullable();

            $table->foreignId('assignee_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('source', 30)->default('voice'); // voice/web/api/email
            $table->string('external_call_id', 200)->nullable();

            $table->timestamps();

            $table->index(['workspace_id', 'status']);
            $table->index(['workspace_id', 'priority']);
            $table->index(['workspace_id', 'created_at']);
            $table->index('external_call_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_cases');
    }
};
