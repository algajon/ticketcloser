<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('intake_configs', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('workspace_id')->unique();

            $table->longText('system_prompt')->nullable();

            // examples: ["callerName","callbackNumber","category","priority","description"]
            $table->json('required_fields')->nullable();

            // optional: ["billing","technical","account","general"]
            $table->json('category_options')->nullable();

            // optional: {"not urgent":"low","urgent":"high"}
            $table->json('priority_rules')->nullable();

            $table->timestamps();

            $table->foreign('workspace_id')->references('id')->on('workspaces')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intake_configs');
    }
};
