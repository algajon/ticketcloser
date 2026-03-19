<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workspace_phone_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();

            $table->string('e164')->nullable(); // "+1..."
            $table->string('vapi_phone_number_id')->nullable()->index();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // MVP: one phone number per workspace
            $table->unique('workspace_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_phone_numbers');
    }
};