<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('voice_configs', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('workspace_id')->unique();

            $table->string('provider', 30)->default('vapi');

            // MVP: paste these in (later we auto-provision)
            $table->string('assistant_id', 120)->nullable();
            $table->string('phone_number_id', 120)->nullable();
            $table->string('phone_number_e164', 20)->nullable(); // +1...

            $table->string('voice_id', 120)->nullable(); // e.g., "Elliot" or provider voice id

            $table->boolean('recording_enabled')->default(false);
            $table->boolean('transcript_enabled')->default(true);

            $table->timestamps();

            $table->foreign('workspace_id')->references('id')->on('workspaces')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voice_configs');
    }
};
