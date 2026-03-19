<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('support_cases', function (Blueprint $table) {
            $table->foreignId('queue_id')->nullable()->after('workspace_id')->constrained('queues')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->after('queue_id')->constrained('contacts')->nullOnDelete();
            $table->longText('transcript')->nullable()->after('description');
            $table->string('recording_url')->nullable()->after('transcript');
            $table->json('structured_payload')->nullable()->after('recording_url');
        });
    }

    public function down(): void
    {
        Schema::table('support_cases', function (Blueprint $table) {
            $table->dropColumn(['transcript', 'recording_url', 'structured_payload']);
            $table->dropForeign(['queue_id']);
            $table->dropForeign(['contact_id']);
            $table->dropColumn(['queue_id', 'contact_id']);
        });
    }
};
