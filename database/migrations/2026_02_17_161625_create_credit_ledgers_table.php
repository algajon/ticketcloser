<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('credit_ledgers', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('workspace_id');

            // purchase | usage | refund | adjustment
            $table->string('type', 30);
            // positive for purchases/refunds, negative for usage
            $table->integer('amount');

            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['workspace_id', 'created_at']);
            $table->foreign('workspace_id')->references('id')->on('workspaces')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_ledgers');
    }
};
