<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->bigIncrements('id'); // BIGINT UNSIGNED PK (explicit)
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('default_timezone')->default('America/New_York');
            $table->string('case_label')->default('Case');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspaces');
    }
};
