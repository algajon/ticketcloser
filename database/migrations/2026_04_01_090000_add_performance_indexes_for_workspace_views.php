<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::whenTableDoesntHaveIndex('support_cases', ['workspace_id', 'created_at'], function (Blueprint $table) {
            $table->index(['workspace_id', 'created_at'], 'support_cases_workspace_created_at_index');
        });

        Schema::whenTableDoesntHaveIndex('support_cases', ['workspace_id', 'status'], function (Blueprint $table) {
            $table->index(['workspace_id', 'status'], 'support_cases_workspace_status_index');
        });

        Schema::whenTableDoesntHaveIndex('support_cases', ['workspace_id', 'assistant_config_id'], function (Blueprint $table) {
            $table->index(['workspace_id', 'assistant_config_id'], 'support_cases_workspace_assistant_index');
        });

        Schema::whenTableDoesntHaveIndex('support_cases', ['workspace_id', 'priority'], function (Blueprint $table) {
            $table->index(['workspace_id', 'priority'], 'support_cases_workspace_priority_index');
        });

        Schema::whenTableDoesntHaveIndex('support_cases', ['workspace_id', 'external_call_id'], function (Blueprint $table) {
            $table->index(['workspace_id', 'external_call_id'], 'support_cases_workspace_external_call_index');
        });

        Schema::whenTableDoesntHaveIndex('call_events', ['workspace_id', 'created_at'], function (Blueprint $table) {
            $table->index(['workspace_id', 'created_at'], 'call_events_workspace_created_at_index');
        });

        Schema::whenTableDoesntHaveIndex('call_events', ['workspace_id', 'vapi_call_id'], function (Blueprint $table) {
            $table->index(['workspace_id', 'vapi_call_id'], 'call_events_workspace_vapi_call_index');
        });

        Schema::whenTableDoesntHaveIndex('assistant_configs', ['workspace_id', 'updated_at'], function (Blueprint $table) {
            $table->index(['workspace_id', 'updated_at'], 'assistant_configs_workspace_updated_at_index');
        });

        Schema::whenTableDoesntHaveIndex('workspace_phone_numbers', ['workspace_id', 'assistant_id'], function (Blueprint $table) {
            $table->index(['workspace_id', 'assistant_id'], 'workspace_phone_numbers_workspace_assistant_index');
        });

        Schema::whenTableDoesntHaveIndex('workspace_phone_numbers', ['workspace_id', 'updated_at'], function (Blueprint $table) {
            $table->index(['workspace_id', 'updated_at'], 'workspace_phone_numbers_workspace_updated_at_index');
        });

        Schema::whenTableDoesntHaveIndex('calendar_events', ['workspace_id', 'created_at'], function (Blueprint $table) {
            $table->index(['workspace_id', 'created_at'], 'calendar_events_workspace_created_at_index');
        });

        Schema::whenTableDoesntHaveIndex('calendar_events', ['workspace_id', 'starts_at'], function (Blueprint $table) {
            $table->index(['workspace_id', 'starts_at'], 'calendar_events_workspace_starts_at_index');
        });
    }

    public function down(): void
    {
        Schema::whenTableHasIndex('support_cases', 'support_cases_workspace_created_at_index', function (Blueprint $table) {
            $table->dropIndex('support_cases_workspace_created_at_index');
        });

        Schema::whenTableHasIndex('support_cases', 'support_cases_workspace_status_index', function (Blueprint $table) {
            $table->dropIndex('support_cases_workspace_status_index');
        });

        Schema::whenTableHasIndex('support_cases', 'support_cases_workspace_assistant_index', function (Blueprint $table) {
            $table->dropIndex('support_cases_workspace_assistant_index');
        });

        Schema::whenTableHasIndex('support_cases', 'support_cases_workspace_priority_index', function (Blueprint $table) {
            $table->dropIndex('support_cases_workspace_priority_index');
        });

        Schema::whenTableHasIndex('support_cases', 'support_cases_workspace_external_call_index', function (Blueprint $table) {
            $table->dropIndex('support_cases_workspace_external_call_index');
        });

        Schema::whenTableHasIndex('call_events', 'call_events_workspace_created_at_index', function (Blueprint $table) {
            $table->dropIndex('call_events_workspace_created_at_index');
        });

        Schema::whenTableHasIndex('call_events', 'call_events_workspace_vapi_call_index', function (Blueprint $table) {
            $table->dropIndex('call_events_workspace_vapi_call_index');
        });

        Schema::whenTableHasIndex('assistant_configs', 'assistant_configs_workspace_updated_at_index', function (Blueprint $table) {
            $table->dropIndex('assistant_configs_workspace_updated_at_index');
        });

        Schema::whenTableHasIndex('workspace_phone_numbers', 'workspace_phone_numbers_workspace_assistant_index', function (Blueprint $table) {
            $table->dropIndex('workspace_phone_numbers_workspace_assistant_index');
        });

        Schema::whenTableHasIndex('workspace_phone_numbers', 'workspace_phone_numbers_workspace_updated_at_index', function (Blueprint $table) {
            $table->dropIndex('workspace_phone_numbers_workspace_updated_at_index');
        });

        Schema::whenTableHasIndex('calendar_events', 'calendar_events_workspace_created_at_index', function (Blueprint $table) {
            $table->dropIndex('calendar_events_workspace_created_at_index');
        });

        Schema::whenTableHasIndex('calendar_events', 'calendar_events_workspace_starts_at_index', function (Blueprint $table) {
            $table->dropIndex('calendar_events_workspace_starts_at_index');
        });
    }
};
