<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('prompt_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assistant_id')->nullable()->constrained('assistant_configs')->nullOnDelete();
            $table->string('name')->nullable();                // user-assigned label
            $table->string('assistant_type');                  // maintenance, mortgage, support, leasing
            $table->string('tone')->default('professional');   // professional, friendly, strict
            $table->string('strictness')->default('medium');   // low, medium, high
            $table->json('tools_enabled')->nullable();
            $table->text('input_summary');
            $table->longText('output_markdown');
            $table->timestamps();

            $table->index(['workspace_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prompt_versions');
    }
};
