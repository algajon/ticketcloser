<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('case_comments', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();

            $table->foreignId('workspace_id')
                ->constrained('workspaces')
                ->cascadeOnDelete();

            $table->foreignId('support_case_id')
                ->constrained('support_cases')
                ->cascadeOnDelete();

            $table->foreignId('author_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->boolean('is_internal')->default(true);
            $table->text('body');

            $table->timestamps();

            $table->index(['workspace_id', 'support_case_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_comments');
    }
};
